<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Item;

class TechnicalDashboardController extends Controller
{
    public function index()
    {
        // ─────────────────────────────────────────────────────────────
        // 1) Asset Status Overview (Google Sheet CSV → base counts)
        // ─────────────────────────────────────────────────────────────
        $csvUrl  = env('BORROW_SHEET_CSV');
        $headers = [];
        $rows    = [];
        $counts  = [
            'borrowed'  => 0,
            'returned'  => 0, // only comes from sheet
            'stolen'    => 0,
            'available' => 0,
            'repair'    => 0, // "under repair"
        ];

        if ($csvUrl) {
            try {
                $resp = Http::timeout(10)->get($csvUrl);
                if ($resp->ok()) {
                    [$headers, $rows] = $this->parseCsv($resp->body());
                    $index = $this->makeHeaderIndex($headers);

                    $statusKey = $index['status']
                        ?? $index['state']
                        ?? $index['action']
                        ?? null;

                    if ($statusKey !== null) {
                        foreach ($rows as $r) {
                            $raw    = $r[$statusKey] ?? '';
                            $status = strtolower(trim($raw));

                            if (in_array($status, ['borrowed','loaned','checked out','on-loan','on loan'], true)) {
                                $counts['borrowed']++;
                            } elseif (in_array($status, ['returned','checked in','in'], true)) {
                                $counts['returned']++;
                            } elseif (in_array($status, ['stolen','stolem','missing/lost','missing','lost'], true)) {
                                $counts['stolen']++;
                            } elseif (in_array($status, ['under repair','repair','under_repair','maintenance','service','fixing'], true)) {
                                $counts['repair']++;
                            } elseif (in_array($status, ['available','in-stock','in stock','idle','retire'], true)) {
                                $counts['available']++;
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // swallow network/timeout errors; the view can show "No data"
            }
        }

        // ─────────────────────────────────────────────────────────────
        // 2) Reconcile with DB so the cards match Inventory dashboard
        //    (DB is the source of truth for item statuses)
        // ─────────────────────────────────────────────────────────────
        $dbByStatus = Item::select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        // Override sheet counts where the DB has authoritative values
        $counts['borrowed']  = $dbByStatus['borrowed']     ?? $counts['borrowed'];
        $counts['stolen']    = $dbByStatus['stolen']       ?? $counts['stolen'];
        $counts['available'] = $dbByStatus['available']    ?? $counts['available'];
        $counts['repair']    = $dbByStatus['under repair'] ?? $counts['repair'];
        // Note: 'returned' remains from the sheet (no such item status)

        // ─────────────────────────────────────────────────────────────
        // 3) Under Repair table (exact DB records)
        // ─────────────────────────────────────────────────────────────
        $UNDER_REPAIR = defined(\App\Models\Item::class . '::STATUS_UNDER_REPAIR')
            ? Item::STATUS_UNDER_REPAIR
            : 'under repair';

        $borrowItems = Item::where('status', $UNDER_REPAIR)
            ->orderByDesc('asset_id')
            ->get();

        // ─────────────────────────────────────────────────────────────
        // 4) Notifications for navbar bell
        // ─────────────────────────────────────────────────────────────
        $unread = collect();
        $unreadCount = 0;
        if (Auth::check()) {
            $unread = Auth::user()->unreadNotifications()->limit(10)->get();
            $unreadCount = $unread->count();
        }

        return view('technical.dashboard', compact(
            'headers',
            'rows',
            'counts',
            'borrowItems',
            'unread',
            'unreadCount'
        ));
    }

    /**
     * Parse CSV → [headers, rows]
     *
     * @return array{0: array<int,string>, 1: array<int,array<int,string>>}
     */
    private function parseCsv(string $csv): array
    {
        $lines   = preg_split("/\r\n|\n|\r/", trim($csv));
        $headers = [];
        $rows    = [];

        foreach ($lines as $i => $line) {
            $fields = str_getcsv($line);

            if ($i === 0) {
                if (isset($fields[0])) {
                    $fields[0] = ltrim($fields[0], "\xEF\xBB\xBF");
                }
                $headers = array_map(fn($h) => trim((string) $h), $fields);
                continue;
            }

            $rows[] = array_map(fn($v) => trim((string) $v), $fields);
        }

        return [$headers, $rows];
    }

    /**
     * Build case-insensitive header index
     *
     * @param array<int,string> $headers
     * @return array<string,int>
     */
    private function makeHeaderIndex(array $headers): array
    {
        $map = [];
        foreach ($headers as $i => $h) {
            $key = strtolower(trim((string) $h));
            if ($key !== '') {
                $map[$key] = $i;
            }
        }
        return $map;
    }
}
