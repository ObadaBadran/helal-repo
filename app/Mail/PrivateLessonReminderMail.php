<?php

namespace App\Mail;

use App\Models\PrivateLessonInformation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PrivateLessonReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $privateLessonInformation;

    /**
     * Create a new message instance.
     */
    public function __construct(PrivateLessonInformation $privateLessonInformation)
    {
        $this->privateLessonInformation = $privateLessonInformation;
    }

    public function build()
    {
        return $this->subject('Reminder of the upcoming private lesson')
            ->view('emails.private_lesson_reminder')
            ->with([
                'privateLessonInformation' => $this->privateLessonInformation,
            ]);
    }
}
