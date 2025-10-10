<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\GenericDatabaseNotification;
use Illuminate\Support\Facades\Log;
use App\Services\GoogleSheetService;

class ItemStatusController extends Controller
{
    /**
     * Mark an item as AVAILABLE again after repair.
     * Route: PATCH /items/{asset_id}/mark-available  → name: items.markAvailable
     */
    public function markAvailable(string $asset_id)
    {
        $item = Item::where('asset_id', $asset_id)->firstOrFail();

        $current = strtolower(trim((string) $item->status));
        if ($current !== 'under repair') {
            return back()->with('error', 'Only items currently "Under Repair" can be marked as Available.');
        }

        $item->status = 'available';
        $item->save();

        // ✅ Sync to Google Sheets
        try {
            $sheet = new GoogleSheetService();
            $sheet->updateRow($item);
        } catch (\Throwable $e) {
            Log::error('Google Sheet sync failed (markAvailable): ' . $e->getMessage());
        }

        // ✅ Notify admins
        try {
            $admins = User::where('role', 'admin')->get();
            Notification::send($admins, new GenericDatabaseNotification(
                'Item is now Available',
                "The item {$item->name} ({$item->asset_id}) has been marked as Available.",
                route('nfc.inventory')
            ));
        } catch (\Throwable $e) {
            Log::warning('Notify (markAvailable) failed: '.$e->getMessage());
        }

        return back()->with('success', 'Item marked as Available, synced to Google Sheet, and admins notified.');
    }

    /**
     * Mark an item as UNDER REPAIR (admin triggers this).
     * Route: PATCH /items/{asset_id}/under-repair  → name: items.markUnderRepair
     */
    public function markUnderRepair(string $asset_id)
    {
        $item = Item::where('asset_id', $asset_id)->firstOrFail();

        $current = strtolower(trim((string) $item->status));
        if ($current === 'under repair') {
            return back()->with('error', 'Item is already marked as Under Repair.');
        }

        $item->status = 'under repair';
        $item->save();

        // ✅ Sync to Google Sheets
        try {
            $sheet = new GoogleSheetService();
            $sheet->updateRow($item);
        } catch (\Throwable $e) {
            Log::error('Google Sheet sync failed (markUnderRepair): ' . $e->getMessage());
        }

        // ✅ Notify admins
        try {
            $admins = User::where('role', 'admin')->get();
            Notification::send($admins, new GenericDatabaseNotification(
                'Item sent for Repair',
                "The item {$item->name} ({$item->asset_id}) has been marked as Under Repair.",
                route('nfc.inventory')
            ));
        } catch (\Throwable $e) {
            Log::warning('Notify (markUnderRepair) failed: '.$e->getMessage());
        }

        return back()->with('success', 'Item marked as Under Repair, synced to Google Sheet, and admins notified.');
    }
}
