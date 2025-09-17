<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleSheetService;
use Illuminate\Support\Facades\Cache;

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
        $uid = Cache::get('scanned_uid'); // UID set by Arduino
        return view('register.index', compact('uid'));
    }

    // Save form to Google Sheets
    public function store(Request $request)
    {
        $request->validate([
            'uid' => 'required',
            'name' => 'required',
            'student_id' => 'required',
        ]);

        // Append user row into "Users" tab
        $this->sheets->appendRow([
            $request->uid,
            $request->student_id,
            $request->name,
        ], config('services.google.users_range'));

        return redirect('/nfc-inventory')->with('success', 'User registered successfully!');
    }

    // Called when user presses "Scan Card" button (browser)
    public function startScan()
    {
        Cache::put('scan_requested', true, 30); // valid for 30 seconds
        return response()->json(['status' => 'ready']);
    }

    // Arduino polls this to know if scan requested
    public function scanNext()
    {
        if (Cache::get('scan_requested')) {
            return response()->json(['status' => 'ready']);
        }
        return response()->json(['status' => 'idle']);
    }

    // Arduino sends UID here after scanning
    public function captureUID(Request $request)
    {
        // Force decode JSON if Laravel didn’t parse automatically
        $data = $request->json()->all();
        $uid = $data['uid'] ?? $request->input('uid');

        if ($uid) {
            // Store UID in cache (since API routes don’t share session easily)
            Cache::put('scanned_uid', $uid, now()->addMinutes(5));

            return response()->json(['status' => 'ok', 'uid' => $uid]);
        }

        return response()->json(['status' => 'error', 'message' => 'UID missing'], 400);
    }

    // Browser polls this to auto-fill UID field
    public function getUID()
    {
        $uid = Cache::get('scanned_uid');

        if ($uid) {
            return response()->json(['uid' => $uid]);
        }

        return response()->json(['uid' => null], 404);
    }
}
