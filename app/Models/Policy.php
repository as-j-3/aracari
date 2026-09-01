<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'type',
        'created_by',
    ];

    protected $casts = [
        'uuid' => 'string',
    ];

    /**
     * Model boot method for automatic UUID generation
     *
     * This method automatically generates a UUID for new policies
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rules()
    {
        return $this->hasMany(PolicyCancellationRule::class, 'policy_id');
    }

    public function trips()
    {
        return $this->belongsToMany(Trip::class, 'trip_policies');
    }
}
