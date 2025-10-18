<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'guest_name',      // added for public user name
        'guest_email',     // added for public user email
        'subject',
        'category',
        'priority',
        'item_asset_id',   // optional: depends on your database column
        'message',
        'attachments',
        'status',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    // relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function item()
    {
        return $this->belongsTo(\App\Models\Item::class, 'item_id');
    }
}
