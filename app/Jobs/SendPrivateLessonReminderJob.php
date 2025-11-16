<?php

namespace App\Jobs;

use App\Mail\ConsultationReminderMail;
use App\Mail\PrivateLessonReminderMail;
use App\Models\Enroll;
use App\Models\PrivateLessonInformation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPrivateLessonReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, \Illuminate\Bus\Queueable, SerializesModels;

    public $enrollmentId;

    /**
     * Create a new job instance.
     */
    public function __construct($enrollmentId)
    {
        $this->enrollmentId = $enrollmentId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $enrollment = Enroll::findOrFail($this->enrollmentId);

        Mail::to($enrollment->user->email)
            ->send(new PrivateLessonReminderMail($enrollment->privateLessonInformation));

        Mail::to(config('services.admin.address'))
            ->send(new PrivateLessonReminderMail($enrollment->privateLessonInformation));
    }
}
