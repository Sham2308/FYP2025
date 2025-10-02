<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Borrow extends Model
{
    protected $table = 'borrows';

    protected $fillable = [
        'transaction_id',
        'uid',
        'user_id',
        'borrower_name',
        'borrow_date',
        'due_date',
        'return_date',
        'remarks',
        'borrowed_at',
        'returned_at',
    ];

    protected $casts = [
        'borrow_date' => 'date',
        'due_date'    => 'date',
        'return_date' => 'date',
        'borrowed_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'uid', 'uid');
    }
}
