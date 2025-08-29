<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use Illuminate\Support\Facades\Http;
use App\Services\GoogleSheetService;
use Carbon\Carbon;

class InventoryController extends Controller
{
    /**
     * Show the Inventory dashboard.
     */
    public function index()
    {
        // Use only items table (single source of truth)
        $items = Item::orderByDesc('asset_id')->get();
        return view('nfc_inventory.index', compact('items'));
    }

    /**
     * Handle "Add Item" form: save to DB and append to Google Sheets.
     */
    public function store(Request $request, GoogleSheetService $sheetService)
    {
        // Validate incoming form
        $data = $request->validate([
            'uid'           => ['nullable', 'string', 'max:191'],
            'asset_id'      => ['required', 'string', 'max:191'],
            'name'          => ['nullable', 'string', 'max:191'],
            'detail'        => ['nullable', 'string'],
            'accessories'   => ['nullable', 'string'],
            'type_id'       => ['nullable', 'string', 'max:191'],
            'serial_no'     => ['nullable', 'string', 'max:191'],
            'status'        => ['nullable', 'string'],
            'purchase_date' => ['nullable', 'date'],
            'remarks'       => ['nullable', 'string', 'max:191'],
        ]);

        // Normalize status to match the Sheet dropdown values
        $data['status'] = $this->normalizeStatus($data['status'] ?? null);

        // Format purchase_date for DB (Y-m-d)
        $dbDate = null;
        if (!empty($data['purchase_date'])) {
            try {
                $dbDate = Carbon::parse($data['purchase_date'])->format('Y-m-d');
            } catch (\Throwable $e) {
                $dbDate = null;
            }
        }

        // Save to DB (upsert by asset_id)
        $item = Item::updateOrCreate(
            ['asset_id' => $data['asset_id']],
            [
                'uid'           => $data['uid'] ?? null,
                'name'          => $data['name'] ?? null,
                'detail'        => $data['detail'] ?? null,
                'accessories'   => $data['accessories'] ?? null,
                'type_id'       => $data['type_id'] ?? null,
                'serial_no'     => $data['serial_no'] ?? null,
                'status'        => $data['status'] ?: 'Available',
                'purchase_date' => $dbDate,
                'remarks'       => $data['remarks'] ?? null,
            ]
        );

        // Prepare values for Google Sheets (date as d/m/Y)
        $sheetDate = '';
        if (!empty($dbDate)) {
            try {
                $sheetDate = Carbon::parse($dbDate)->format('d/m/Y');
            } catch (\Throwable $e) {
                $sheetDate = '';
            }
        }

        // Append to Google Sheets
        // (If you later implement an "upsert" in GoogleSheetService, you can swap this to update-in-place.)
        $sheetService->appendRow([
            $item->uid ?? '',
            $item->asset_id ?? '',
            $item->name ?? '',
            $item->detail ?? '',
            $item->accessories ?? '',
            $item->type_id ?? '',
            $item->serial_no ?? '',
            $item->status ?? '',
            $sheetDate,
            $item->remarks ?? '',
        ]);

        return redirect()->route('nfc.inventory')->with('success', 'Item saved successfully.');
    }

    /**
     * Import Items from Google Sheets (published as CSV).
     * Clears old items and replaces with sheet data.
     */
    public function importFromGoogleSheet()
    {
        $url = config('services.google.sheet_csv_url');

        if (empty($url)) {
            return back()->with('error', 'Google Sheet CSV URL is not configured.');
        }

        try {
            $response = Http::timeout(20)->get($url);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to reach Google Sheets: ' . $e->getMessage());
        }

        if ($response->failed()) {
            return back()->with('error', 'Failed to fetch Google Sheet (HTTP ' . $response->status() . ').');
        }

        $csvRaw = $response->body();
        $rows = array_map('str_getcsv', preg_split("/\r\n|\n|\r/", $csvRaw));
        if (count($rows) < 2) {
            return back()->with('error', 'CSV appears to have no data rows.');
        }

        // Normalize headers
        $headers = array_map(fn($h) => strtolower(trim((string)$h)), array_shift($rows));

        // Map sheet headers → DB fields
        $mapping = [
            'uid'           => 'uid',
            'asset_id'      => 'asset_id',
            'name'          => 'name',
            'detail'        => 'detail',
            'accessories'   => 'accessories',
            'type_id'       => 'type_id',
            'serial_no'     => 'serial_no',
            'status'        => 'status',
            'purchase_date' => 'purchase_date',
            'remarks'       => 'remarks',
        ];

        // Clear old items
        Item::truncate();

        $inserted = 0;
        foreach ($rows as $row) {
            if (!is_array($row) || count(array_filter($row)) === 0) continue;
            if (count($row) < count($headers)) $row = array_pad($row, count($headers), null);

            $rowData = array_combine($headers, $row);

            // Build DB data
            $data = [];
            foreach ($mapping as $sheetCol => $dbField) {
                $data[$dbField] = $rowData[$sheetCol] ?? null;
            }

            // Normalize status
            $data['status'] = $this->normalizeStatus($data['status'] ?? null);

            // Normalize purchase_date (d/m/Y in sheet → Y-m-d for DB)
            if (!empty($data['purchase_date'])) {
                try {
                    $dt = Carbon::createFromFormat('d/m/Y', trim($data['purchase_date']));
                    $data['purchase_date'] = $dt->format('Y-m-d');
                } catch (\Throwable $e) {
                    // try Y-m-d just in case
                    try {
                        $data['purchase_date'] = Carbon::parse($data['purchase_date'])->format('Y-m-d');
                    } catch (\Throwable $e2) {
                        $data['purchase_date'] = null;
                    }
                }
            }

            if (empty($data['asset_id'])) continue;
            if (empty($data['status'])) $data['status'] = 'Available';

            Item::create($data);
            $inserted++;
        }

        return redirect()->route('nfc.inventory')
            ->with('success', "Google Sheet import successful. Replaced table with {$inserted} items.");
    }

    /**
     * Delete a single item (DB + Google Sheets).
     */
    public function destroy($asset_id, GoogleSheetService $sheetService)
    {
        $item = Item::where('asset_id', $asset_id)->first();

        if ($item) {
            $item->delete();
            // If you implemented this method in the service, it will remove the row in Sheets too.
            $sheetService->deleteRowByAssetId($asset_id);
            return back()->with('success', "Item {$asset_id} deleted successfully.");
        }

        return back()->with('error', "Item {$asset_id} not found.");
    }

    /**
     * Map incoming status values to your official dropdown labels.
     */
    private function normalizeStatus($status)
    {
        if (!$status) return null;
        $map = [
            'available'     => 'Available',
            'borrowed'      => 'Borrowed',
            'under_repair'  => 'Under Repair',
            'stolen'        => 'Stolen',
            'missing_lost'  => 'Missing/Lost',
        ];
        return $map[strtolower($status)] ?? ucfirst($status);
    }
}
