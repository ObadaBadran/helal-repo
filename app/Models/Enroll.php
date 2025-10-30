<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enroll extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'payment_status',
        'payment_method',
        'amount',
        'currency',
        'is_enroll',
        'transaction_id',
    ];

    public function user() : BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function course() : BelongsTo {
        return $this->belongsTo(Course::class);
    }
}
