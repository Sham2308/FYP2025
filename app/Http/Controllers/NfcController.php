<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NfcScan;

class NfcController extends Controller
{
    public function store(Request $request)
    {
        $scan = NfcScan::create([
            'uid'        => $request->uid,
            'student_id' => $request->student_id,
            'user_name'  => $request->user_name,
            'item_id'    => $request->item_id,
            'item_name'  => $request->item_name,
            'status'     => $request->status,
        ]);

        return response()->json(['status' => 'success', 'data' => $scan], 201);
    }

    public function register(Request $request)
    {
        $scan = NfcScan::create([
            'uid'        => null,
            'student_id' => $request->student_id,
            'user_name'  => $request->user_name,
            'item_id'    => $request->item_id,
            'item_name'  => $request->item_name,
            'status'     => $request->status,
        ]);

        return response()->json(['status' => 'registered', 'data' => $scan], 201);
    }

    public function delete($uid)
    {
        $deleted = NfcScan::where('uid', $uid)->delete();

        return $deleted > 0
            ? response()->json(['status' => 'deleted', 'uid' => $uid, 'rows' => $deleted], 200)
            : response()->json(['status' => 'not_found', 'uid' => $uid], 404);
    }
}

