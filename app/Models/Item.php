<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $table = 'items';
    protected $primaryKey = 'asset_id';   // asset_id is the PK
    public $incrementing = false;         // asset_id is not auto-increment
    protected $keyType = 'string';        // asset_id is a string
    
    protected $fillable = [
        'item_id',
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

    // ✅ Standardized status values
    public const STATUS_AVAILABLE     = 'available';
    public const STATUS_BORROWED      = 'borrowed';
    public const STATUS_RETIRED       = 'retire';
    public const STATUS_UNDER_REPAIR  = 'under repair';
    public const STATUS_STOLEN        = 'stolen';
    public const STATUS_MISSING       = 'missing/lost';

    /**
     * ✅ Optional: Nice display for status (for blade)
     */
    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status); // e.g. "under repair" → "Under repair"
    }
}
