<?php

namespace App\Jobs;

use App\Mail\ConsultationReminderMail;
use App\Models\Consultation;
use App\Models\Meeting;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendConsultationReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $consultationId;

    /**
     * Create a new job instance.
     */
    public function __construct($consultationId)
    {
        $this->consultationId = $consultationId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $consultation = Consultation::findOrFail($this->consultationId);

        $roomName = 'meeting_' . Str::random(10);
        $meetUrl = "https://meet.jit.si/{$roomName}";

        $roomId = basename($meetUrl);
        $studentJoinUrl = config('services.meet_url.web') . $roomId;
        $adminJoinUrl = config('services.meet_url.dash') . $roomId;

        $consultation->update([
            'meet_url' => $meetUrl
        ]);

        Mail::to($consultation->user->email)
            ->send(new ConsultationReminderMail($consultation, $studentJoinUrl));

        Mail::to(config('services.admin.address'))
            ->send(new ConsultationReminderMail($consultation, $adminJoinUrl));
    }
}
