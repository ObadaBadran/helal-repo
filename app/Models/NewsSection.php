<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsSection extends Model
{
    use HasFactory;
    protected $table = 'news_sections';

    protected $fillable = [
        'title_en',
        'title_ar',
        'subtitle_en',
        'subtitle_ar',
        'description_en',
        'description_ar',
    ];



    public function images()
    {
        return $this->hasMany(NewsSectionImage::class);
    }

}

