<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $course;
    public $joinUrl;

    /**
     * Create a new message instance.
     */

    public function __construct($user, $course, $joinUrl)
    {
        $this->user = $user;
        $this->course = $course;
        $this->joinUrl = $joinUrl;
    }

    public function build()
    {
        return $this->subject("Online Course Ready: {$this->course->name_en}")
            ->view('emails.course_ready');
    }
}
