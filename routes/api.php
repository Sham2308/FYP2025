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

// API endpoints for Arduino
Route::post('/start-scan', [RegisterController::class, 'startScan']);
// Route::get('/scan-next', [RegisterController::class, 'scanNext']);
// Route::post('/register-uid', [RegisterController::class, 'captureUID']);
Route::get('/get-uid', [RegisterController::class, 'getUID']);

Route::get('/get-student-name', [BorrowController::class, 'getStudentName']);
Route::get('/borrow/user/{cardUid}', [BorrowController::class, 'getUserByUid']);

// ✅ New unified route
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

// Tell ESP32 when to scan (card or sticker)
Route::get('/scan-next', function () {
    $type = Cache::pull('nfc_scan_ready', false);
    if ($type) {
        return response($type, 200); // either "card" or "sticker"
    }
    return response('idle', 200);
});

// ESP32 sends UID here
Route::post('/register-uid', function (Request $request) {
    $uid = $request->input('uid');
    if (!$uid) {
        return response()->json(['error' => 'UID missing'], 400);
    }

    // Store UID temporarily for frontend
    Cache::put('last_nfc_uid', $uid, now()->addSeconds(30));

    return response()->json(['message' => 'UID received', 'uid' => $uid]);
});

// Frontend fetches UID after scan
Route::get('/read-uid', function () {
    $uid = Cache::pull('last_nfc_uid');
    if ($uid) {
        return response()->json(['uid' => $uid]);
    }
    return response()->json(['uid' => null]);
});

// Frontend requests ESP32 to start scanning (type: card/sticker)
Route::post('/request-scan', function (Request $request) {
    $type = $request->input('type', 'card'); // default to card
    Cache::put('nfc_scan_ready', $type, now()->addSeconds(20));
    return response()->json(['message' => "Scan ($type) requested"]);
});
