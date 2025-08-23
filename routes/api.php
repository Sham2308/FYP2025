<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NfcController;
use App\Http\Controllers\ScanRequestController;

// Scans
Route::post('/nfc-scan',     [NfcController::class, 'store']);
Route::post('/nfc-register', [NfcController::class, 'register']);
Route::delete('/nfc-delete/{uid}', [NfcController::class, 'delete']);

// Scan flow
Route::post('/scan-request',       [ScanRequestController::class, 'create']);
Route::get('/scan-next',           [ScanRequestController::class, 'next']);
Route::post('/scan-complete/{id}', [ScanRequestController::class, 'complete']);
Route::get('/scan-result/{id}',    [ScanRequestController::class, 'result']);
