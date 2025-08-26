<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NfcScan extends Model
{
    protected $fillable = [
        'uid',
        'asset_id',
        'name',
        'detail',
        'accessories',
        'type_id',
        'serial_no',
        'location_id',
        'purchase_date',
        'remarks',
        'status',
    ];

    protected $casts = [
        'purchase_date' => 'date',
    ];
}
