<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;

use App\Models\Item;
use Carbon\Carbon;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\BorrowController;
use App\Http\Controllers\NfcController;

// ────────────────────────────────
// ✅ Existing NFC / Google Sheet Routes
// ────────────────────────────────
Route::post('/nfc-scan', [NfcController::class, 'store']);
Route::post('/nfc-register', [NfcController::class, 'register']);
Route::delete('/nfc-delete/{uid}', [NfcController::class, 'delete']);

// ────────────────────────────────
// ✅ Borrow / Student Lookup
// ────────────────────────────────
Route::get('/get-student-name', [BorrowController::class, 'getStudentName']);

Route::get('/borrow/user/{cardUid}', [BorrowController::class, 'getUserByUid'])->name('borrow.getUser');
Route::get('/borrow/fetch/{uid}', [BorrowController::class, 'fetchItem'])->name('borrow.fetch');



// ────────────────────────────────
// ✅ Item Registration (keep existing)
// ────────────────────────────────
Route::post('/items/register', function (Request $request) {
    $data = $request->only([
        'uid','asset_id','name','detail','accessories',
        'type_id','serial_no','status','purchase_date','remarks'
    ]);

    if (!empty($data['purchase_date'])) {
        try {
            $data['purchase_date'] = Carbon::parse($data['purchase_date'])->format('Y-m-d');
        } catch (\Exception $e) {
            $data['purchase_date'] = null;
        }
    }

    Item::updateOrCreate(['asset_id' => $data['asset_id']], $data);
    return response("Item saved successfully");
});

// ────────────────────────────────
// ✅ FULL RESTORE of Project 1 SCANNING FLOW
// ────────────────────────────────

// 🔹 Borrow Flow
Route::get('/scan-next', function () {
    return response(Cache::get('nfc_scan_ready', 'idle'), 200); // “card”, “sticker”, or “idle”
});

Route::post('/register-uid', function (Request $request) {
    $uid = $request->input('uid');
    if (!$uid) return response()->json(['error' => 'UID missing'], 400);

    Cache::put('last_nfc_uid', $uid, now()->addSeconds(30));
    return response()->json(['message' => 'UID received', 'uid' => $uid]);
});

Route::get('/read-uid', function () {
    return response()->json(['uid' => Cache::get('last_nfc_uid')]);
});

Route::post('/request-scan', function (Request $request) {
    $type = $request->input('type', 'card');
    Cache::put('nfc_scan_ready', $type, now()->addSeconds(20));
    return response()->json(['message' => "Scan ($type) requested"]);
});

// 🔹 Register Flow (fixed for Project 2)
Route::get('/register-scan-next', function () {
    $value = Cache::get('register_scan_ready', 'idle');
    \Log::info("🔍 [register-scan-next] Current value: " . $value);
    return response($value, 200);
});

Route::post('/request-register-scan', function (Request $request) {
    // Always refresh cache for 25 seconds
    Cache::put('register_scan_ready', 'card', now()->addSeconds(25));
    \Log::info("✅ [request-register-scan] Key stored = card");
    return response()->json(['message' => "Register scan requested"]);
});

Route::post('/register-register-uid', function (Request $request) {
    $uid = $request->input('uid');
    if (!$uid) {
        \Log::warning("⚠️ register-register-uid missing UID");
        return response()->json(['error' => 'UID missing'], 400);
    }

    Cache::put('last_register_uid', $uid, now()->addSeconds(60));
    \Log::info("📌 Register UID received: " . $uid);

    return response()->json(['message' => 'Register UID received', 'uid' => $uid]);
});

Route::get('/read-register-uid', function () {
    $uid = Cache::get('last_register_uid');
    \Log::info("📖 [read-register-uid] returning uid = " . ($uid ?? 'null'));
    return response()->json(['uid' => $uid]);
});
