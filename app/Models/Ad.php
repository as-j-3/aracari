<?php

namespace App\Models;

use App\Traits\BelongsToProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Ad extends Model
{
    use BelongsToProvider, HasFactory, SoftDeletes;

    protected string $providerOwnershipColumn = 'user_id';
    protected string $providerOwnershipScope = 'provider_user';

    protected $fillable = [
        'uuid',
        'user_id',
        'title',
        'description',
        'image_url',
        'target_url',
        'status',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'uuid' => 'string',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
               && $this->start_date->isPast()
               && $this->end_date->isFuture();
    }

    public function getImageUrl(): ?string
    {
        $imagePath = $this->attributes['image_url'] ?? null;
        return $imagePath ? asset('storage/' . $imagePath) : null;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->getImageUrl();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ad) {
            if (empty($ad->uuid)) {
                $ad->uuid = Str::uuid()->toString();
            }
        });
    }
}
