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
        $appointments = Appointment::query();

        if ($request->has('day'))
            $appointments = $appointments->whereDay('date', $request->query('day'));

        if ($request->has('month'))
            $appointments = $appointments->whereMonth('date', $request->query('month'));

        if ($request->has('year'))
            $appointments = $appointments->whereYear('date', $request->query('year'));

        $appointments = $appointments->latest('date')->get();

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
            'data' => $data
        ], 200);
    }
}
