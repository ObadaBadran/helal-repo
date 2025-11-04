<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseOnline extends Model
{
    use HasFactory;
      protected $table = 'course_online';

    protected $fillable = [
        'name', 'description', 'duration', 'price', 'date', 'cover_image', 'meet_url'
    ];

    public function enrolls()
{
    return $this->hasMany(\App\Models\Enroll::class, 'course_online_id');
}

public function enrolledUsers()
{
    return $this->belongsToMany(\App\Models\User::class, 'enrolls', 'course_online_id', 'user_id')
                ->withPivot(['payment_status', 'is_enrolled'])
                ->withTimestamps();
}
}
