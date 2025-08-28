<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NfcScan;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

class NfcController extends Controller
{
    // Device save (UID comes from scanner)
    public function store(Request $request)
    {
        $data = $request->validate([
            'uid'           => ['required','string','max:191'],
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

        try {
            $scan = NfcScan::create($data);
            return response()->json(['status' => 'success', 'data' => $scan], 201);
        } catch (QueryException $e) {
            // Handle duplicate UID nicely (unique constraint)
            if ($e->getCode() === '23000') {
                return response()->json(['status' => 'conflict', 'message' => 'UID already exists'], 409);
            }
            throw $e;
        }
    }

    // Manual register (now requires UID typed by user)
    public function register(Request $request)
    {
        $data = $request->validate([
            'uid'           => ['required','string','max:191'],
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

        try {
            $scan = NfcScan::create($data);
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
}
