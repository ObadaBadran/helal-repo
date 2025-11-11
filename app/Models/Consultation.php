<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consultation extends Model
{
     protected $fillable = [
        'user_id',
        'information_id', 
        'name',
        'email',
        'phone',
        'payment_status',
        'payment_method',
        'stripe_session_id',
        'meet_url',
        'is_done',
        'consultation_date',
        'consultation_time'
    ];

    public function user() : BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function information(): BelongsTo {
        return $this->belongsTo(ConsultationInformation::class, 'information_id');
    }
}
