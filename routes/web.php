<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BorrowController;
use App\Http\Controllers\InventoryController;

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->role === 'admin'
            ? redirect()->route('nfc.inventory')   // admins → inventory
            : redirect()->route('borrow.index');   // non-admins → borrow
    }
    return view('welcome'); // guests see welcome first
});

// ── Public Borrow index (guests can view) ───────────────────────────────
Route::get('/borrow', [BorrowController::class, 'index'])->name('borrow.index');

// ── Auth (available to any logged-in user) ──────────────────────────────
Route::middleware('auth')->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Borrow actions (must be logged in)
    Route::post('/borrow', [BorrowController::class, 'store'])->name('borrow.store');
    Route::post('/borrow/return/{uid}', [BorrowController::class, 'returnItem'])->name('borrow.return');
    Route::delete('/borrow/{id}', [BorrowController::class, 'destroy'])->name('borrow.destroy');
    Route::get('/borrow/fetch/{uid}', [BorrowController::class, 'fetchItem'])->name('borrow.fetch');
});

// ── Admin-only ──────────────────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->group(function () {
    // Inventory (admin dashboard)
    Route::get('/nfc/inventory', [InventoryController::class, 'index'])
        ->name('nfc.inventory');

    // Import & items
    Route::match(['GET','POST'], '/items/import/google', [\App\Http\Controllers\ItemImportController::class, 'google'])
        ->name('items.import.google');
    Route::post('/items', function () {
        return back()->with('status', 'Items stored (placeholder).');
    })->name('items.store');
});

require __DIR__ . '/auth.php';
