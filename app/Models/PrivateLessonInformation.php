<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivateLessonInformation extends Model
{
    protected $table = 'private_lesson_informations';
    protected $fillable = [
        'place',
        'price',
        'duration',
    ];
}
