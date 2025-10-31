<?php

namespace App\Http\Controllers;


use App\Events\VideoSignal;
use App\Mail\StartVideoChat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class VideoChatController extends Controller
{
    // إرسال دعوة لمستخدم واحد (optional إذا تريد private call)
    public function callUser(Request $request)
    {
        $data = [
            'user_to_call' => $request->user_to_call,
            'signal_data' => $request->signal_data,
            'from' => Auth::id(),
            'type' => 'call'
        ];

        event(new StartVideoChat($data));

        return response()->json(['message' => 'Call initiated']);
    }

    // قبول الدعوة لمستخدم واحد (optional إذا تريد private call)
    public function acceptCall(Request $request)
    {
        $data = [
            'signal' => $request->signal,
            'to' => $request->to,
            'type' => 'accept'
        ];

        event(new StartVideoChat($data));

        return response()->json(['message' => 'Call accepted']);
    }

    // بدء بث جماعي (multi-user)
    public function start(Request $request)
    {
        $roomId = $request->room_id; // معرف البث الفريد
        $data = [
            'room_id' => $roomId,
            'from' => Auth::id(),
            'type' => 'call'
        ];

        event(new StartVideoChat($data));
       /* $users = User::where('role', 'user')->get();
        foreach ($users as $user) {
            Mail::to($user->email)->send(new StartVideoChat([
                'room_id' => $roomId
            ]));
        }*/

        return response()->json(['message' => 'Broadcast started', 'room_id' => $roomId]);
    }

    // إرسال signal data لكل المشاركين في البث
    public function signal(Request $request)
    {
        $data = [
            'from' => Auth::id(),
            'signal' => $request->signal,
            'room_id' => $request->room_id,
        ];

        // VideoSignal Event يجب أن تنشئه بنفس طريقة StartVideoChat
        event(new VideoSignal($data));

        return response()->json(['message' => 'Signal sent']);
    }
}
