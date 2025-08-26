<?php

namespace App\Http\Controllers;

use App\Models\NfcScan;

class InventoryController extends Controller
{
    /**
     * Show the Inventory dashboard with a single "Latest Scans" table.
     * Columns match the NFC scans page.
     */
    public function index()
    {
        // Fetch the latest 50 scans (adjust the limit if you want more/less)
        $latest = NfcScan::orderByDesc('id')->limit(50)->get();

        // Only pass what the view needs
        return view('nfc_inventory.index', compact('latest'));
    }
}
