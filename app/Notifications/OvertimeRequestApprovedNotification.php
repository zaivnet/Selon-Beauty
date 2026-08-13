<?php

namespace App\Notifications;

use App\Models\OvertimeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OvertimeRequestApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public OvertimeRequest $overtimeRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $wDate = $this->overtimeRequest->work_date->format('d/m/Y');
        $approvedMins = $this->overtimeRequest->approved_minutes;

        return [
            'type' => 'overtime_approved',
            'title' => 'Pengajuan Lembur Disetujui',
            'message' => "Pengajuan Lembur Anda pada {$wDate} telah disetujui ({$approvedMins} menit).",
            'overtime_request_id' => $this->overtimeRequest->id,
            'target_url' => route('employee.overtime-requests.index'),
            'icon' => 'check-circle',
        ];
    }
}
