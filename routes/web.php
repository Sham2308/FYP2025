<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BorrowController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ItemImportController;
use App\Http\Controllers\TechnicalDashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ItemStatusController; // ← NEW (for mark-available)

// Notifications
use App\Notifications\GenericDatabaseNotification;

// ── Home / Welcome ─────────────────────────────────────────────────────
Route::get('/', function () {
    if (auth()->check()) {
        return match (auth()->user()->role) {
            'admin'     => redirect()->route('nfc.inventory'),        // admins → inventory
            'technical' => redirect()->route('technical.dashboard'),  // technical → technical dashboard
            default     => redirect()->route('borrow.index'),         // others → borrow
        };
    }
    return view('welcome'); // guests see welcome first
});

// (Public) keep old link working for everyone
Route::redirect('/nfc-inventory', '/nfc/inventory');

// ── Public pages ───────────────────────────────────────────────────────
Route::get('/borrow', [BorrowController::class, 'index'])->name('borrow.index');
Route::get('/history', [HistoryController::class, 'index'])->name('history.index');

// ── Auth-only (any logged-in user) ─────────────────────────────────────
Route::middleware('auth')->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Borrow actions
    Route::post('/borrow', [BorrowController::class, 'store'])->name('borrow.store');
    Route::post('/borrow/return/{uid}', [BorrowController::class, 'returnItem'])->name('borrow.return');
    Route::delete('/borrow/{id}', [BorrowController::class, 'destroy'])->name('borrow.destroy');
    Route::get('/borrow/fetch/{uid}', [BorrowController::class, 'fetchItem'])->name('borrow.fetch');

    // History: import from Google Sheets
    Route::post('/history/import/google', [HistoryController::class, 'importFromGoogleSheet'])
        ->name('history.import.google');

    // ── Notifications (KEEP these) ─────────────────────────────────────
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])
        ->name('notifications.unreadCount');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])
        ->name('notifications.markAllRead');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markOneRead'])
        ->name('notifications.markOneRead');

    // ── TEMP: smoke-test a notification (REMOVE later) ─────────────────
    Route::get('/notify-test', function () {
        auth()->user()->notify(
            new GenericDatabaseNotification('Test notice', 'Hello from the bell!', url('/'))
        );
        return 'Sent';
    })->name('notify.test');
});

// ── Technical-only ─────────────────────────────────────────────────────
// Requires 'role' middleware registered in bootstrap/app.php
Route::middleware(['auth', 'role:technical'])->group(function () {
    Route::get('/technical', [TechnicalDashboardController::class, 'index'])
        ->name('technical.dashboard');

    // Mark item as AVAILABLE (repair finished) — button from "Under Repair" list
    Route::patch('/items/{asset_id}/mark-available', [ItemStatusController::class, 'markAvailable'])
        ->where('asset_id', '[A-Za-z0-9\-_]+')
        ->name('items.markAvailable');
});

// ── Admin-only ─────────────────────────────────────────────────────────
// CHANGED: use 'role:admin' instead of 'admin'
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Inventory dashboard
    Route::get('/nfc/inventory', [InventoryController::class, 'index'])->name('nfc.inventory');

    // Import items from Google Sheets (POST only)
    Route::post('/items/import/google', [ItemImportController::class, 'importFromGoogle'])
        ->name('items.import.google');

    // Items CRUD (asset_id is your string PK)
    Route::post('/items', [InventoryController::class, 'store'])->name('items.store');
    Route::delete('/items/{asset_id}', [InventoryController::class, 'destroy'])
        ->where('asset_id', '[A-Za-z0-9\-_]+')
        ->name('items.destroy');

    // Mark item as UNDER REPAIR (admin triggers this)
    Route::patch('/items/{asset_id}/under-repair', [InventoryController::class, 'markUnderRepair'])
        ->where('asset_id', '[A-Za-z0-9\-_]+')
        ->name('items.markUnderRepair');
});

// Breeze / Fortify authentication routes
require __DIR__.'/auth.php';

// Alias so old links route('register-user') still work
Route::get('/register-user', fn () => redirect()->route('register'))
    ->name('register-user');

// Breeze/Fortify default redirect → send to our role-based home instead
Route::get('/dashboard', fn () => redirect('/'))
    ->middleware('auth')
    ->name('dashboard');
