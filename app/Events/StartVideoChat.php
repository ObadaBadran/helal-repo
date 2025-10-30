<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StartVideoChat implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data; // البيانات التي ترسل للفرونت

    public function __construct($data)
    {
        $this->data = $data;
    }

   /* public function broadcastOn()
    {
        return new PresenceChannel('presence-video-channel'); // أو PrivateChannel حسب حاجتك
    }*/

    public function broadcastOn()
    {
        // قناة presence حقيقية حتى تسمح بإرسال client events
        return new PresenceChannel('presence-video-room.' . $this->data['room_id']);
    }
    /*public function broadcastOn()
    {
        // قناة خاصة بالبث الجماعي
        return new PresenceChannel('video-room.' . $this->data['room_id']);
    }*/

    public function broadcastAs()
    {
        return 'start-video-chat';
    }
}
