<?php

namespace App\Notifications;

use App\Models\ShiftSwapRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ShiftSwapNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ShiftSwapRequest $swap,
        public string $event, // 'requested', 'target_accepted', 'target_rejected', 'admin_approved', 'admin_rejected', 'cancelled'
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $reqName = $this->swap->requester?->full_name ?? 'Karyawan';
        $targetName = $this->swap->target?->full_name ?? 'Rekan Kerja';
        $reqDate = $this->swap->requester_work_date->format('d M Y');

        [$title, $message, $targetUrl] = match ($this->event) {
            'requested' => [
                'Permintaan Tukar Jadwal',
                "{$reqName} mengajukan tukar jadwal dengan Anda untuk tanggal {$reqDate}.",
                route('employee.shift-swaps.index', ['tab' => 'incoming']),
            ],
            'target_accepted' => [
                'Tukar Jadwal Disetujui Rekan',
                "{$targetName} menyetujui tukar jadwal tanggal {$reqDate}. Menunggu persetujuan Admin.",
                route('employee.shift-swaps.index'),
            ],
            'target_rejected' => [
                'Tukar Jadwal Ditolak Rekan',
                "{$targetName} menolak permintaan tukar jadwal tanggal {$reqDate}.",
                route('employee.shift-swaps.index'),
            ],
            'admin_pending' => [
                'Permintaan Tukar Jadwal Menunggu Admin',
                "Permintaan tukar jadwal antara {$reqName} dan {$targetName} tanggal {$reqDate} membutuhkan persetujuan Anda.",
                route('admin.shift-swaps.index', ['status' => 'pending_admin']),
            ],
            'admin_approved' => [
                'Tukar Jadwal Disetujui Admin',
                "Pertukaran jadwal antara {$reqName} dan {$targetName} untuk tanggal {$reqDate} telah disetujui.",
                route('employee.shift-swaps.index'),
            ],
            'admin_rejected' => [
                'Tukar Jadwal Ditolak Admin',
                "Permintaan tukar jadwal tanggal {$reqDate} ditolak oleh Admin.",
                route('employee.shift-swaps.index'),
            ],
            'cancelled' => [
                'Permintaan Tukar Jadwal Dibatalkan',
                "Permintaan tukar jadwal tanggal {$reqDate} telah dibatalkan oleh {$reqName}.",
                route('employee.shift-swaps.index'),
            ],
            default => [
                'Update Tukar Jadwal',
                "Status tukar jadwal tanggal {$reqDate} diperbarui menjadi {$this->swap->status_label}.",
                route('employee.shift-swaps.index'),
            ],
        };

        return [
            'type' => 'shift_swap_'.$this->event,
            'title' => $title,
            'message' => $message,
            'target_url' => $targetUrl,
            'shift_swap_request_id' => $this->swap->id,
        ];
    }
}
