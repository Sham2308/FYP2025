<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NfcScan;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Services\GoogleSheetService;
use Carbon\Carbon;

class NfcController extends Controller
{
    // Device save (UID comes from scanner)
    public function store(Request $request, GoogleSheetService $sheetService)
    {
        $data = $request->validate([
            'uid'           => ['required','string','max:191'],
            'asset_id'      => ['nullable','string','max:191'],
            'name'          => ['nullable','string','max:191'],
            'detail'        => ['nullable','string'],
            'accessories'   => ['nullable','string'],
            'type_id'       => ['nullable','string','max:191'],
            'serial_no'     => ['nullable','string','max:191'],
            'purchase_date' => ['nullable','date'],
            'remarks'       => ['nullable','string'],
            'status'        => ['nullable','string'],
        ]);

        // ✅ Normalize Status
        $data['status'] = $this->normalizeStatus($data['status']);

        // ✅ Format purchase_date (dd/mm/yyyy for Sheets)
        if (!empty($data['purchase_date'])) {
            try {
                $dt = Carbon::parse($data['purchase_date']);
                $data['purchase_date'] = $dt->format('d/m/Y');
            } catch (\Exception $e) {
                $data['purchase_date'] = null;
            }
        }

        try {
            $scan = NfcScan::create($data);

            // ✅ Also push to Google Sheets
            $sheetService->appendRow([
                $data['uid'] ?? '',
                $data['asset_id'] ?? '',
                $data['name'] ?? '',
                $data['detail'] ?? '',
                $data['accessories'] ?? '',
                $data['type_id'] ?? '',
                $data['serial_no'] ?? '',
                $data['status'] ?? '',
                $data['purchase_date'] ?? '',
                $data['remarks'] ?? '',
            ],'Items!A:J');

            return response()->json(['status' => 'success', 'data' => $scan], 201);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json(['status' => 'conflict', 'message' => 'UID already exists'], 409);
            }
            throw $e;
        }
    }

    // Manual register
    public function register(Request $request, GoogleSheetService $sheetService)
    {
        $data = $request->validate([
            'uid'           => ['required','string','max:191'],
            'asset_id'      => ['nullable','string','max:191'],
            'name'          => ['nullable','string','max:191'],
            'detail'        => ['nullable','string'],
            'accessories'   => ['nullable','string'],
            'type_id'       => ['nullable','string','max:191'],
            'serial_no'     => ['nullable','string','max:191'],
            'purchase_date' => ['nullable','date'],
            'remarks'       => ['nullable','string'],
            'status'        => ['nullable','string'],
        ]);

        // ✅ Normalize Status
        $data['status'] = $this->normalizeStatus($data['status']);

        // ✅ Format purchase_date
        if (!empty($data['purchase_date'])) {
            try {
                $dt = Carbon::parse($data['purchase_date']);
                $data['purchase_date'] = $dt->format('d/m/Y');
            } catch (\Exception $e) {
                $data['purchase_date'] = null;
            }
        }

        try {
            $scan = NfcScan::create($data);

            // ✅ Push to Google Sheets
            $sheetService->appendRow([
                $data['uid'] ?? '',
                $data['asset_id'] ?? '',
                $data['name'] ?? '',
                $data['detail'] ?? '',
                $data['accessories'] ?? '',
                $data['type_id'] ?? '',
                $data['serial_no'] ?? '',
                $data['status'] ?? '',
                $data['purchase_date'] ?? '',
                $data['remarks'] ?? '',
            ],'Items!A:J');

            return response()->json(['status' => 'registered', 'data' => $scan], 201);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json(['status' => 'conflict', 'message' => 'UID already exists'], 409);
            }
            throw $e;
        }
    }

    // Delete all rows for a UID
    public function delete($uid)
    {
        $deleted = NfcScan::where('uid', $uid)->delete();
        return $deleted > 0
            ? response()->json(['status' => 'deleted', 'uid' => $uid, 'rows' => $deleted], 200)
            : response()->json(['status' => 'not_found', 'uid' => $uid], 404);
    }

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

    public function index()
    {
         // Fetch from nfc_scans but name it $items to match the Blade
        $items = \App\Models\NfcScan::orderByDesc('created_at')->paginate(10);

        // Your view path per your structure: resources/views/nfc_inventory/index.blade.php
        return view('nfc_inventory.index', compact('items'));
    }


}
