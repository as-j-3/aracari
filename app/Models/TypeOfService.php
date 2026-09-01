<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeOfService extends Model
{
    use HasFactory;

    protected $table = 'type_of_services';

    protected $fillable = [
        'type_name',
    ];

    /**
     * Get the providers that use this service type.
     */
    public function providers()
    {
        return $this->hasMany(ProviderService::class, 'type_of_service_id');
    }


        /**
        * Get the tax rules associated with this service type.
        */
    public function taxRules()
    {
        return $this->hasMany(TaxRule::class, 'service_type_id');
    }
}
