<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsSectionImage extends Model
{
    protected $table = 'news_section_images';
    protected $fillable = ['news_section_id', 'image'];

    public function newsSection()
    {
        return $this->belongsTo(NewsSection::class);
    }

}
