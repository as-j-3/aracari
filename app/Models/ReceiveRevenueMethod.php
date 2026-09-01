<?php

namespace App\Models;

use App\Traits\BelongsToProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class ReceiveRevenueMethod extends Model
{
    use BelongsToProvider, HasFactory;

    protected $fillable = [
        'digital_pocket_id',
        'provider_id',
        'account_number',
        'account_name',
    ];

    /**
     * Get the digital pocket for the receive revenue method.
     */
    public function digitalPocket()
    {
        return $this->belongsTo(DigitalPocket::class);
    }

    /**
     * Get the provider service for the receive revenue method.
     */
    public function provider()
    {
        return $this->belongsTo(ProviderService::class);
    }

    protected function accountNumber(): Attribute
    {
    return Attribute::make(
        get: function ($value) {
            if (empty($value)) return null;
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        },
        set: fn ($value) => $value ? Crypt::encryptString($value) : null,
    );
    }

    public function getAccountNumberLast4Attribute(): ?string
    {
        $number = $this->account_number;
        if (strlen($number) < 4) return '****';
        return '****' . substr($number, -4);
    }

}
