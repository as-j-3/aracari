<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PolicyCancellationRule extends Model
{
    use HasFactory;

    protected $table = 'policy_cancellation_rules';

    protected $fillable = [
        'policy_id',
        'from_hours',
        'to_hours',
        'fee_percentage',
    ];

    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }
}
