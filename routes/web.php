<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache; // for last-sync JSON

// Controllers
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BorrowController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ItemImportController;
use App\Http\Controllers\TechnicalDashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ItemStatusController;
// use App\Http\Controllers\ChatController; // ❌ removed
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PublicRegisterController;

// Notifications
use App\Notifications\GenericDatabaseNotification;

// ── Home / Welcome ─────────────────────────────────────────────────────
Route::get('/', function () {
    // Force logout if the user is not admin or technical
    if (auth()->check()) {
        if (in_array(auth()->user()->role, ['admin', 'technical'])) {
            return match (auth()->user()->role) {
                'admin'     => redirect()->route('nfc.inventory'),
                'technical' => redirect()->route('technical.dashboard'),
            };
        }

        // 👇 Force logout for normal user sessions (student/staff)
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
    }

    return view('welcome'); // Always show welcome for public
})->name('home');

// (Public) keep old link working for everyone
Route::redirect('/nfc-inventory', '/nfc/inventory');

// ── Public pages ───────────────────────────────────────────────────────
Route::get('/register', [PublicRegisterController::class, 'showForm'])->name('public.register.form');
Route::post('/register', [PublicRegisterController::class, 'store'])->name('public.register.store');

// Legacy /register-uid route (kept so old links still work)
Route::get('/register-uid', [PublicRegisterController::class, 'showForm'])->name('public.registeruid.form');
Route::post('/register-uid', [PublicRegisterController::class, 'store'])->name('public.registeruid.store');

// After successful registration (go to borrow page)
Route::get('/borrow', [BorrowController::class, 'index'])->name('borrow.index');

// History is public (read-only)
Route::get('/history', [HistoryController::class, 'index'])->name('history.index');

// Public endpoint for fetching item details by UID
Route::get('/borrow/fetch/{uid}', [BorrowController::class, 'fetchItem'])->name('borrow.fetch');

// ── Reports (PUBLIC) ───────────────────────────────────────────────────
Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
Route::post('/reports', [ReportController::class, 'store'])->middleware('throttle:20,1')->name('reports.store');
Route::redirect('/report', '/reports/create')->name('report.alias');

// ── Live-reload JSON for NFC Inventory ─────────────────────────────────
Route::get('/items/last-sync', fn () =>
    response()->json(['last_sync_at' => Cache::get('items_last_sync_at')])
)->name('items.last-sync');

// ── Auth-only (any logged-in user) ─────────────────────────────────────
Route::middleware('auth')->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Borrow actions (mutating)
    Route::post('/borrow', [BorrowController::class, 'store'])->name('borrow.store');
    Route::post('/borrow/return/{uid}', [BorrowController::class, 'returnItem'])->name('borrow.return');
    Route::delete('/borrow/{id}', [BorrowController::class, 'destroy'])->name('borrow.destroy');

    // Notifications
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unreadCount');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markOneRead'])->name('notifications.markOneRead');

    // Test notification
    Route::get('/notify-test', function () {
        auth()->user()->notify(
            new GenericDatabaseNotification('Test notice', 'Hello from the bell!', url('/'))
        );
        return 'Sent';
    })->name('notify.test');
});

// ── Admin + Technical ──────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin,technical'])->group(function () {
    Route::get('/technical', [TechnicalDashboardController::class, 'index'])->name('technical.dashboard');

    // History import (only admin/technical)
    Route::post('/history/import/google', [HistoryController::class, 'importFromGoogleSheet'])
        ->middleware('throttle:5,1')
        ->name('history.import.google');

    Route::patch('/items/{asset_id}/mark-available', [ItemStatusController::class, 'markAvailable'])
        ->where('asset_id', '[A-Za-z0-9\-_]+')
        ->name('items.markAvailable');
});

// ── Admin-only ─────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Inventory
    Route::get('/nfc/inventory', [InventoryController::class, 'index'])->name('nfc.inventory');

    // Import items from Google Sheets
    Route::post('/items/import/google', [ItemImportController::class, 'importFromGoogle'])->name('items.import.google');

    // Items CRUD
    Route::post('/items', [InventoryController::class, 'store'])->name('items.store');
    Route::get('/items/{asset_id}/edit', [InventoryController::class, 'edit'])
        ->where('asset_id', '[A-Za-z0-9\-_]+')
        ->name('items.edit');
    Route::patch('/items/{asset_id}', [InventoryController::class, 'update'])
        ->where('asset_id', '[A-Za-z0-9\-_]+')
        ->name('items.update');
    Route::delete('/items/{asset_id}', [InventoryController::class, 'destroy'])
        ->where('asset_id', '[A-Za-z0-9\-_]+')
        ->name('items.destroy');

    // Mark as under repair
    Route::patch('/items/{asset_id}/under-repair', [InventoryController::class, 'markUnderRepair'])
        ->where('asset_id', '[A-Za-z0-9\-_]+')
        ->name('items.markUnderRepair');
});

// Admin → Reports
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin/reports')
    ->name('admin.reports.')
    ->group(function () {
        Route::get('/', [ReportController::class,'adminIndex'])->name('index');
        Route::get('/{report}', [ReportController::class,'show'])->whereNumber('report')->name('show');
        Route::patch('/{report}/status', [ReportController::class,'updateStatus'])->whereNumber('report')->name('updateStatus');
        Route::get('/{report}/attachments/{index}', [ReportController::class,'downloadAttachment'])
            ->whereNumber('report')->whereNumber('index')->name('attachment');
    });

// Breeze / Fortify routes
require __DIR__.'/auth.php';

// Keep legacy links working
Route::get('/register-user', fn () => redirect()->route('public.register.form'))->name('register-user');

// Default dashboard redirect
Route::get('/dashboard', fn () => redirect('/'))->middleware('auth')->name('dashboard');
