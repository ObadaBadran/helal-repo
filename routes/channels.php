<?php
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('presence-video-channel', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});
