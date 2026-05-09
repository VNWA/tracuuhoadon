<?php

namespace App\Models;

use Database\Factories\BillFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    /** @use HasFactory<BillFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'private_key',
        'date',
        'month',
        'year',
        'sell_mst',
        'customer_name',
        'unit_name',
        'customer_mst',
        'customer_address',
        'customer_cccd',
        'customer_phone',
        'payment_method',
        'note',
        'bill_total_currency',
        'bill_total_text',
        'path',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }
}
