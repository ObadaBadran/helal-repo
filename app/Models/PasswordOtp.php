<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PasswordOtp extends Model
{
    protected $fillable = [
        'user_id',
        'otp',
        'expires_at',
    ];

    // تحويل expires_at إلى كائن Carbon تلقائيًا
    protected $dates = [
        'expires_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function isExpired()
    {
        return Carbon::now()->greaterThan($this->expires_at);
    }
}
