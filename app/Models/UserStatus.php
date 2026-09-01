<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStatus extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */

    protected $fillable = [
        'status_name',
        'status_code',
    ];

    /**
     * Get the users for the status.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
