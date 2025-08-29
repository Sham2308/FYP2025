<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Item;
use Carbon\Carbon;

// other existing routes...
Route::post('/nfc-scan', [App\Http\Controllers\NfcController::class, 'store']);
Route::post('/nfc-register', [App\Http\Controllers\NfcController::class, 'register']);
Route::delete('/nfc-delete/{uid}', [App\Http\Controllers\NfcController::class, 'delete']);

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
