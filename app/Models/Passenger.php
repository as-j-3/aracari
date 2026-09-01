<?php

namespace App\Models;

use App\Traits\BelongsToProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class Passenger extends Model
{
    use BelongsToProvider, HasFactory;

    protected $providerOwnershipColumn = 'created_by';
    protected $providerOwnershipScope = 'provider_user';

    protected $fillable = [
        'uuid',
        'created_by',
        'gender_id',
        'full_name',
        'phone_number',
        'birthday',
        'personal_card_id',
        'personal_card_id_photo',
        'personal_card_place_of_issue',
        'personal_card_date_issu',
        'personal_card_date_expiry',
        'passport_number',
        'passport_photo',
        'passport_date_issu',
        'passport_date_expiry',
        'passport_place_of_issue',
    ];

    protected $casts = [
        'passport_date_issu' => 'date',
        'passport_date_expiry' => 'date',
        'birthday' => 'date',
        'uuid' => 'string',
        'personal_card_date_issu' => 'date',
        'personal_card_date_expiry' => 'date',
    ];

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

    protected static function booted(): void
    {
        static::creating(function (Passenger $passenger) {
            if (empty($passenger->uuid)) {
                $passenger->uuid = (string) \Illuminate\Support\Str::uuid();
            }
            if (auth()->check() && !$passenger->isDirty('created_by')) {
                $passenger->created_by = auth()->id();
            }
        });
    }
    /**
     * حساب العمر من تاريخ الميلاد
     */
    public function getAgeAttribute(): int
    {
        return $this->birthday ? $this->birthday->age : 0;
    }

    /**
     * Get the user who created this passenger.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the gender for the passenger.
     */
    public function gender()
    {
        return $this->belongsTo(Gender::class);
    }

    /**
     * Get the tickets for the passenger.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
    public function tripReservations()
    {
        return $this->hasManyThrough(
            TripReservation::class,
            Ticket::class,
            'passenger_id',  // Foreign key on tickets table
            'id',            // Local key on trip_reservations table
            'id',            // Local key on passengers table
            'trip_reservation_id'  // Foreign key on tickets table
        );
    }
    // bookings تم إزالتها — tickets ليست مرتبطة مباشرة بالـ bookings
    // استخدم tripReservations() بدلاً منها للتحقق من وجود حجوزات
    /**
     * جلب رابط صورة بطاقة الهوية
     */
    public function getPersonalCardIdPhotoUrlAttribute(): ?string
    {
        return $this->personal_card_id_photo ? (new \App\Services\FileUploadService())->getFileUrl($this->personal_card_id_photo) : null;
    }

    /**
     * جلب رابط صورة جواز السفر
     */
    public function getPassportPhotoUrlAttribute(): ?string
    {
        return $this->passport_photo ? (new \App\Services\FileUploadService())->getFileUrl($this->passport_photo) : null;
    }
    // Accessor and mutator for encrypting/decrypting personalCardId
    protected function personalCardId(): Attribute
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
    // Accessor and mutator for encrypting/decrypting passportNumber
    protected function passportNumber(): Attribute
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

}
