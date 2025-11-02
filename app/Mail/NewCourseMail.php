<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewCourseMail extends Mailable
{
    use Queueable, SerializesModels;

    public $course;
    public $user;
    public $courseUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($course, $user, $courseUrl = null)
    {
        $this->course = $course;
        $this->user = $user;

        $this->courseUrl = $courseUrl ?? env('FRONTEND_URL') . '/courses/' . $course->id;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $isArabic = $this->user->locale === 'ar';

        return $this->subject($isArabic ? 'تم إضافة كورس جديد' : 'New Course Available')
                    ->view('emails.new_course')
                    ->with([
                        'course' => $this->course,
                        'isArabic' => $isArabic,
                        'courseUrl' => $this->courseUrl,
                    ]);
    }
}
