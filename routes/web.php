<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NfcScanController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\BorrowController;

Route::get('/nfc-inventory', [InventoryController::class, 'index'])->name('nfc.inventory');

// New: Import from Google Sheets
Route::get('/items/import/google', [InventoryController::class, 'importFromGoogleSheet'])->name('items.import.google');

Route::get('/nfc-scans', [NfcScanController::class, 'index'])->name('nfc_scans.index');

// NEW: Borrow page
Route::get('/borrow', [BorrowController::class, 'index'])->name('borrow.index');

// NEW: Borrow actions
Route::post('/borrow/store', [BorrowController::class, 'store'])->name('borrow.store');
Route::post('/borrow/return/{uid}', [BorrowController::class, 'returnItem'])->name('borrow.return');

Route::get('/', function () {
    return view('welcome');
});
