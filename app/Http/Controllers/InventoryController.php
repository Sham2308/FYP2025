<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NfcScan;
use App\Models\Item;
use Illuminate\Support\Facades\Http;

class InventoryController extends Controller
{
    /**
     * Show the Inventory dashboard.
     * Displays latest scans and latest items synced from Google Sheets.
     */
    public function index()
    {
        // Existing: latest scans (keep as-is)
        $latest = NfcScan::orderByDesc('id')->limit(50)->get();

        // Items from DB (populated via Google Sheets import)
        $items = Item::orderByDesc('id')->limit(50)->get();

        return view('nfc_inventory.index', compact('latest', 'items'));
    }

    /**
     * Import Items from Google Sheets (published as CSV).
     */
    public function importFromGoogleSheet()
    {
        $url = env('GOOGLE_SHEET_CSV_URL'); // Google Sheet published as CSV
        $response = Http::get($url);

        if ($response->failed()) {
            return back()->with('error', 'Failed to fetch Google Sheet.');
        }

        $csvData = array_map('str_getcsv', explode("\n", $response->body()));
        $headers = array_map('trim', array_shift($csvData)); // first row as headers

        foreach ($csvData as $row) {
            if (count($row) < count($headers)) continue;
            $rowData = array_combine($headers, $row);

            // Upsert item by UID
            Item::updateOrCreate(
                ['uid' => $rowData['uid']],
                $rowData
            );
        }

        return redirect()->route('nfc.inventory')->with('success', 'Google Sheet imported successfully!');
    }
}
