<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrivateLessonInformation extends Model
{
    protected $table = 'private_lesson_informations';
    protected $fillable = [
        'appointment_id',
        'private_lesson_id',
        'place_ar',
        'place_en',
        'price_aed',
        'price_usd',
        // 'duration'
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(PrivateLesson::class, 'private_lesson_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function enrolls(): HasMany
    {
        return $this->hasMany(Enroll::class, 'private_information_id');
    }
}
