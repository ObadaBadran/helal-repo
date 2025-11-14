<?php

namespace App\Mail;

use App\Models\Consultation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConsultationReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $consultation;
    public $joinUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Consultation $consultation, $joinUrl)
    {
        $this->consultation = $consultation;
        $this->joinUrl = $joinUrl;
    }

    public function build()
    {
        return $this->subject('Reminder of the upcoming consultation')
            ->view('emails.consultation_reminder')
            ->with([
                'consultation' => $this->consultation,
                'joinUrl' => $this->joinUrl
            ]);
    }
}
