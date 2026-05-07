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
        'bill_symbol',
        'bill_number',
        'bill_date',
        'bill_month',
        'bill_year',
        'bill_private_key',
        'bill_sell_mst',
        'customer_name',
        'customer_address',
        'customer_cccd_number',
        'customer_phone',
        'payment_method',
        'total_amount',
        'bill_path',
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
