<?php

namespace App\Http\Controllers;

use App\Events\StartVideoChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VideoChatController extends Controller
{
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
}
