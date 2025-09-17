<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BorrowController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ItemImportController;

// ── Home / Welcome ─────────────────────────────────────────────────────
Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->role === 'admin'
            ? redirect()->route('nfc.inventory')   // admins → inventory
            : redirect()->route('borrow.index');   // non-admins → borrow
    }
    return view('welcome'); // guests see welcome first
});

// ── Public pages ───────────────────────────────────────────────────────
Route::get('/borrow', [BorrowController::class, 'index'])->name('borrow.index');
Route::get('/history', [HistoryController::class, 'index'])->name('history.index');

// (Optional) simple register page if you’re using a custom controller
Route::get('/register', [RegisterController::class, 'index'])->name('register-user');
Route::post('/register', [RegisterController::class, 'store'])->name('register-user.store');

// ── Auth-only (any logged-in user) ─────────────────────────────────────
Route::middleware('auth')->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Borrow actions
    Route::post('/borrow', [BorrowController::class, 'store'])->name('borrow.store'); // POST /borrow
    Route::post('/borrow/return/{uid}', [BorrowController::class, 'returnItem'])->name('borrow.return');
    Route::delete('/borrow/{id}', [BorrowController::class, 'destroy'])->name('borrow.destroy');
    Route::get('/borrow/fetch/{uid}', [BorrowController::class, 'fetchItem'])->name('borrow.fetch');

    // History: import from Google Sheets (if needed)
    Route::post('/history/import/google', [HistoryController::class, 'importFromGoogleSheet'])
        ->name('history.import.google');
});

// ── Admin-only ─────────────────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->group(function () {
    // Inventory dashboard
    Route::get('/nfc/inventory', [InventoryController::class, 'index'])->name('nfc.inventory');
    // (If your app still links to /nfc-inventory, keep a redirect)
    Route::redirect('/nfc-inventory', '/nfc/inventory');

    // Import items from Google Sheets (allow GET for form & POST for submit)
    Route::match(['GET', 'POST'], '/items/import/google', [ItemImportController::class, 'google'])
        ->name('items.import.google');

    // Items CRUD
    Route::post('/items', [InventoryController::class, 'store'])->name('items.store');
    Route::delete('/items/{asset_id}', [InventoryController::class, 'destroy'])->name('items.destroy');
});

require __DIR__ . '/auth.php';
