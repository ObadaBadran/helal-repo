<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivateLesson extends Model
{
     protected $table = 'private_lessons';
     protected $fillable = [
        'title_en',
        'title_ar',
        'description_en',
        'description_ar',
        'cover_image'
    ];

     public function informations(): HasMany
    {
        return $this->hasMany(PrivateLessonInformation::class);
    }
}
