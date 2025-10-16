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

    public function importFromGoogleSheet()
{
    try {
        $gs = app(\App\Services\GoogleSheetService::class);
        if (!$gs->isReady()) {
            return back()->with('error', 'Google Sheets API not configured.');
        }

        // ✅ Read directly from the BorrowDetails sheet
        $values = $gs->getValues('BorrowDetails!A:J')->getValues();
        if (!$values || count($values) <= 1) {
            return back()->with('error', 'No history data found in Google Sheet.');
        }

        $rows = array_slice($values, 1); // skip header row

        $normalized = [];
        foreach ($rows as $r) {
            $normalized[] = [
                'UID'          => $r[0] ?? '', // CardID
                'BorrowerName' => $r[1] ?? '',
                'UserID'       => $r[2] ?? '',
                'AssetID'      => $r[3] ?? '', // ItemID
                'BorrowDate'   => $r[4] ?? '',
                'ReturnDate'   => $r[5] ?? '',
                'BorrowedAt'   => $r[6] ?? '',
                'ReturnedAt'   => $r[7] ?? '',
                'Status'       => $r[8] ?? '',
                'Remarks'      => $r[9] ?? '',
            ];
        }

        try {
    DB::beginTransaction();

    // ✅ Use delete() instead of truncate() inside a transaction
    DB::table('borrow_details')->delete();

    foreach (array_chunk($normalized, 1000) as $chunk) {
        DB::table('borrow_details')->insert($chunk);
    }

    DB::commit();
} catch (\Throwable $e) {
    if (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
    throw $e;
}


        return redirect()->route('history.index')->with('success', '✅ Borrow history successfully imported.');
    } catch (\Throwable $e) {
        DB::rollBack();
        \Log::error('importFromGoogleSheet failed: '.$e->getMessage());
        return back()->with('error', '❌ Import failed: '.$e->getMessage());
    }
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

    /** Build a tolerant header index (accepts synonyms and your exact sheet layout). */
private function indexHeaders(array $head): array
{
    $norm = fn($s) => strtolower(trim(preg_replace('/\s+/', '', (string)$s)));

    $want = [
        'CardID'       => ['cardid','uid'],
        'BorrowerName' => ['borrowername','borrower','name'],
        'UserID'       => ['userid','user id'],
        'ItemID'       => ['itemid','item id','assetid','asset id'], // ✅ fixed to include "Item ID"
        'BorrowDate'   => ['borrowdate','borrow date','dateborrow','date'],
        'ReturnDate'   => ['returndate','return date','datereturn'],
        'BorrowedAt'   => ['borrowedat','borrowed at','borrowtime','borrow time','timeborrowed'],
        'ReturnedAt'   => ['returnedat','returned at','returntime','return time','timereturned'],
        'Status'       => ['status'],
        'Remarks'      => ['remarks','remark','notes','note'],
    ];

    $idx = [];
    foreach ($want as $key => $alts) {
        foreach ($head as $i => $h) {
            $hN = $norm($h);
            foreach ($alts as $a) {
                if ($hN === $norm($a)) {
                    $idx[$key] = $i;
                    break 2;
                }
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


    /** Normalize rows from web-app payloads or CSV with possible Timestamp column. */
private function normalizeHistoryRow($row): array
{
    if (is_array($row) && $this->isList($row)) {
        $v = array_values($row);

        // ✅ Detect & remove Timestamp column if first looks like datetime
        if ($this->looksLikeTimestamp($v[0] ?? null)) {
            array_shift($v);
        }

        // Pad missing columns to at least 9
        $v = array_pad($v, 9, null);

        return [
            'UID'          => $v[0] ?? null,  // CardID
            'BorrowerName' => $v[1] ?? null,
            'UserID'       => $v[2] ?? null,
            'AssetID'      => $v[3] ?? null,  // Item ID
            'BorrowDate'   => $this->toYmd($v[4] ?? null),
            'ReturnDate'   => $this->toYmd($v[5] ?? null),
            'BorrowedAt'   => $this->toHm($v[6] ?? null),
            'ReturnedAt'   => $this->toHm($v[7] ?? null),
            'Status'       => $v[8] ?? null,
            'Remarks'      => $v[9] ?? null,
        ];
    }

    // Associative fallback
    $g = fn($k) => $row[$k] ?? $row[strtolower($k)] ?? null;

    return [
        'UID'          => $g('CardID') ?? $g('UID'),
        'BorrowerName' => $g('BorrowerName'),
        'UserID'       => $g('UserID'),
        'AssetID'      => $g('Item ID') ?? $g('ItemID') ?? $g('AssetID'),
        'BorrowDate'   => $this->toYmd($g('BorrowDate')),
        'ReturnDate'   => $this->toYmd($g('ReturnDate')),
        'BorrowedAt'   => $this->toHm($g('BorrowedAt')),
        'ReturnedAt'   => $this->toHm($g('ReturnedAt')),
        'Status'       => $g('Status'),
        'Remarks'      => $g('Remarks'),
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