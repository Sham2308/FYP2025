<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ItemUnderRepairNotification extends Notification
{
    use Queueable;

    public function __construct(public \App\Models\Item $item) {}

    public function via($notifiable) { return ['database']; }

    public function toDatabase($notifiable)
    {
        return [
            'type'     => 'under_repair',
            'item_id'  => $this->item->id,
            'asset_id' => $this->item->asset_id,
            'name'     => $this->item->name,
            'message'  => "Item {$this->item->asset_id} ({$this->item->name}) marked Under Repair.",
            'url'      => route('technical.dashboard'),
        ];
    }
}
