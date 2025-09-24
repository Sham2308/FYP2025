<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use Illuminate\Support\Facades\Http;
use App\Services\GoogleSheetService;
use Carbon\Carbon;

// 🔔 imports for notifications + logging
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\GenericDatabaseNotification;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{
    /**
     * Show the Inventory dashboard.
     */
    public function index()
    {
        $items = Item::orderByDesc('asset_id')->get();
        return view('nfc_inventory.index', compact('items'));
    }

    /**
     * Handle "Add Item" form: save to DB and append to Google Sheets.
     */
    public function store(Request $request, GoogleSheetService $sheetService)
    {
        $data = $request->validate([
            'uid'           => ['nullable', 'string', 'max:191'],
            'asset_id'      => ['required', 'string', 'max:191'],
            'name'          => ['required', 'string', 'max:191'], // DB is NOT NULL
            'detail'        => ['nullable', 'string'],
            'accessories'   => ['nullable', 'string'],
            'type_id'       => ['nullable', 'string', 'max:191'],
            'serial_no'     => ['nullable', 'string', 'max:191'],
            'status'        => ['nullable', 'string'],
            'purchase_date' => ['nullable', 'date'],
            'remarks'       => ['nullable', 'string', 'max:191'],
        ]);

        // canonicalize status for DB (lowercase matches enum)
        $data['status'] = $this->normalizeStatus($data['status'] ?? null) ?? 'available';

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
                'name'          => $data['name'],
                'detail'        => $data['detail'] ?? null,
                'accessories'   => $data['accessories'] ?? null,
                'type_id'       => $data['type_id'] ?? null,
                'serial_no'     => $data['serial_no'] ?? null,
                'status'        => $data['status'],   // lowercase for DB enum
                'purchase_date' => $dbDate,
                'remarks'       => $data['remarks'] ?? null,
            ]
        );

        // Prepare values for Google Sheets (date as d/m/Y) + Title Case status for readability
        $sheetDate = '';
        if (!empty($dbDate)) {
            try {
                $sheetDate = Carbon::parse($dbDate)->format('d/m/Y');
            } catch (\Throwable $e) {
                $sheetDate = '';
            }
        }
        $sheetStatus = $this->titleCaseStatus($item->status);

        // Append to Google Sheets
        $sheetService->appendRow([
            $item->uid ?? '',
            $item->asset_id ?? '',
            $item->name ?? '',
            $item->detail ?? '',
            $item->accessories ?? '',
            $item->type_id ?? '',
            $item->serial_no ?? '',
            $sheetStatus, // Title Case for sheet
            $sheetDate,
            $item->remarks ?? '',
        ]);

        // 🔔 Notify admins & technicals about the new/updated item
        try {
            $targets = User::whereIn('role', ['admin', 'technical'])->get();
            $title   = 'New Inventory Item';
            $body    = 'Added: '.($item->name ?? 'Unnamed').' (Asset: '.($item->asset_id ?? '—').')';
            Notification::send($targets, new GenericDatabaseNotification(
                $title, $body, route('nfc.inventory')
            ));
        } catch (\Throwable $e) {
            Log::warning('Notify (item add) failed: '.$e->getMessage());
        }

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

            // Normalize status for DB (lowercase canonical)
            $data['status'] = $this->normalizeStatus($data['status'] ?? null) ?? 'available';

            // Normalize purchase_date (d/m/Y in sheet → Y-m-d for DB)
            if (!empty($data['purchase_date'])) {
                try {
                    $dt = Carbon::createFromFormat('d/m/Y', trim($data['purchase_date']));
                    $data['purchase_date'] = $dt->format('Y-m-d');
                } catch (\Throwable $e) {
                    // try a generic parse (Y-m-d or others)
                    try {
                        $data['purchase_date'] = Carbon::parse($data['purchase_date'])->format('Y-m-d');
                    } catch (\Throwable $e2) {
                        $data['purchase_date'] = null;
                    }
                }
            }

            if (empty($data['asset_id'])) continue;

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

            // If implemented, remove from Sheets too.
            try {
                $sheetService->deleteRowByAssetId($asset_id);
            } catch (\Throwable $e) {
                Log::warning('Sheet delete skipped/failed: '.$e->getMessage());
            }

            // 🔔 notify ADMINS only that the item was deleted
            try {
                $admins = User::where('role', 'admin')->get();
                Notification::send($admins, new GenericDatabaseNotification(
                    'Item Deleted',
                    "Deleted asset_id: {$asset_id}",
                    route('nfc.inventory')
                ));
            } catch (\Throwable $e) {
                Log::warning('Notify (item delete) failed: '.$e->getMessage());
            }

            return back()->with('success', "Item {$asset_id} deleted successfully.");
        }

        return back()->with('error', "Item {$asset_id} not found.");
    }

    /**
     * ADMIN action: mark an item as Under Repair and notify technicals.
     * Route: PATCH /items/{asset_id}/under-repair  → name: items.markUnderRepair
     */
    public function markUnderRepair(string $asset_id)
    {
        $item = Item::where('asset_id', $asset_id)->firstOrFail();

        // Only allow when currently available (case-insensitive)
        if (strtolower(trim((string) $item->status)) !== 'available') {
            return back()->with('error', 'Only available items can be marked as Under Repair.');
        }

        // Update DB to canonical lowercase
        $item->status = 'under repair';
        $item->save();

        // 🔔 Notify all technical users
        try {
            $techs = User::where('role', 'technical')->get();
            Notification::send($techs, new GenericDatabaseNotification(
                'Item Marked Under Repair',
                "Item {$item->asset_id} ({$item->name}) marked Under Repair.",
                route('technical.dashboard')
            ));
        } catch (\Throwable $e) {
            Log::warning('Notify (under repair) failed: '.$e->getMessage());
        }

        // ✅ Refresh admin inventory instead of redirecting to /technical (prevents 403)
        return redirect()
            ->route('nfc.inventory')
            ->with('success', 'Item marked as Under Repair.');
    }

    /**
     * Map incoming status values to canonical lowercase enum values for DB.
     */
    private function normalizeStatus($status)
    {
        if (!$status) return null;

        $s = strtolower(trim((string) $status));
        $s = str_replace(['_', '-'], ' ', $s);   // handle under_repair / under-repair
        $s = preg_replace('/\s+/', ' ', $s);

        // Normalize missing/lost variants
        if ($s === 'missing lost' || $s === 'missing/ lost' || $s === 'missing / lost') {
            return 'missing/lost';
        }
        if ($s === 'missing/lost') return 'missing/lost';

        // Map to canonical set
        switch ($s) {
            case 'available':     return 'available';
            case 'borrowed':      return 'borrowed';
            case 'retire':        return 'retire';
            case 'under repair':  return 'under repair';
            case 'stolen':        return 'stolen';
            default:
                if (strpos($s, 'missing') !== false && strpos($s, 'lost') !== false) {
                    return 'missing/lost';
                }
                return $s; // fallback: let DB enum enforce validity
        }
    }

    /**
     * For Sheets readability: convert canonical DB values to Title Case.
     */
    private function titleCaseStatus(string $status): string
    {
        if ($status === 'missing/lost') return 'Missing/Lost';
        return ucwords($status);
    }
}
