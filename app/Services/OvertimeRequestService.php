<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Notifications\OvertimeRequestApprovedNotification;
use App\Notifications\OvertimeRequestRejectedNotification;
use App\Notifications\OvertimeRequestSubmittedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OvertimeRequestService
{
    public function __construct(protected ?EffectiveScheduleService $effectiveScheduleService = null)
    {
        $this->effectiveScheduleService = $effectiveScheduleService ?? new EffectiveScheduleService;
    }

    /**
     * Submit a new overtime request for an employee.
     */
    public function submitRequest(Employee $employee, array $data): OvertimeRequest
    {
        if (! $employee->isCurrentAttendanceWorkforceMember()) {
            throw ValidationException::withMessages([
                'attendance' => 'Akun Anda tidak terdaftar sebagai peserta sistem kehadiran.',
            ]);
        }

        $workDate = $data['work_date'];
        $requestedMinutes = (int) $data['requested_minutes'];
        $reason = trim($data['reason']);

        // 1. Requested Minutes Validation
        if ($requestedMinutes <= 0) {
            throw ValidationException::withMessages([
                'requested_minutes' => 'Durasi lembur harus lebih besar dari 0 menit.',
            ]);
        }

        // 2. Work Schedule Validation (Requirement 3)
        $effective = $this->effectiveScheduleService->resolve($employee, $workDate);
        if ($effective['source'] === 'none') {
            throw ValidationException::withMessages([
                'work_date' => "Tanggal {$workDate} belum memiliki konteks jadwal atau kalender kerja.",
            ]);
        }

        // 3. Duplicate Active Request Validation
        $hasActive = OvertimeRequest::where('employee_id', $employee->id)
            ->whereDate('work_date', $workDate)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($hasActive) {
            throw ValidationException::withMessages([
                'work_date' => "Anda sudah memiliki pengajuan lembur aktif pada tanggal {$workDate}.",
            ]);
        }

        // 4. DB Transaction & Audit Log
        return DB::transaction(function () use ($employee, $workDate, $requestedMinutes, $reason) {
            $overtimeRequest = OvertimeRequest::create([
                'employee_id' => $employee->id,
                'work_date' => $workDate,
                'requested_minutes' => $requestedMinutes,
                'approved_minutes' => null,
                'reason' => $reason,
                'status' => 'pending',
            ]);

            AuditLog::log('overtime.submitted', $overtimeRequest, null, $overtimeRequest->toArray());

            // Send In-App Notifications to Owner & Admin
            $recipients = User::whereIn('role', ['owner', 'admin'])->where('is_active', true)->get();
            foreach ($recipients as $recipient) {
                $recipient->notify(new OvertimeRequestSubmittedNotification($overtimeRequest));
            }

            return $overtimeRequest;
        });
    }

    /**
     * Cancel a pending overtime request by employee.
     */
    public function cancelRequest(OvertimeRequest $request, User $user): OvertimeRequest
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Hanya pengajuan lembur berstatus Menunggu (Pending) yang dapat dibatalkan.',
            ]);
        }

        if ($user->role === 'employee' && $request->employee_id !== $user->employee_id) {
            throw ValidationException::withMessages([
                'auth' => 'Anda tidak memiliki hak untuk membatalkan pengajuan ini.',
            ]);
        }

        return DB::transaction(function () use ($request, $user) {
            $before = $request->toArray();
            $request->update(['status' => 'cancelled']);

            AuditLog::log('overtime.cancelled', $request, $before, $request->toArray(), $user);

            return $request;
        });
    }

    /**
     * Approve a pending overtime request by Owner/Admin.
     */
    public function approveRequest(OvertimeRequest $request, User $reviewer, int $approvedMinutes, ?string $note = null): OvertimeRequest
    {
        if ($reviewer->role === 'employee' || ($reviewer->employee_id && $reviewer->employee_id === $request->employee_id)) {
            throw ValidationException::withMessages([
                'reviewer' => 'Karyawan tidak diperbolehkan menyetujui pengajuan lembur milik sendiri.',
            ]);
        }

        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Hanya pengajuan lembur berstatus Menunggu (Pending) yang dapat disetujui.',
            ]);
        }

        if ($approvedMinutes < 0) {
            throw ValidationException::withMessages([
                'approved_minutes' => 'Durasi lembur disetujui tidak boleh negatif.',
            ]);
        }

        if ($approvedMinutes > $request->requested_minutes) {
            throw ValidationException::withMessages([
                'approved_minutes' => 'Durasi lembur disetujui tidak boleh melebihi durasi yang diajukan ('.$request->requested_minutes.' menit).',
            ]);
        }

        return DB::transaction(function () use ($request, $reviewer, $approvedMinutes, $note) {
            $before = $request->toArray();
            $request->update([
                'status' => 'approved',
                'approved_minutes' => $approvedMinutes,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => Carbon::now('Asia/Jakarta'),
                'reviewer_note' => $note,
            ]);

            AuditLog::log('overtime.approved', $request, $before, $request->toArray(), $reviewer);

            // Send In-App Notification to Employee
            $empUser = $request->employee?->user;
            if ($empUser) {
                $empUser->notify(new OvertimeRequestApprovedNotification($request));
            }

            return $request;
        });
    }

    /**
     * Reject a pending overtime request by Owner/Admin.
     */
    public function rejectRequest(OvertimeRequest $request, User $reviewer, string $note): OvertimeRequest
    {
        if ($reviewer->role === 'employee' || ($reviewer->employee_id && $reviewer->employee_id === $request->employee_id)) {
            throw ValidationException::withMessages([
                'reviewer' => 'Karyawan tidak diperbolehkan menolak pengajuan lembur milik sendiri.',
            ]);
        }

        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Hanya pengajuan lembur berstatus Menunggu (Pending) yang dapat ditolak.',
            ]);
        }

        if (empty(trim($note))) {
            throw ValidationException::withMessages([
                'reviewer_note' => 'Alasan penolakan wajib diisi.',
            ]);
        }

        return DB::transaction(function () use ($request, $reviewer, $note) {
            $before = $request->toArray();
            $request->update([
                'status' => 'rejected',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => Carbon::now('Asia/Jakarta'),
                'reviewer_note' => trim($note),
            ]);

            AuditLog::log('overtime.rejected', $request, $before, $request->toArray(), $reviewer);

            // Send In-App Notification to Employee
            $empUser = $request->employee?->user;
            if ($empUser) {
                $empUser->notify(new OvertimeRequestRejectedNotification($request));
            }

            return $request;
        });
    }
}
