<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Video extends Model
{
    protected $fillable = [
        'course_id',
        'path',
        'title_en',
        'title_ar',
        'subTitle_en',
        'subTitle_ar',
        'description_en',
        'description_ar',
        'youtube_path',
    ];


    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
