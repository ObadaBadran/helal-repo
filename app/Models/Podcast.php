<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Podcast extends Model
{
    protected $table = 'podcasts';

     protected $fillable = [
        'title_en',
        'title_ar',
        'description_en',
        'description_ar',
        'youtube_link',
        'cover',
    ];
}
