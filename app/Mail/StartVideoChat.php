<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StartVideoChat extends Mailable
{
    use Queueable, SerializesModels;

    public $channel;

    /**
     * Create a new message instance.
     */
    public function __construct($channel)
    {
        $this->channel = $channel; // يحتوي على room_id واسم القناة أو أي بيانات تريد إرسالها
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('New Video Channel Created: ' . $this->channel['room_id'])
            ->markdown('emails.video_channel');
    }
}
