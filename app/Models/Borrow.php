<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Borrow extends Model
{
    use HasFactory;

    protected $table = 'borrows';

    protected $fillable = [
        'uid',
        'user_id',
        'borrowed_at',
        'returned_at',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'uid', 'uid');
    }

    // Since user_id is now a staff/student text, we don't link to users table
    public function getUserIdentifier()
    {
        return $this->user_id;
    }
}
