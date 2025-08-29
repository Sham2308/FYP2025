<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $table = 'items';
    protected $primaryKey = 'asset_id';   // ✅ use asset_id instead of id
    public $incrementing = false;         // ✅ asset_id is not auto-increment
    protected $keyType = 'string';        // ✅ asset_id is a string


    protected $fillable = [
        'uid',
        'asset_id',
        'name',
        'detail',
        'accessories',
        'type_id',
        'serial_no',
        'status',
        'purchase_date',
        'remarks',
    ];
}
