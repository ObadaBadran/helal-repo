<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminContoller extends Controller
{
    public function getUsers()
    {
        return User::all()->where('role', 'user');
    }
}
