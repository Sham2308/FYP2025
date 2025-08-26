<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ScanRequestController extends Controller
{
    // How fresh a request must be to be picked by the device (seconds)
    private int $ttlSeconds = 20;

    public function create()
    {
        $id = DB::table('scan_requests')->insertGetId([
            'status'     => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['request_id' => $id, 'status' => 'pending'], 201);
    }

    public function next()
    {
        $freshAfter = Carbon::now()->subSeconds($this->ttlSeconds);

        // Only pick truly fresh pending requests
        $req = DB::table('scan_requests')
            ->where('status', 'pending')
            ->where('created_at', '>=', $freshAfter)
            ->orderBy('id')
            ->first();

        if (!$req) {
            return response()->json(['request' => null]);
        }

        DB::table('scan_requests')->where('id', $req->id)->update([
            'status'     => 'in_progress',
            'updated_at' => now(),
        ]);

        return response()->json(['request' => ['id' => $req->id]]);
    }

    public function complete(Request $request, $id)
    {
        $uid = $request->uid;

        DB::table('scan_requests')->where('id', $id)->update([
            'status'     => 'done',
            'result'     => json_encode(['uid' => $uid]),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => 'done', 'result' => ['uid' => $uid]]);
    }

    public function result($id)
    {
        $req = DB::table('scan_requests')->where('id', $id)->first();
        if (!$req) return response()->json(['error' => 'not_found'], 404);

        return response()->json([
            'id'     => $req->id,
            'status' => $req->status,
            'result' => $req->result ? json_decode($req->result, true) : null,
        ]);
    }
}
