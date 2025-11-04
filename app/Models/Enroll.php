<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enroll extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'course_online_id',
        'payment_status',
        'payment_method',
        'amount',
        'currency',
        'is_enroll',
        'stripe_session_id',
        'transaction_id',
    ];

    public function user() : BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function course() : BelongsTo {
        return $this->belongsTo(Course::class);
    }

    public function courseOnline()
{
    return $this->belongsTo(CourseOnline::class, 'course_online_id');
}
}
