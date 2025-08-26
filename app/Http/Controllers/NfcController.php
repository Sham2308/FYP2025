<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NfcScan;
use Illuminate\Validation\Rule;

class NfcController extends Controller
{
    /**
     * Save a scan coming from the device (with UID).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'uid'           => ['nullable','string','max:191'],
            'asset_id'      => ['nullable','string','max:191'],
            'name'          => ['nullable','string','max:191'],
            'detail'        => ['nullable','string'],
            'accessories'   => ['nullable','string'],
            'type_id'       => ['nullable','string','max:191'],
            'serial_no'     => ['nullable','string','max:191'],
            'location_id'   => ['nullable','string','max:191'],
            'purchase_date' => ['nullable','date'],
            'remarks'       => ['nullable','string'],
            'status'        => ['nullable', Rule::in(['good','bad'])],
        ]);

        $scan = NfcScan::create($data);

        return response()->json(['status' => 'success', 'data' => $scan], 201);
    }

    /**
     * Save a manual registration (without UID).
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'asset_id'      => ['nullable','string','max:191'],
            'name'          => ['nullable','string','max:191'],
            'detail'        => ['nullable','string'],
            'accessories'   => ['nullable','string'],
            'type_id'       => ['nullable','string','max:191'],
            'serial_no'     => ['nullable','string','max:191'],
            'location_id'   => ['nullable','string','max:191'],
            'purchase_date' => ['nullable','date'],
            'remarks'       => ['nullable','string'],
            'status'        => ['nullable', Rule::in(['good','bad'])],
        ]);

        // Ensure uid is NULL for manual register
        $data['uid'] = null;

        $scan = NfcScan::create($data);

        return response()->json(['status' => 'registered', 'data' => $scan], 201);
    }

    /**
     * Delete by UID (all rows with that UID).
     */
    public function delete($uid)
    {
        $deleted = NfcScan::where('uid', $uid)->delete();

        return $deleted > 0
            ? response()->json(['status' => 'deleted', 'uid' => $uid, 'rows' => $deleted], 200)
            : response()->json(['status' => 'not_found', 'uid' => $uid], 404);
    }
}
