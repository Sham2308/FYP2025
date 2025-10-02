<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;

use App\Models\Item;
use Carbon\Carbon;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\BorrowController;

// other existing routes...
Route::post('/nfc-scan', [App\Http\Controllers\NfcController::class, 'store']);
Route::post('/nfc-register', [App\Http\Controllers\NfcController::class, 'register']);
Route::delete('/nfc-delete/{uid}', [App\Http\Controllers\NfcController::class, 'delete']);

// API endpoints for Arduino (legacy RegisterController methods)
Route::post('/start-scan', [RegisterController::class, 'startScan']);
Route::get('/get-uid', [RegisterController::class, 'getUID']);

Route::get('/get-student-name', [BorrowController::class, 'getStudentName']);
Route::get('/borrow/user/{cardUid}', [BorrowController::class, 'getUserByUid']);

// ✅ Register item (still kept)
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

// ======================
// 🔹 Borrow Flow
// ======================

// Tell ESP32 when to scan (card or sticker)
Route::get('/scan-next', function () {
    return response(Cache::get('nfc_scan_ready', 'idle'), 200); // ✅ get() not pull()
});

// ESP32 sends UID here
Route::post('/register-uid', function (Request $request) {
    $uid = $request->input('uid');
    if (!$uid) {
        return response()->json(['error' => 'UID missing'], 400);
    }

    Cache::put('last_nfc_uid', $uid, now()->addSeconds(30));
    return response()->json(['message' => 'UID received', 'uid' => $uid]);
});

// Frontend fetches UID after scan
Route::get('/read-uid', function () {
    return response()->json(['uid' => Cache::get('last_nfc_uid')]);
});

// Frontend requests ESP32 to start scanning (type: card/sticker)
Route::post('/request-scan', function (Request $request) {
    $type = $request->input('type', 'card'); // default card
    Cache::put('nfc_scan_ready', $type, now()->addSeconds(20));
    return response()->json(['message' => "Scan ($type) requested"]);
});

// ======================
// 🔹 Register Flow (separate from borrow)
// ======================

// Tell ESP32 when to scan for registration
Route::get('/register-scan-next', function () {
    return response(Cache::get('register_scan_ready', 'idle'), 200); // ✅ get() not pull()
});

// Browser requests ESP32 to start register scan
Route::post('/request-register-scan', function (Request $request) {
    Cache::put('register_scan_ready', 'card', now()->addSeconds(20));
    return response()->json(['message' => "Register scan requested"]);
});

// ESP32 sends UID for register flow
Route::post('/register-register-uid', function (Request $request) {
    $uid = $request->input('uid');
    \Log::info("📌 Register UID received: " . $uid); // log to storage/logs/laravel.log
    if (!$uid) {
        return response()->json(['error' => 'UID missing'], 400);
    }
    Cache::put('last_register_uid', $uid, now()->addSeconds(30));
    return response()->json(['message' => 'Register UID received', 'uid' => $uid]);
});


// Browser fetches UID after register scan
Route::get('/read-register-uid', function () {
    return response()->json(['uid' => Cache::get('last_register_uid')]);
});
