<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NfcScan;

class NfcScanController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'uid'  => 'required|string',
            'type' => 'required|string',   // "user" or "item"
            'id'   => 'required|string',
            'name' => 'required|string',
        ]);

        $scan = NfcScan::create($data);

        return response()->json([
            'status' => 'success',
            'data'   => $scan
        ]);
    }
}
