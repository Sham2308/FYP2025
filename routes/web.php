<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\BorrowController;

Route::get('/nfc-inventory', [InventoryController::class, 'index'])->name('nfc.inventory');

// Import from Google Sheets
Route::get('/items/import/google', [InventoryController::class, 'importFromGoogleSheet'])->name('items.import.google');

// Add item
Route::post('/items', [InventoryController::class, 'store'])->name('items.store');

// Delete item (by asset_id)
Route::delete('/items/{asset_id}', [InventoryController::class, 'destroy'])->name('items.destroy');


// Borrow page
Route::get('/borrow', [BorrowController::class, 'index'])->name('borrow.index');
Route::post('/borrow/store', [BorrowController::class, 'store'])->name('borrow.store');
Route::post('/borrow/return/{uid}', [BorrowController::class, 'returnItem'])->name('borrow.return');
Route::get('/borrow/fetch/{uid}', [BorrowController::class, 'fetchItem'])->name('borrow.fetch');

// 🔹 New delete borrow route
Route::delete('/borrow/{id}', [BorrowController::class, 'destroy'])->name('borrow.destroy');

Route::get('/', function () {
    return view('welcome');
});
