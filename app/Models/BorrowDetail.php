<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BorrowDetail extends Model
{
    use HasFactory;

    protected $table = 'borrow_details'; // link to DB table

    protected $fillable = [
        'Timestamp',
        'BorrowID',
        'UserID',
        'BorrowerName',
        'UID',
        'AssetID',
        'Name',
        'BorrowDate',
        'ReturnDate',
        'BorrowedAt',
        'ReturnedAt',
        'Status',
        'Remarks',
    ];
}
