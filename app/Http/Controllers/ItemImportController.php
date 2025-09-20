<?php

// app/Http/Controllers/ItemImportController.php
namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ItemImportController extends Controller
{
    public function importFromGoogle(Request $request)
    {
        Log::info('🚀 Import route fired', ['url' => env('GOOGLE_SHEET_CSV_URL')]);

        $csvUrl = env('GOOGLE_SHEET_CSV_URL');
        if (!$csvUrl) {
            return back()->with('error', 'GOOGLE_SHEET_CSV_URL is not set in .env');
        }

        try {
            $res = Http::timeout(20)->get($csvUrl);

            if (!$res->ok()) {
                Log::error('CSV fetch failed', [
                    'status' => $res->status(),
                    'body'   => substr($res->body() ?? '', 0, 500),
                ]);
                return back()->with('error', 'Failed to fetch CSV (HTTP '.$res->status().')');
            }

            $rows = $this->parseCsv($res->body());
            if (count($rows) < 2) {
                Log::warning('CSV parsed but has no data or header row', ['rows_count' => count($rows)]);
                return back()->with('error', 'CSV has no data or missing header row.');
            }

            // Normalize header: lowercase + trim
            $header = array_map(fn($h) => strtolower(trim($h)), $rows[0]);

            // Optional: ensure required headers exist
            if (!in_array('asset_id', $header)) {
                return back()->with('error', 'CSV header must include "asset_id".');
            }

            $imported = 0;
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if ($this->isEmpty($row)) continue;

                $data = $this->combine($header, $row);

                $assetId = $data['asset_id'] ?? null;
                if (!$assetId) {
                    Log::warning('Skipping row without asset_id', ['row' => $row]);
                    continue;
                }

                Item::updateOrCreate(
                    ['asset_id' => $assetId],
                    [
                        'uid'           => $data['uid'] ?? null,
                        'name'          => $data['name'] ?? '',
                        'detail'        => $data['detail'] ?? null,
                        'accessories'   => $data['accessories'] ?? null,
                        'type_id'       => $data['type_id'] ?? null,
                        'serial_no'     => $data['serial_no'] ?? null,
                        'status'        => $data['status'] ?? 'available',
                        'purchase_date' => $this->toSqlDate($data['purchase_date'] ?? null),
                        'remarks'       => $data['remarks'] ?? null,
                    ]
                );

                $imported++;
            }

            return back()->with('success', "Imported/updated {$imported} item(s) from Google Sheet.");
        } catch (\Throwable $e) {
            Log::error('Items import failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Import failed: '.$e->getMessage());
        }
    }

    private function parseCsv(string $csv): array
    {
        // Strip UTF-8 BOM if present
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv);

        $rows = [];
        $fh = fopen('php://temp', 'r+');
        fwrite($fh, $csv);
        rewind($fh);
        while (($r = fgetcsv($fh)) !== false) {
            // skip blank lines that come through as [null] or ['']
            if (count($r) === 1 && ($r[0] === null || trim((string)$r[0]) === '')) {
                continue;
            }
            $rows[] = $r;
        }
        fclose($fh);
        return $rows;
    }

    private function combine(array $header, array $row): array
    {
        $out = [];
        foreach ($header as $i => $key) {
            $out[$key] = $row[$i] ?? null;
        }
        return $out;
    }

    private function isEmpty(array $row): bool
    {
        foreach ($row as $c) {
            if (trim((string)$c) !== '') return false;
        }
        return true;
    }

    private function toSqlDate(?string $v): ?string
{
    if (!$v) return null;
    $v = trim($v);

    // try strict known formats first
    $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y'];
    foreach ($formats as $fmt) {
        $dt = \DateTime::createFromFormat($fmt, $v);
        if ($dt && $dt->format($fmt) === $v) {
            return $dt->format('Y-m-d'); // MySQL-friendly
        }
    }

    // fallback: let strtotime try
    $ts = strtotime($v);
    return $ts ? date('Y-m-d', $ts) : null;
}

}

