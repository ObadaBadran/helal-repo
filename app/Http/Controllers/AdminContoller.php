<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\User;
use App\Models\Meeting;
use App\PaginationTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AdminContoller extends Controller
{
    use PaginationTrait;

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

    public function getMeetings()
    {
        $meetings = Meeting::all();

        return response()->json([
            'message' => 'Meetings have been successfully retrieved ✅',
            'data' => $meetings
        ], 200);
    }

    public function addConsultationResponse(Request $request) {

        try {
        $validatedData = $request->validate([
            'consultation_id' => 'required|integer|exists:consultations,id',
            'meet_url' => 'required|url',
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|date_format:H:i',
        ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'errors' => $e->errors()
            ], 422);
        }

        $consultation = Consultation::find($validatedData['consultation_id']);
        if (!$consultation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Consultation not found.'
            ], 404);
        }

        $consultation->update([
            'meet_url' => $validatedData['meet_url'],
            'consultation_date' => $validatedData['date'],
            'consultation_time' => $validatedData['time'],
        ]);

        $locale = app()->getLocale();
        $isArabic = $locale === 'ar';

        try {
            Mail::send('emails.consultation_meet', [
                'consultation' => $consultation,
                'locale' => $locale,
            ], function ($message) use ($consultation, $isArabic) {
                $message->to($consultation->email)
                        ->subject($isArabic
                            ? 'تفاصيل استشارتك الخاصة'
                            : 'Your Private Consultation Details');
            });
        } catch (\Exception $e) {
            Log::error('Failed to send consultation email', [
                'consultation_id' => $consultation->id,
                'user_email' => $consultation->email,
                'error' => $e->getMessage()
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => $isArabic
                ? 'تم إرسال تفاصيل الاستشارة إلى البريد الإلكتروني للمستخدم.'
                : 'Consultation details sent to user email.',
        ]);
    }


}
