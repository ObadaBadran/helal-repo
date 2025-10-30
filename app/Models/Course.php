<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'title_en',
        'title_ar',
        'subTitle_en',
        'subTitle_ar',
        'description_en',
        'description_ar',
        'price_aed',
        'price_usd',
        'reviews',
        'image',
    ];


    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function enrolls() : HasMany {
        return $this->hasMany(Enroll::class, 'course_id');
    }
}
