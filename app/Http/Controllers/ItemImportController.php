<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ItemImportController extends Controller
{
    /**
     * Import inventory items from Google Sheet CSV.
     */
    public function importFromGoogle(Request $request)
    {
        Log::info('🚀 [Import] Import route triggered', ['csv_url' => env('GOOGLE_SHEET_CSV_URL')]);

        $csvUrl = env('GOOGLE_SHEET_CSV_URL');
        if (!$csvUrl) {
            $message = '⚠️ GOOGLE_SHEET_CSV_URL is not set in .env file.';
            return $request->ajax()
                ? response()->json(['success' => false, 'message' => $message], 400)
                : back()->with('error', $message);
        }

        try {
            $res = Http::timeout(20)->get($csvUrl);
            if (!$res->ok()) {
                Log::error('[Import] CSV fetch failed', ['status' => $res->status()]);
                $message = 'Failed to fetch CSV (HTTP '.$res->status().')';
                return $request->ajax()
                    ? response()->json(['success' => false, 'message' => $message], 500)
                    : back()->with('error', $message);
            }

            $rows = $this->parseCsv($res->body());
            if (count($rows) < 2) {
                $message = 'CSV file is empty or missing header.';
                return $request->ajax()
                    ? response()->json(['success' => false, 'message' => $message], 400)
                    : back()->with('error', $message);
            }

            $header = array_map(fn($h) => strtolower(trim($h)), $rows[0]);
            if (!in_array('asset_id', $header)) {
                $message = 'CSV header must include "asset_id".';
                return $request->ajax()
                    ? response()->json(['success' => false, 'message' => $message], 400)
                    : back()->with('error', $message);
            }

            $imported = 0;
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if ($this->isEmpty($row)) continue;

                $data = $this->combine($header, $row);
                $assetId = $data['asset_id'] ?? null;
                if (!$assetId) continue;

                $status = strtolower(trim($data['status'] ?? 'available'));
                $allowedStatuses = ['available', 'borrowed', 'under repair', 'retire', 'stolen', 'missing/lost'];
                if (!in_array($status, $allowedStatuses)) $status = 'available';

                Item::updateOrCreate(
                    ['asset_id' => $assetId],
                    [
                        // 👇 Only change: map "Item ID" -> uid (headers are lowercased)
                        'uid'           => $data['uid'] ?? $data['item id'] ?? $data['item_id'] ?? null,
                        'name'          => $data['name'] ?? '',
                        'detail'        => $data['detail'] ?? null,
                        'accessories'   => $data['accessories'] ?? null,
                        'type_id'       => $data['type_id'] ?? null,
                        'serial_no'     => $data['serial_no'] ?? null,
                        'status'        => $status,
                        'purchase_date' => $this->toSqlDate($data['purchase_date'] ?? null),
                        'remarks'       => $data['remarks'] ?? null,
                    ]
                );

                $imported++;
            }

            $message = "✅ Imported/updated {$imported} item(s) from Google Sheet.";
            Log::info('[Import] Success', ['count' => $imported]);

            return $request->ajax()
                ? response()->json(['success' => true, 'message' => $message])
                : back()->with('success', $message);

        } catch (\Throwable $e) {
            Log::error('[Import] Failed', ['error' => $e->getMessage()]);
            $message = 'Import failed: '.$e->getMessage();
            return $request->ajax()
                ? response()->json(['success' => false, 'message' => $message], 500)
                : back()->with('error', $message);
        }
    }

    private function parseCsv(string $csv): array
    {
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv);
        $rows = [];
        $fh = fopen('php://temp', 'r+');
        fwrite($fh, $csv);
        rewind($fh);

        while (($r = fgetcsv($fh)) !== false) {
            if (count($r) === 1 && ($r[0] === null || trim((string)$r[0]) === '')) continue;
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

        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y'];
        foreach ($formats as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $v);
            if ($dt && $dt->format($fmt) === $v) {
                return $dt->format('Y-m-d');
            }
        }

        $ts = strtotime($v);
        return $ts ? date('Y-m-d', $ts) : null;
    }
}