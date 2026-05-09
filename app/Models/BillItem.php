<?php

namespace App\Models;

use Database\Factories\BillItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillItem extends Model
{
    /** @use HasFactory<BillItemFactory> */
    use HasFactory;

    protected $fillable = [
        'bill_id',
        'name',
        'unit',
        'quantity',
        'unit_price',
        'amount',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}
