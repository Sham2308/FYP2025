<?php

namespace App\Actions;

use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncItemsFromSheet
{
    public function __invoke(): int
    {
        $url = config('services.google_sheet.csv_url', env('GOOGLE_SHEET_CSV_URL'));
        if (!$url) throw new \RuntimeException('GOOGLE_SHEET_CSV_URL is not set.');

        $etag = Cache::get('sheet_items_etag');
        $lm   = Cache::get('sheet_items_lastmod');

        $res = Http::timeout(20)->withHeaders(array_filter([
            'If-None-Match'     => $etag,
            'If-Modified-Since' => $lm,
        ]))->get($url);

        if ($res->status() === 304) {
            Cache::put('items_last_sync_at', now()->toDateTimeString(), 86400);
            Log::info('[ItemsSync] 304 Not Modified');
            return 0;
        }
        if (!$res->ok()) throw new \RuntimeException('CSV fetch failed: '.$res->status());

        Cache::put('sheet_items_etag', $res->header('ETag'), 86400);
        Cache::put('sheet_items_lastmod', $res->header('Last-Modified'), 86400);

        $rows = $this->parseCsv($res->body());

        Item::query()->upsert($rows, ['asset_id'], [
            'uid','name','detail','accessories','type_id','serial_no','status','purchase_date','remarks','updated_at',
        ]);

        Cache::put('items_last_sync_at', now()->toDateTimeString(), 86400);
        return count($rows);
    }

    private function parseCsv(string $csv): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($csv));
        if (!$lines) return [];

        $headerRaw = str_getcsv(array_shift($lines));
        // normalize header: lowercase, strip non-alnum
        $norm = fn($s) => strtolower(preg_replace('/[^a-z0-9]/', '', (string)$s));
        $headerMap = [];
        foreach ($headerRaw as $i => $h) $headerMap[$norm($h)] = $i;

        // accepted aliases per field
        $aliases = [
            'uid'           => ['uid','nfcuid','rfid','rfiduid','carduid','taguid'],
            'asset_id'      => ['assetid','asset_id','asset','assetno','assetnumber','assetnum','assetcode','assetno.', 'asset no','asset number','id','itemid'],
            'name'          => ['name','item','itemname','equipment','equipmentname','assetname','asset name','title'],
            'detail'        => ['detail','details','description','desc','spec','specs','model'],
            'accessories'   => ['accessories','accessory','acc','included','inclusions','include'],
            'type_id'       => ['typeid','type_id','type','categoryid','category','catid'],
            'serial_no'     => ['serialno','serial_no','serial','sn','s/n','serialnumber','serial number'],
            'status'        => ['status','state','availability','available','condition'],
            'purchase_date' => ['purchasedate','purchase_date','datepurchased','date','acquireddate','acquired'],
            'remarks'       => ['remarks','remark','note','notes','comment','comments'],
        ];

        $col = function(array $cands) use ($headerMap, $norm): ?int {
            foreach ($cands as $c) {
                $k = $norm($c);
                if (array_key_exists($k, $headerMap)) return $headerMap[$k];
            }
            return null;
        };

        $getField = function(array $row, array $cands) use ($col) {
            $i = $col($cands);
            return $i !== null ? ($row[$i] ?? null) : null;
        };

        $out = [];
        $skipped = 0;

        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            $r = str_getcsv($line);

            $assetId = trim((string)($getField($r, $aliases['asset_id']) ?? ''));
            if ($assetId === '') { $skipped++; continue; } // must have unique key

            $uid      = trim((string)($getField($r, $aliases['uid']) ?? ''));
            $name     = trim((string)($getField($r, $aliases['name']) ?? ''));
            $detail   = trim((string)($getField($r, $aliases['detail']) ?? ''));
            $acc      = trim((string)($getField($r, $aliases['accessories']) ?? ''));
            $typeId   = trim((string)($getField($r, $aliases['type_id']) ?? ''));
            $serial   = trim((string)($getField($r, $aliases['serial_no']) ?? ''));
            $status   = trim((string)($getField($r, $aliases['status']) ?? ''));
            $purchRaw =          ($getField($r, $aliases['purchase_date']) ?? null);
            $remarks  = trim((string)($getField($r, $aliases['remarks']) ?? ''));

            $out[] = [
                'uid'           => $uid,
                'asset_id'      => $assetId,
                'name'          => $name,
                'detail'        => $detail,
                'accessories'   => $acc,
                'type_id'       => $typeId,
                'serial_no'     => $serial,
                'status'        => $status,
                'purchase_date' => $this->normalizeDate($purchRaw),
                'remarks'       => $remarks,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }

        if ($skipped > 0) {
            Log::warning("[ItemsSync] Skipped {$skipped} rows without asset_id");
        }

        return $out;
    }

    // Supports: 30/08/2025, 30-08-2025, 2025-08-30, 08/30/2025, Excel serials.
    private function normalizeDate($v): ?string
    {
        $v = trim((string)($v ?? ''));
        if ($v === '' || strtolower($v) === 'n/a') return null;

        $formats = ['d/m/Y','d-m-Y','Y-m-d','m/d/Y','m-d-Y'];
        foreach ($formats as $f) {
            $dt = \DateTime::createFromFormat($f, $v);
            if ($dt && $dt->format($f) === $v) return $dt->format('Y-m-d');
        }

        if (is_numeric($v)) {
            $base = new \DateTime('1899-12-30');
            $base->modify('+' . (int)$v . ' days');
            return $base->format('Y-m-d');
        }

        try { return Carbon::parse($v)->toDateString(); } catch (\Throwable) { return null; }
    }
}