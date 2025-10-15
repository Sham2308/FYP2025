<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\BorrowDetail;
use Carbon\Carbon;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $q = BorrowDetail::query();

        if ($request->filled('user')) {
            $s = $request->input('user');
            $q->where(function ($x) use ($s) {
                $x->where('UserID', 'LIKE', "%{$s}%")
                  ->orWhere('BorrowerName', 'LIKE', "%{$s}%");
            });
        }
        if ($request->filled('status')) $q->where('Status', $request->input('status'));
        if ($request->filled('from'))   $q->whereDate('BorrowDate', '>=', $request->input('from'));
        if ($request->filled('to'))     $q->whereDate('BorrowDate', '<=', $request->input('to'));

        // Feed the Blade with the same order as the sheet
        $history = $q->select([
                'UID as CardID',
                'BorrowerName',
                'UserID',
                'AssetID as ItemID',
                'BorrowDate',
                'ReturnDate',
                DB::raw('COALESCE(BorrowedAt, Name) as BorrowedAt'),
                'ReturnedAt',
                'Status',
                'Remarks',
            ])
            ->orderByDesc('BorrowDate')
            ->orderByDesc(DB::raw('COALESCE(ReturnDate, BorrowDate)'))
            ->orderByDesc('id')
            ->limit(2000)
            ->get();

        return view('history.index', [
            'history' => $history,
            'error'   => $history->isEmpty() ? 'No history data found.' : null,
        ]);
    }

    /**
     * Import BorrowDetails.
     * 1) If GOOGLE_BORROW_DETAILS_CSV_URL is set -> import from CSV (preferred).
     * 2) Else fallback to Google WebApp (POST, then GET).
     */
    public function importFromGoogleSheet()
    {
        $csvUrl = env('GOOGLE_BORROW_DETAILS_CSV_URL');

        if ($csvUrl) {
            try {
                $csv = Http::timeout(60)->get($csvUrl);
                if (!$csv->ok()) return back()->with('error', "CSV fetch failed (HTTP {$csv->status()}).");
                $rows = $this->parseBorrowDetailsCsv($csv->body());
                if (empty($rows)) return back()->with('error', 'CSV has no rows.');
                return $this->replaceBorrowDetails($rows);
            } catch (\Throwable $e) {
                return back()->with('error', 'CSV import error: '.$e->getMessage());
            }
        }

        // Fallback: Apps Script web app
        $url    = config('services.google.webapp_url');
        $secret = config('services.google.secret');
        if (!$url || !$secret) return back()->with('error', 'Google WebApp URL or secret is not configured.');

        $payload = ['secret' => $secret, 'type' => 'history'];

        try { $resp = Http::timeout(60)->asJson()->post($url, $payload); }
        catch (\Throwable $e) { $resp = null; Log::warning('[History Import] POST failed: '.$e->getMessage()); }

        if (!$resp || !$resp->ok()) {
            try { $resp = Http::timeout(60)->get($url, $payload); }
            catch (\Throwable $e) { return back()->with('error', 'Failed to reach Google Sheets: '.$e->getMessage()); }
        }
        if ($resp->failed()) return back()->with('error', 'Failed to fetch history (HTTP '.$resp->status().').');

        $json = $resp->json();
        $rows = $json['rows'] ?? (is_array($json) ? $json : []);
        if (!$rows) return back()->with('error', 'No history rows found in response.');

        // Normalize mixed payloads (handles leading Timestamp, wrong orders, etc.)
        $norm = [];
        foreach ($rows as $r) $norm[] = $this->normalizeHistoryRow($r);

        return $this->replaceBorrowDetails($norm);
    }

    /** Parse BorrowDetails CSV with headers:
     * CardID,BorrowerName,UserID,Item ID,BorrowDate,ReturnDate,BorrowedAt,ReturnedAt,Status,Remarks
     */
    private function parseBorrowDetailsCsv(string $csvBody): array
    {
        // Split lines, keep non-empty
        $lines = preg_split("/\r\n|\n|\r/", $csvBody);
        $lines = array_values(array_filter($lines, fn($l)=>$l!==null && $l!==''));

        if (count($lines) < 2) return [];

        $head = str_getcsv($lines[0]);
        $map  = $this->indexHeaders($head);

        $out = [];
        for ($i=1; $i<count($lines); $i++) {
            $cols = str_getcsv($lines[$i]);
            $get  = function(string $k) use ($cols, $map) {
                $idx = $map[$k] ?? null;
                return ($idx !== null && array_key_exists($idx, $cols)) ? $cols[$idx] : null;
            };

            $out[] = [
                'UID'           => $get('CardID'),
                'BorrowID'      => null,
                'UserID'        => $get('UserID'),
                'BorrowerName'  => $get('BorrowerName'),
                'AssetID'       => $get('ItemID'),
                'Name'          => null,
                'BorrowDate'    => $this->toYmd($get('BorrowDate')),
                'ReturnDate'    => $this->toYmd($get('ReturnDate')),
                'BorrowedAt'    => $this->toHm($get('BorrowedAt')),
                'ReturnedAt'    => $this->toHm($get('ReturnedAt')),
                'Status'        => $get('Status'),
                'Remarks'       => $get('Remarks'),
            ];
        }
        return $out;
    }

    /** Build a tolerant header index (accepts synonyms). */
    private function indexHeaders(array $head): array
    {
        $norm = fn($s)=> strtolower(trim(preg_replace('/\s+/', '', (string)$s)));

        $want = [
            'CardID'     => ['cardid','uid'],
            'BorrowerName'=>['borrowername','borrower','name'],
            'UserID'     => ['userid','user id'],
            'ItemID'     => ['itemid','item id','assetid','asset id'],
            'BorrowDate' => ['borrowdate','borrow date','dateborrow','date'],
            'ReturnDate' => ['returndate','return date','datereturn'],
            'BorrowedAt' => ['borrowedat','borrowed at','borrowtime','borrow time','timeborrowed'],
            'ReturnedAt' => ['returnedat','returned at','returntime','return time','timereturned'],
            'Status'     => ['status'],
            'Remarks'    => ['remarks','remark','notes','note'],
        ];

        $idx = [];
        foreach ($want as $key => $alts) {
            foreach ($head as $i => $h) {
                $hN = $norm($h);
                foreach ($alts as $a) {
                    if ($hN === $norm($a)) { $idx[$key] = $i; break 2; }
                }
            }
        }
        return $idx;
    }

    /** Replace table with normalized rows. */
    private function replaceBorrowDetails(array $rows)
    {
        if (empty($rows)) {
            return back()->with('error', 'Nothing to import.');
        }

        try {
            DB::beginTransaction();

            // Safer than TRUNCATE inside transactions
            DB::table('borrow_details')->delete();

            // Bulk insert (faster, avoids fillable)
            $now = now();
            $prepared = array_map(function ($r) use ($now) {
                // ensure all expected keys exist
                $defaults = [
                    'UID' => null, 'BorrowID' => null, 'UserID' => null, 'BorrowerName' => null,
                    'AssetID' => null, 'Name' => null, 'BorrowDate' => null, 'ReturnDate' => null,
                    'BorrowedAt' => null, 'ReturnedAt' => null, 'Status' => null, 'Remarks' => null,
                    'created_at' => $now, 'updated_at' => $now,
                ];
                return array_replace($defaults, $r);
            }, $rows);

            foreach (array_chunk($prepared, 1000) as $chunk) {
                DB::table('borrow_details')->insert($chunk);
            }

            DB::commit();
            return redirect()->route('history.index')
                ->with('success', 'BorrowDetails import successful ('.count($rows).' rows).');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'CSV import error: '.$e->getMessage());
        }
    }


    /** Robust normalizer for web-app payloads (handles Timestamp, permutations). */
    private function normalizeHistoryRow($row): array
    {
        // Numeric list path
        if (is_array($row) && $this->isList($row)) {
            $v = array_values($row);

            // Strip leading Timestamp if present
            if ($this->looksLikeTimestamp($v[0] ?? null)) array_shift($v);

            // Expect 10-col layout now (pad if short)
            $v = array_pad($v, 10, null);

            return [
                'UID'           => $v[0],                        // CardID
                'BorrowID'      => null,
                'UserID'        => $v[2],
                'BorrowerName'  => $v[1],
                'AssetID'       => $v[3],                        // ItemID
                'Name'          => null,
                'BorrowDate'    => $this->toYmd($v[4]),
                'ReturnDate'    => $this->toYmd($v[5]),
                'BorrowedAt'    => $this->toHm($v[6]),
                'ReturnedAt'    => $this->toHm($v[7]),
                'Status'        => $v[8],
                'Remarks'       => $v[9],
            ];
        }

        // Associative path
        $g = fn($k) => $row[$k] ?? $row[strtolower($k)] ?? null;

        // If a 'Timestamp' key exists, ignore it.
        $card = $g('CardID') ?? $g('UID');

        return [
            'UID'           => $card,
            'BorrowID'      => $g('BorrowID'),
            'UserID'        => $g('UserID'),
            'BorrowerName'  => $g('BorrowerName'),
            'AssetID'       => $g('ItemID') ?? $g('AssetID'),
            'Name'          => $g('Name'),
            'BorrowDate'    => $this->toYmd($g('BorrowDate')),
            'ReturnDate'    => $this->toYmd($g('ReturnDate')),
            'BorrowedAt'    => $this->toHm($g('BorrowedAt')),
            'ReturnedAt'    => $this->toHm($g('ReturnedAt')),
            'Status'        => $g('Status'),
            'Remarks'       => $g('Remarks'),
        ];
    }

    private function looksLikeTimestamp($v): bool
    {
        if ($v === null || $v === '') return false;
        if (is_numeric($v)) return $v > 40000 && $v < 80000; // Excel serial date range
        if (!is_string($v)) return false;
        return (bool)preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}:\d{2}(?:\.\d+)?Z?)$/', $v);
    }

    /** Excel serial or ISO date → 'Y-m-d' */
    private function toYmd($v): ?string
    {
        if ($v === null || $v === '') return null;

        if (is_numeric($v)) {
            $n = (float)$v;
            if ($n >= 1) { // date (not time fraction)
                $ts = ($n - 25569) * 86400;
                return gmdate('Y-m-d', (int)round($ts));
            }
            return null;
        }

        try { return Carbon::parse((string)$v)->format('Y-m-d'); }
        catch (\Throwable $e) { return null; }
    }

    /** Excel time fraction / HH:mm(:ss) / datetime → 'H:i' */
    private function toHm($v): ?string
    {
        if ($v === null || $v === '') return null;

        if (is_numeric($v)) {
            $seconds = (int)round(((float)$v) * 86400);
            return gmdate('H:i', $seconds);
        }

        $s = (string)$v;
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $s)) return substr($s, 0, 5);

        try { return Carbon::parse($s)->format('H:i'); }
        catch (\Throwable $e) { return null; }
    }

    /** PHP < 8.1 polyfill */
    private function isList(array $arr): bool
    {
        $i = 0; foreach ($arr as $k => $_) { if ($k !== $i++) return false; }
        return true;
    }
}
