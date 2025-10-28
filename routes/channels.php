<?php
use Illuminate\Support\Facades\Broadcast;

/*Broadcast::channel('presence-video-channel', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});*/

Broadcast::channel('video-room.{room_id}', function ($user, $room_id) {
    // جميع المستخدمين المصرح لهم يمكنهم الانضمام
    return ['id' => $user->id, 'name' => $user->name];
});
