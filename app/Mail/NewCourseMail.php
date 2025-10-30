<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewCourseMail extends Mailable
{
    use Queueable, SerializesModels;

    public $course;

    /**
     * Create a new message instance.
     */
    public function __construct($course)
    {
        $this->course = $course;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('🎓 New Course Available: ' . $this->course->title_en)
            ->markdown('emails.new_course');
    }
}
