<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'identifier',
        'status',
        'user_agent',
        'ip_address',
        'attempted_at',
    ];

    protected $casts = [
        'uuid' => 'string',
        'attempted_at' => 'datetime',
    ];

    /**
     * Model boot method for automatic UUID generation
     *
     * This method automatically generates a UUID for new login activities
     * if not provided during creation.
     */
    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model (UUID instead of ID)
     *
     * This allows Route Model Binding to use UUID instead of the default ID
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get the user for the login activity.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
