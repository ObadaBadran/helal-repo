<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoChatController;
use App\Http\Controllers\AgoraController;


Route::get('/', function () {
    return view('welcome');
});
Route::get('/live/{channelName}', [AgoraController::class, 'showBroadcast'])->name('live.broadcast');

/*
Route::get('/watch/{liveId}', function ($liveId) {
    return view('live.watch', compact('liveId'));
})
->name('live.watch')
->middleware('signed');*/
/*
Route::get('/watch/{liveId}', [VideoChatController::class, 'show'])
    ->name('live.watch')
    ->middleware(['web']);
    
    Route::get('/live/{channelName}/admin', [AgoraController::class, 'showBroadcastAdmin']);
Route::get('/live/{channelName}/student', [AgoraController::class, 'showBroadcastStudent']);*/


Route::prefix('agora')->group(function () {
    Route::get('/broadcast/{channelName}', [AgoraController::class, 'showBroadcast'])->name('agora.broadcast');
    Route::post('/raise-hand', [AgoraController::class, 'raiseHand']);
    Route::post('/mute', [AgoraController::class, 'muteUser']);
    Route::post('/kick', [AgoraController::class, 'kickUser']);
    Route::get('/participants/{channelName}', [AgoraController::class, 'getParticipants']);
});

  // اسم وحيد;