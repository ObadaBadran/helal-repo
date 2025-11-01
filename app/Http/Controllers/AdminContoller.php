<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminContoller extends Controller
{
   public function getUsers(Request $request)
{
    try {
       
        $lang = $request->query('lang', 'en');

       
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', (int) $request->query('sizer', 10));

       
        $usersQuery = User::where('role', 'user')->orderBy('id', 'asc');
        $users = $usersQuery->paginate($perPage, ['*'], 'page', $page);

       
        $data = $users->map(function ($user) use ($lang) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
               
            ];
        });

        if ($data->isEmpty()) {
            return response()->json(['message' => 'No users found.'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
        
            ]
        ]);
    } catch (Exception $e) {
        return response()->json([
            'error' => 'Something went wrong.',
            'message' => $e->getMessage()
        ], 500);
    }
}
}
