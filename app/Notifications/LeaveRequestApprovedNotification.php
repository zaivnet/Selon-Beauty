<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public LeaveRequest $leaveRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $typeLabel = $this->leaveRequest->type_label;
        $sDate = $this->leaveRequest->start_date->format('d/m/Y');
        $eDate = $this->leaveRequest->end_date->format('d/m/Y');
        $dateStr = $sDate === $eDate ? $sDate : "{$sDate}–{$eDate}";

        return [
            'type' => 'leave_approved',
            'title' => 'Pengajuan Disetujui',
            'message' => "Pengajuan {$typeLabel} Anda untuk tanggal {$dateStr} telah disetujui.",
            'leave_request_id' => $this->leaveRequest->id,
            'target_url' => route('employee.leave-requests.index'),
            'icon' => 'check-circle',
        ];
    }
}
