<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\GenericDatabaseNotification;
use Illuminate\Support\Facades\Log;

class ItemStatusController extends Controller
{
    /**
     * Mark an item as AVAILABLE again after repair.
     * Route: PATCH /items/{asset_id}/mark-available  → name: items.markAvailable
     */
    public function markAvailable(string $asset_id)
    {
        // Find by asset_id (your string PK in routes), not by numeric id
        $item = Item::where('asset_id', $asset_id)->firstOrFail();

        // Only allow when currently "under repair"
        $current = strtolower(trim((string) $item->status));
        if ($current !== 'under repair') {
            return back()->with('error', 'Only items currently "Under Repair" can be marked as Available.');
        }

        // Update status (canonical lowercase for DB)
        $item->status = 'available';
        $item->save();

        // Notify all admins
        try {
            $admins = User::where('role', 'admin')->get();
            Notification::send($admins, new GenericDatabaseNotification(
                'Item is now Available',
                "The item {$item->name} ({$item->asset_id}) has been marked as Available.",
                route('nfc.inventory')
            ));
        } catch (\Throwable $e) {
            Log::warning('Notify (mark available) failed: '.$e->getMessage());
        }

        return back()->with('success', 'Item marked as Available and admins notified.');
    }
}
