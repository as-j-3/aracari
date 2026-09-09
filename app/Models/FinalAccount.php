<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinalAccount extends Model
{
    protected $primaryKey = 'final_account_id';

    protected $fillable = [
        'name',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'final_account_id', 'final_account_id');
    }
}