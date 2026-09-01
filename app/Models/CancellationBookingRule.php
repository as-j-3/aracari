<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CancellationBookingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_id',
        'from_hours',
        'to_hours',
        'fee_percentage',
    ];

    protected $casts = [
        'from_hours' => 'integer',
        'to_hours' => 'integer',
        'fee_percentage' => 'decimal:2',
    ];

    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }
}
