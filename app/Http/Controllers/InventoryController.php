<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NfcScan;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index()
    {
        // Overall counters
        $total   = NfcScan::count();
        $goodCnt = NfcScan::where('status','good')->count();
        $badCnt  = NfcScan::where('status','bad')->count();

        // Per-item summary (latest name per item_id, counts by status)
        $items = NfcScan::select([
                'item_id',
                DB::raw("MAX(item_name) as item_name"),
                DB::raw("SUM(CASE WHEN status='good' THEN 1 ELSE 0 END) as good_count"),
                DB::raw("SUM(CASE WHEN status='bad' THEN 1 ELSE 0 END) as bad_count"),
                DB::raw("COUNT(*) as total_count"),
            ])
            ->groupBy('item_id')
            ->orderBy('item_id')
            ->get();

        // Latest 20 scans
        $latest = NfcScan::orderByDesc('id')->limit(20)->get();

        return view('nfc_inventory.index', compact('total','goodCnt','badCnt','items','latest'));
    }
}
