<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BorrowDetail extends Model
{
    protected $table = 'borrow_details';

    // Disable Eloquent's created_at / updated_at if your table doesn't have them
    public $timestamps = false;

    // Exactly the 12 columns from your Google Sheet (A → L)
    protected $fillable = [
        'UID',
        'BorrowID',
        'UserID',
        'BorrowerName',
        'AssetID',
        'Name',
        'BorrowDate',
        'ReturnDate',
        'BorrowedAt',
        'ReturnedAt',
        'Status',
        'Remarks',
    ];

    // Helpful for queries & your Blade helper (returns Carbon instances)
    protected $casts = [
        'BorrowDate' => 'date:Y-m-d',
        'ReturnDate' => 'date:Y-m-d',
    ];
}
