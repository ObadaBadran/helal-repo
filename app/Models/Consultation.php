<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consultation extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'payment_status',
        'payment_method',
        'stripe_session_id',
        'amount',
        'currency'
    ];

    public function user() : BelongsTo {
        return $this->belongsTo(User::class);
    }
}
