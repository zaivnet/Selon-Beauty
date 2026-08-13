<?php

namespace App\Notifications;

use App\Models\OvertimeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OvertimeRequestRejectedNotification extends Notification
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
        $note = $this->overtimeRequest->reviewer_note ? " Catatan: {$this->overtimeRequest->reviewer_note}" : '';

        return [
            'type' => 'overtime_rejected',
            'title' => 'Pengajuan Lembur Ditolak',
            'message' => "Pengajuan Lembur Anda pada {$wDate} ditolak.{$note}",
            'overtime_request_id' => $this->overtimeRequest->id,
            'target_url' => route('employee.overtime-requests.index', ['highlight' => $this->overtimeRequest->id]).'#overtime-'.$this->overtimeRequest->id,
            'icon' => 'x-circle',
        ];
    }
}
