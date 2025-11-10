<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationInformation extends Model
{
    use HasFactory;
    protected $table = 'consulation_informations';
     protected $fillable = [
        'type_en',
        'type_ar',
        'price',
        'currency',
        'duration',
    ];
}
