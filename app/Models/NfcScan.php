<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NfcScan extends Model
{
    protected $fillable = [
        'uid',         // NFC card UID
        'student_id',  // Student ID (e.g. 23FTTXXXX)
        'user_name',
        'item_id',
        'item_name',
        'status',      // 'good' | 'bad'
    ];
}
