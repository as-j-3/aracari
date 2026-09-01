<?php

namespace App\Models;

use App\Traits\BelongsToProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceCommission extends Model
{
    use BelongsToProvider, HasFactory, SoftDeletes;

    protected $fillable = [
        'provider_id',
        'service_id',
        'commission_id',
        'value',
    ];

    protected $casts = [
        'value' => 'decimal:2',
    ];

    public function provider()
    {
        return $this->belongsTo(ProviderService::class, 'provider_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
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
