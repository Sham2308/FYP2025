<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\BorrowDetail;
use Carbon\Carbon;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $q = BorrowDetail::query();

        // Filter by UserID or BorrowerName
        if ($request->filled('user')) {
            $search = $request->input('user');
            $q->where(function ($x) use ($search) {
                $x->where('UserID', 'LIKE', "%{$search}%")
                  ->orWhere('BorrowerName', 'LIKE', "%{$search}%");
            });
        }

        // Filter by Status
        if ($request->filled('status')) {
            $q->where('Status', $request->input('status'));
        }

        // Filter by BorrowDate range
        if ($request->filled('from')) {
            $q->whereDate('BorrowDate', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('BorrowDate', '<=', $request->input('to'));
        }

        $history = $q->orderByDesc('id')->get();

        return view('history.index', [
            'history' => $history,
            'error'   => $history->isEmpty() ? 'No history data found.' : null,
        ]);
    }

    /**
     * Import BorrowDetails from Google Apps Script and save to DB.
     * Expects rows in the exact Google Sheet column order A..L.
     */
    public function importFromGoogleSheet()
    {
        $url    = config('services.google.webapp_url');
        $secret = config('services.google.secret');

        if (empty($url) || empty($secret)) {
            return back()->with('error', 'Google WebApp URL or secret is not configured.');
        }

        try {
            $response = Http::timeout(20)->asJson()->post($url, [
                'secret' => $secret,
                'type'   => 'history',
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to reach Google Sheets: '.$e->getMessage());
        }

        if ($response->failed()) {
            return back()->with('error', 'Failed to fetch history (HTTP '.$response->status().').');
        }

        $payload = $response->json();
        $rows = $payload['rows'] ?? [];

        if (!$rows) {
            return back()->with('error', 'No history rows found.');
        }

        // Replace all records
        BorrowDetail::truncate();

        $inserted = 0;
        foreach ($rows as $row) {
            $norm = $this->normalizeHistoryRow($row); // A..L mapping (no Timestamp)
            BorrowDetail::create($norm);
            $inserted++;
        }

        return redirect()->route('history.index')
            ->with('success', "BorrowDetails import successful. Replaced table with {$inserted} records.");
    }

    /**
     * Normalize one row into DB-ready fields (no Timestamp).
     * Accepts:
     *  - numeric list [A..L]
     *  - associative array with keys matching the sheet headers
     */
    private function normalizeHistoryRow($row): array
    {
        // If it's a simple numeric list, treat it as A..L
        if (is_array($row) && $this->isList($row)) {
            $r = array_pad(array_values($row), 12, null); // ensure 12 cols
            return [
                // A..L exactly (NO Timestamp)
                'UID'           => $r[0],                 // A
                'BorrowID'      => $r[1],                 // B
                'UserID'        => $r[2],                 // C
                'BorrowerName'  => $r[3],                 // D
                'AssetID'       => $r[4],                 // E
                'Name'          => $r[5],                 // F
                'BorrowDate'    => $this->toYmd($r[6]),   // G
                'ReturnDate'    => $this->toYmd($r[7]),   // H
                'BorrowedAt'    => $this->toHm($r[8]),    // I
                'ReturnedAt'    => $this->toHm($r[9]),    // J
                'Status'        => $r[10],                // K
                'Remarks'       => $r[11],                // L
            ];
        }

        // Associative fallback
        $get = function ($k) use ($row) {
            return $row[$k] ?? $row[strtolower($k)] ?? null;
        };

        return [
            'UID'           => $get('UID'),
            'BorrowID'      => $get('BorrowID'),
            'UserID'        => $get('UserID'),
            'BorrowerName'  => $get('BorrowerName'),
            'AssetID'       => $get('AssetID'),
            'Name'          => $get('Name'),
            'BorrowDate'    => $this->toYmd($get('BorrowDate')),
            'ReturnDate'    => $this->toYmd($get('ReturnDate')),
            'BorrowedAt'    => $this->toHm($get('BorrowedAt')),
            'ReturnedAt'    => $this->toHm($get('ReturnedAt')),
            'Status'        => $get('Status'),
            'Remarks'       => $get('Remarks'),
        ];
    }

    /** Excel serial or ISO date → 'Y-m-d' (Sheet G/H) */
    private function toYmd($v): ?string
    {
        if ($v === null || $v === '') return null;

        if (is_numeric($v)) {
            $n = (float) $v;
            // Excel date serials are >= 1; convert to Unix time from 1899-12-30 base
            if ($n >= 1) {
                $ts = ($n - 25569) * 86400;
                return gmdate('Y-m-d', (int) round($ts));
            }
            // <1 means it's a time fraction, not a date
            return null;
        }

        try {
            return Carbon::parse((string) $v)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Excel time fraction / 'HH:mm(:ss)' / datetime → 'H:i' (Sheet I/J) */
    private function toHm($v): ?string
    {
        if ($v === null || $v === '') return null;

        if (is_numeric($v)) {
            // Excel time is fraction of a day
            $seconds = (int) round(((float) $v) * 86400);
            return gmdate('H:i', $seconds);
        }

        $s = (string) $v;
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $s)) {
            return substr($s, 0, 5);
        }

        try {
            return Carbon::parse($s)->format('H:i');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** PHP < 8.1 polyfill for array_is_list */
    private function isList(array $arr): bool
    {
        $i = 0;
        foreach ($arr as $k => $_) {
            if ($k !== $i++) return false;
        }
        return true;
    }
}
