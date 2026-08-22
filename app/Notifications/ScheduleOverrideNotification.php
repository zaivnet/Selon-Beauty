<?php

namespace App\Notifications;

use App\Models\EmployeeScheduleOverride;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ScheduleOverrideNotification extends Notification
{
    use Queueable;

    public function __construct(public EmployeeScheduleOverride $override, public string $event = 'updated') {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $date = $this->override->date->format('d/m/Y');
        $isWork = $this->override->override_type === 'work';
        $outletName = $this->override->workOutlet?->name;
        $outletInfo = $outletName ? " di {$outletName}" : '';
        $message = $this->event === 'deleted'
            ? "Override penugasan jadwal {$date} dibatalkan. Jadwal efektif kembali mengikuti kalender/regular schedule."
            : ($isWork
                ? "Anda dijadwalkan bekerja pada {$date} — Shift {$this->override->shift?->name}{$outletInfo}."
                : "Jadwal {$date} diubah menjadi Libur Khusus.");

        return [
            'type' => 'schedule_override_'.$this->event,
            'title' => 'Jadwal Anda berubah',
            'message' => $message,
            'target_url' => route('employee.schedules.index', ['start_date' => $this->override->date->copy()->startOfWeek()->format('Y-m-d')]),
            'icon' => 'calendar',
        ];
    }
}
