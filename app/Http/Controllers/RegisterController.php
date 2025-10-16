<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleSheetService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    protected $sheets;

    public function __construct(GoogleSheetService $sheets)
    {
        $this->sheets = $sheets;
    }

    // Show the register form
    public function index()
    {
        Cache::forget('last_register_uid');
        $uid = Cache::get('last_register_uid');
        return view('register.index', compact('uid'));
    }

    // Save form to Google Sheets
    public function store(Request $request)
    {
        $request->validate([
            'uid' => 'required',
            'name' => 'required',
            'staff_id' => 'required',
        ]);

        try {
            Log::info("💾 [RegisterController@store] Save button clicked", $request->all());

            // ✅ Append to Google Sheets
            $this->sheets->appendRow([
                $request->uid,
                $request->staff_id,
                $request->name,
            ], config('services.google.users_range', 'Users!A:Z'));

            Log::info("✅ Successfully added user to Google Sheets");

            return redirect('/borrow')->with('success', 'Account registered successfully!');

        } catch (\Exception $e) {
            Log::error("❌ Failed to save user to Google Sheets: " . $e->getMessage());
            return back()->with('error', 'Failed to save user to Google Sheets. Check server logs for details.');
        }
    }

    // Browser requests ESP32 to start register scan
    public function startScan()
    {
        Cache::put('register_scan_ready', 'card', now()->addSeconds(20));
        Log::info("✅ [request-register-scan] Key stored = card");
        return response()->json(['status' => 'ready']);
    }

    // Arduino polls this endpoint (/api/register-scan-next)
    public function scanNext()
    {
        $status = Cache::get('register_scan_ready', 'idle');
        Log::info("🔍 [register-scan-next] Current value: {$status}");
        return response()->json(['status' => $status]);
    }

    // Arduino sends UID to Laravel (/api/register-register-uid)
    public function captureUID(Request $request)
    {
        $data = $request->json()->all();
        $uid  = $data['uid'] ?? $request->input('uid');

        if ($uid) {
            Cache::put('last_register_uid', $uid, now()->addMinutes(5));
            Log::info("📌 Register UID received: {$uid}");
            return response()->json(['status' => 'ok', 'uid' => $uid]);
        }

        return response()->json(['status' => 'error', 'message' => 'UID missing'], 400);
    }

    // Browser fetches UID after scan (/api/read-register-uid)
    public function getUID()
    {
        $uid = Cache::get('last_register_uid');
        Log::info("📖 [read-register-uid] returning uid = " . ($uid ?? 'null'));
        return $uid
            ? response()->json(['uid' => $uid])
            : response()->json(['uid' => null], 404);
    }
}
