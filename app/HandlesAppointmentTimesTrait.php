<?php

namespace App;

use App\Models\Appointment;
use App\Models\Availability;
use Carbon\Carbon;
use Illuminate\Http\Request;

trait HandlesAppointmentTimesTrait
{
    /**
     * التحقق من أن اليوم والوقت ضمن جدول الإتاحة
     */
    public function checkAvailabilityForDay($date, $start_time, $end_time)
    {
        $dayName = Carbon::parse($date)->format('l');

        $availability = Availability::where('day', $dayName)
            ->where('start_time', '<=', $start_time)
            ->where('end_time', '>=', $end_time)
            ->first();

        if (!$availability) {
            return false;
        }

        return true;
    }

    /**
     * التحقق من وجود تضارب بين الموعد الجديد والمواعيد الحالية
     */
    public function checkAppointmentConflict($date, $start_time, $end_time, $appointmentId = 0)
    {
        $date = Carbon::parse($date)->format('Y-m-d');

        $conflict = Appointment::whereNotIn('id', [$appointmentId])
            ->whereDate('date', $date)
            ->where(function ($query) use ($start_time, $end_time) {
                $query->whereBetween('start_time', [$start_time, $end_time])
                    ->orWhereBetween('end_time', [$start_time, $end_time])
                    ->orWhere(function ($q) use ($start_time, $end_time) {
                        $q->where('start_time', '<=', $start_time)
                            ->where('end_time', '>=', $end_time);
                    });
            })
            ->exists();

        if ($conflict) {
            return false;
        }

        return true;
    }
}
