<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = ['user_id', 'room', 'body', 'guest_name']; // ← added guest_name

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

