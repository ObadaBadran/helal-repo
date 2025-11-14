<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Availability;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AvailabilityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $availabilities = Availability::all();

        return response()->json([
            'status' => 'success',
            'message' => "Availabilities retrieved successfully",
            'Availabilities' => $availabilities,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        try {
            $request->validate([
                'availabilities' => 'required|array',
                'availabilities.*.day' => 'required|string|in:Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday,Monday',
                'availabilities.*.start_time' => 'required|date_format:H:i',
                'availabilities.*.end_time' => 'required|date_format:H:i|after:availabilities.*.start_time',
            ]);

            foreach ($request->availabilities as $availability) {
                Availability::updateOrCreate(
                    ['day' => $availability['day']],
                    [
                        'start_time' => $availability['start_time'],
                        'end_time' => $availability['end_time']
                    ]
                );
            }

            return response()->json([
                'status' => 'success',
                'message' => "Availabilities created successfully",
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'error' => $e->getMessage()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create availabilities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $availability = Availability::findOrFail($id);
            $availability->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Availability deleted successfully'
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Availability not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete availability',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getDayIntervals(Request $request)
    {
        if (!$request->has('date')) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'error' => 'date parameter is required'
            ], 422);
        }

        $date = Carbon::parse($request->date)->format('Y-m-d');
        $dayName = Carbon::parse($request->date)->format('l');

        $availability = Availability::where('day', $dayName)->first();

        if (!$availability) {
            return response()->json([
                'status' => 'success',
                'message' => "No availability for this day",
                'data' => [
                    "day" => $dayName,
                    "date" => $date,
                    "available_intervals" => []
                ]
            ], 400);
        }

        $workingStart = Carbon::parse($availability->start_time);
        $workingEnd = Carbon::parse($availability->end_time);

        $appointments = Appointment::whereDate('date', $date)
            ->orderBy('start_time', 'asc')
            ->get();

        $intervals = [];
        $currentStart = $workingStart;

        foreach ($appointments as $app) {
            $appStart = Carbon::parse($app->start_time);
            $appEnd = Carbon::parse($app->end_time);

            if ($currentStart < $appStart) {
                $intervals[] = [
                    'start' => $currentStart->format('H:i'),
                    'end' => $appStart->copy()->subMinute()->format('H:i')
                ];
            }

            $currentStart = $appEnd->copy()->addMinute();
        }

        if ($currentStart < $workingEnd) {
            $intervals[] = [
                'start' => $currentStart->format('H:i'),
                'end' => $workingEnd->format('H:i')
            ];
        }

        return response()->json([
            'status' => 'success',
            'message' => "Available intervals retrieved successfully",
            'data' => [
                "day" => $dayName,
                "date" => $date,
                "available_intervals" => $intervals
            ]
        ], 200);
    }
}
