<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseOnline extends Model
{
    use HasFactory;

    protected $table = 'course_online';

    protected $fillable = [
        'name_en', 'description_en', 'name_ar', 'description_ar', 'price_usd', 'price_aed', 'cover_image', 'meet_url', 'appointment_id', 'active'
    ];

    public function enrolls()
    {
        return $this->hasMany(Enroll::class, 'course_online_id');
    }

    public function enrolledUsers()
    {
        return $this->belongsToMany(User::class, 'enrolls', 'course_online_id', 'user_id')
            ->withPivot(['payment_status', 'is_enrolled'])
            ->withTimestamps();
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
