<?php

namespace App\Models;

use App\Traits\BelongsToProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingCommission extends Model
{
    use BelongsToProvider, HasFactory, SoftDeletes;

    protected $fillable = [
        'provider_id',
        'commission_id',
        'min_amount',
        'max_amount',
        'value',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'value' => 'decimal:2',
    ];

    public function provider()
    {
        return $this->belongsTo(ProviderService::class, 'provider_id');
    }

    public function commission()
    {
        return $this->belongsTo(Commission::class);
    }

    public function getCalculationTypeAttribute(): ?string
    {
        return $this->commission?->commission_type;
    }

    public function getTypeAttribute(): ?string
    {
        return $this->calculation_type;
    }
}
