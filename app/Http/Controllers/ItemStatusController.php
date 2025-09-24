<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\GenericDatabaseNotification;

class ItemStatusController extends Controller
{
    /**
     * Mark an item as available again after repair.
     */
    public function markAvailable($asset_id)
    {
        // Find the item by its asset_id (primary key in your migration)
        $item = Item::findOrFail($asset_id);

        // Update status
        $item->status = 'available';
        $item->save();

        // Notify all admins
        $admins = User::where('role', 'admin')->get();
        Notification::send($admins, new GenericDatabaseNotification(
            "Item is now available",
            "The item {$item->name} ({$item->asset_id}) has been marked as available.",
            url()->route('nfc.inventory')
        ));

        return redirect()->back()->with('success', 'Item marked as available and admins notified.');
    }
}
