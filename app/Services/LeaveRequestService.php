<?php

namespace App\Services;

use App\Events\LeaveRequestApproved;
use App\Events\LeaveRequestCancelled;
use App\Events\LeaveRequestRejected;
use App\Events\LeaveRequestSubmitted;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\LeaveRequestApprovedNotification;
use App\Notifications\LeaveRequestRejectedNotification;
use App\Notifications\LeaveRequestSubmittedNotification;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LeaveRequestService
{
    public function __construct(protected ?EffectiveScheduleService $effectiveScheduleService = null)
    {
        $this->effectiveScheduleService = $effectiveScheduleService ?? new EffectiveScheduleService;
    }

    /**
     * Submit a new permission, sick, or leave request for an employee.
     */
    public function submitRequest(Employee $employee, array $data, ?UploadedFile $attachment = null): LeaveRequest
    {
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];
        $type = strtolower($data['type']);
        $reason = trim($data['reason']);

        // 1. Date Range Order Validation
        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            throw ValidationException::withMessages([
                'end_date' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
            ]);
        }

        // 2. Single Day OFF / Holiday Validation (Requirement 18 & 19)
        if ($startDate === $endDate) {
            $effective = $this->effectiveScheduleService->resolve($employee, $startDate);
            if ($effective['source'] !== 'none' && ! $effective['is_working_day']) {
                throw ValidationException::withMessages([
                    'start_date' => "Tanggal {$startDate} adalah hari libur, tidak memerlukan pengajuan izin/cuti.",
                ]);
            }
        }

        // 3. Overlap Detection against active requests (Requirement 5)
        $hasOverlap = LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereDate('start_date', '<=', $endDate)
                    ->whereDate('end_date', '>=', $startDate);
            })
            ->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'start_date' => 'Anda sudah memiliki pengajuan izin/cuti aktif yang tumpang tindih pada rentang tanggal tersebut.',
            ]);
        }

        // 4. Secure Private Attachment Upload (Requirement 8 & 9)
        $attachmentPath = null;
        if ($attachment) {
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
            if (! in_array($attachment->getMimeType(), $allowedMimes, true)) {
                throw ValidationException::withMessages([
                    'attachment' => 'Format lampiran harus berupa gambar (JPEG, PNG, WebP) atau PDF.',
                ]);
            }

            if ($attachment->getSize() > 5 * 1024 * 1024) {
                throw ValidationException::withMessages([
                    'attachment' => 'Ukuran lampiran maksimal 5 MB.',
                ]);
            }

            $dateObj = Carbon::parse($startDate);
            $year = $dateObj->format('Y');
            $month = $dateObj->format('m');
            $ext = strtolower($attachment->getClientOriginalExtension() ?: 'bin');
            $uuidName = (string) Str::uuid().'.'.$ext;

            $directory = "leave-attachments/{$employee->id}/{$year}/{$month}";
            $attachmentPath = Storage::disk('local')->putFileAs($directory, $attachment, $uuidName);
        }

        // 5. DB Transaction & Event Dispatch
        return DB::transaction(function () use ($employee, $type, $startDate, $endDate, $reason, $attachmentPath) {
            $leaveRequest = LeaveRequest::create([
                'employee_id' => $employee->id,
                'type' => $type,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => $reason,
                'attachment_path' => $attachmentPath,
                'status' => 'pending',
            ]);

            AuditLog::log('leave.submitted', $leaveRequest, null, $leaveRequest->toArray());
            LeaveRequestSubmitted::dispatch($leaveRequest);

            // Send In-App Notifications to Owner & Admin
            $recipients = User::whereIn('role', ['owner', 'admin'])->where('is_active', true)->get();
            foreach ($recipients as $recipient) {
                $recipient->notify(new LeaveRequestSubmittedNotification($leaveRequest));
            }

            return $leaveRequest;
        });
    }

    /**
     * Cancel a pending leave request by employee.
     */
    public function cancelRequest(LeaveRequest $request, User $user): LeaveRequest
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Hanya pengajuan berstatus Menunggu (Pending) yang dapat dibatalkan.',
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

            AuditLog::log('leave.cancelled', $request, $before, $request->toArray(), $user);
            LeaveRequestCancelled::dispatch($request);

            return $request;
        });
    }

    /**
     * Approve a pending leave request by Owner/Admin.
     */
    public function approveRequest(LeaveRequest $request, User $reviewer, ?string $note = null): LeaveRequest
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Hanya pengajuan berstatus Menunggu (Pending) yang dapat disetujui.',
            ]);
        }

        return DB::transaction(function () use ($request, $reviewer, $note) {
            $before = $request->toArray();
            $request->update([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => Carbon::now('Asia/Jakarta'),
                'reviewer_note' => $note,
            ]);

            AuditLog::log('leave.approved', $request, $before, $request->toArray(), $reviewer);
            LeaveRequestApproved::dispatch($request);

            // Send In-App Notification to Employee
            $empUser = $request->employee?->user;
            if ($empUser) {
                $empUser->notify(new LeaveRequestApprovedNotification($request));
            }

            return $request;
        });
    }

    /**
     * Reject a pending leave request by Owner/Admin.
     */
    public function rejectRequest(LeaveRequest $request, User $reviewer, string $note): LeaveRequest
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Hanya pengajuan berstatus Menunggu (Pending) yang dapat ditolak.',
            ]);
        }

        if (empty(trim($note))) {
            throw ValidationException::withMessages([
                'reviewer_note' => 'Alasan penolakan wajib diisi agar karyawan mengetahui alasannya.',
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

            AuditLog::log('leave.rejected', $request, $before, $request->toArray(), $reviewer);
            LeaveRequestRejected::dispatch($request);

            // Send In-App Notification to Employee
            $empUser = $request->employee?->user;
            if ($empUser) {
                $empUser->notify(new LeaveRequestRejectedNotification($request));
            }

            return $request;
        });
    }
}
