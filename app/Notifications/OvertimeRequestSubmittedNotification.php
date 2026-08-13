<?php

namespace App\Notifications;

use App\Models\OvertimeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OvertimeRequestSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(public OvertimeRequest $overtimeRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $empName = $this->overtimeRequest->employee?->full_name ?? 'Karyawan';
        $wDate = $this->overtimeRequest->work_date->format('d/m/Y');
        $mins = $this->overtimeRequest->requested_minutes;

        return [
            'type' => 'overtime_submitted',
            'title' => 'Pengajuan Lembur Baru',
            'message' => "{$empName} mengajukan Lembur {$mins} menit pada {$wDate}.",
            'overtime_request_id' => $this->overtimeRequest->id,
            'employee_name' => $empName,
            'target_url' => route('admin.overtime-requests.index', ['employee_id' => $this->overtimeRequest->employee_id]),
            'icon' => 'clock',
        ];
    }
}
