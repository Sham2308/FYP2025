<?php

namespace App\Http\Controllers;

use App\Models\NfcScan;

class NfcScanController extends Controller
{
    public function index()
    {
        // You’ll see newest first
        $scans = NfcScan::latest()->get();
        return view('nfc_scans.index', compact('scans'));
    }
}
