<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestRejectedNotification extends Notification
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
        $note = $this->leaveRequest->reviewer_note ? " Catatan: {$this->leaveRequest->reviewer_note}" : '';

        return [
            'type' => 'leave_rejected',
            'title' => 'Pengajuan Ditolak',
            'message' => "Pengajuan {$typeLabel} Anda untuk tanggal {$dateStr} ditolak.{$note}",
            'leave_request_id' => $this->leaveRequest->id,
            'target_url' => route('employee.leave-requests.index'),
            'icon' => 'x-circle',
        ];
    }
}
