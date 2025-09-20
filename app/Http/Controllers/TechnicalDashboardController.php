<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class TechnicalDashboardController extends Controller
{
    public function index()
    {
        $csvUrl  = env('BORROW_SHEET_CSV');
        $headers = [];
        $rows    = [];
        $counts  = [
            'borrowed'  => 0,
            'returned'  => 0,
            'stolen'    => 0,
            'available' => 0,
            'repair'    => 0, // under repair
        ];

        if ($csvUrl) {
            try {
                $resp = Http::timeout(10)->get($csvUrl);
                if ($resp->ok()) {
                    [$headers, $rows] = $this->parseCsv($resp->body());

                    // Case-insensitive header index
                    $index = $this->makeHeaderIndex($headers);

                    // Accept Status / State / Action as the status column
                    $statusKey = $index['status']
                        ?? $index['state']
                        ?? $index['action']
                        ?? null;

                    if ($statusKey !== null) {
                        foreach ($rows as $r) {
                            $raw    = $r[$statusKey] ?? '';
                            $status = strtolower(trim($raw));

                            // === Normalize to 5 buckets (based on your sheet screenshot) ===
                            if (in_array($status, [
                                'borrowed','loaned','checked out','on-loan','on loan'
                            ])) {
                                $counts['borrowed']++;
                            } elseif (in_array($status, [
                                'returned','checked in','in'
                            ])) {
                                $counts['returned']++;
                            } elseif (in_array($status, [
                                'stolen','stolem','missing/lost','missing','lost'
                            ])) {
                                $counts['stolen']++;
                            } elseif (in_array($status, [
                                'under repair','repair','under_repair','maintenance','service','fixing'
                            ])) {
                                $counts['repair']++;
                            } elseif (in_array($status, [
                                'available','in-stock','in stock','idle','retire' // map "retire" to available by default
                            ])) {
                                $counts['available']++;
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // network/timeout errors are ignored; view will show "No data" state
            }
        }

        return view('technical.dashboard', compact('headers', 'rows', 'counts'));
    }

    /**
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
                // Trim + strip possible UTF-8 BOM from first header cell
                if (isset($fields[0])) {
                    $fields[0] = ltrim($fields[0], "\xEF\xBB\xBF");
                }
                $headers = array_map(fn($h) => trim((string)$h), $fields);
                continue;
            }

            $rows[] = array_map(fn($v) => trim((string)$v), $fields);
        }

        return [$headers, $rows];
    }

    /**
     * Build a case-insensitive header index: ['status' => idx, ...]
     * @param array<int,string> $headers
     * @return array<string,int>
     */
    private function makeHeaderIndex(array $headers): array
    {
        $map = [];
        foreach ($headers as $i => $h) {
            $key = strtolower(trim((string)$h));
            if ($key !== '') {
                $map[$key] = $i;
            }
        }
        return $map;
    }
}
