<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    protected $fillable = ['date', 'start_time', 'end_time'];

      protected $casts = [
        'date' => 'date',
    ];

    
    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function courseOnline(): HasOne
    {
        return $this->hasOne(CourseOnline::class);
    }


}
