<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(public LeaveRequest $leaveRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $empName = $this->leaveRequest->employee?->full_name ?? 'Karyawan';
        $typeLabel = $this->leaveRequest->type_label;
        $sDate = $this->leaveRequest->start_date->format('d/m/Y');
        $eDate = $this->leaveRequest->end_date->format('d/m/Y');
        $dateStr = $sDate === $eDate ? $sDate : "{$sDate}–{$eDate}";

        return [
            'type' => 'leave_submitted',
            'title' => 'Pengajuan Izin/Cuti Baru',
            'message' => "{$empName} mengajukan {$typeLabel} untuk tanggal {$dateStr}.",
            'leave_request_id' => $this->leaveRequest->id,
            'employee_name' => $empName,
            'target_url' => route('admin.leave-requests.index', ['employee_id' => $this->leaveRequest->employee_id]),
            'icon' => 'calendar',
        ];
    }
}
