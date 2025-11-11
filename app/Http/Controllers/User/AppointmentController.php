<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Availability;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = (int)$request->query('page', 1);
        $perPage = (int)$request->query('per_page', 10);

        $appointments = Appointment::orderBy('id', 'asc')->paginate($perPage, ['*'], 'page', $page);

        if ($appointments->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No appointments found.'
            ], 404);
        }

        $data = $appointments->map(function ($appointment) {
            return [
                'id' => $appointment->id,
                'date' => $appointment->date,
                'start_time' => $appointment->start_time,
                'end_time' => $appointment->end_time
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $appointments->currentPage(),
                'last_page' => $appointments->lastPage(),
                'per_page' => $appointments->perPage(),
                'total' => $appointments->total(),
            ]
        ], 200);
    }
}
