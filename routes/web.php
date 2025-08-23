<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NfcScanController;
use App\Http\Controllers\InventoryController;

Route::get('/nfc-inventory', [InventoryController::class, 'index'])->name('nfc.inventory');


Route::get('/nfc-scans', [NfcScanController::class, 'index'])->name('nfc_scans.index');

Route::get('/', function () {
    return view('welcome');
});
