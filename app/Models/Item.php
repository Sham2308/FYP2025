<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $table = 'items';

    protected $fillable = [
        'uid',
        'asset_id',
        'name',
        'detail',
        'accessories',
        'type_id',
        'serial_no',
        'status',
        'qr_id',
        'remarks',
    ];
}
