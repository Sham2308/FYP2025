<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','subject','category','priority','item_id','message','attachments','status',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    // relationships
    public function user() { return $this->belongsTo(User::class); }
    public function item() { return $this->belongsTo(\App\Models\Item::class, 'item_id'); }
}
