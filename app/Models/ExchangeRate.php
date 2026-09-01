<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_currency_id',
        'to_currency_id',
        'rate',
        'rate_date',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'rate_date' => 'date',
    ];

    public function fromCurrency()
    {
        return $this->belongsTo(Currency::class, 'from_currency_id');
    }

    public function toCurrency()
    {
        return $this->belongsTo(Currency::class, 'to_currency_id');
    }

    public function scopeLatestRate($query)
    {
        return $query->orderBy('rate_date', 'desc');
    }

    public function scopeForCurrencies($query, $fromId, $toId)
    {
        return $query->where('from_currency_id', $fromId)
                     ->where('to_currency_id', $toId);
    }
}