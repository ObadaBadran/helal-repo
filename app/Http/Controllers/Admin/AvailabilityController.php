<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Availability;
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
                'availabilities.*.day' => 'required|string|in:Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
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
}
