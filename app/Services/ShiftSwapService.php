<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeScheduleOverride;
use App\Models\LeaveRequest;
use App\Models\OvertimeSession;
use App\Models\Shift;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use App\Notifications\ShiftSwapNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftSwapService
{
    public function __construct(
        protected ?EffectiveScheduleService $effectiveService = null,
        protected ?AttendancePeriodService $periodService = null,
        protected ?OutletScopeService $outletScopeService = null,
    ) {
        $this->effectiveService = $effectiveService ?? new EffectiveScheduleService;
        $this->periodService = $periodService ?? new AttendancePeriodService;
        $this->outletScopeService = $outletScopeService ?? app(OutletScopeService::class);
    }

    public function submitRequest(Employee $requester, array $data): ShiftSwapRequest
    {
        $this->ensureReason($data['reason'] ?? '');

        return DB::transaction(function () use ($requester, $data) {
            $target = Employee::with('user')->findOrFail($data['target_employee_id']);
            $reqDate = $this->formatDate($data['requester_work_date']);
            $targetDate = $this->formatDate($data['target_work_date'] ?? $data['requester_work_date']);

            $resolved = $this->validateEligibilityAndConflicts($requester, $target, $reqDate, $targetDate);

            $swap = ShiftSwapRequest::create([
                'requester_employee_id' => $requester->id,
                'target_employee_id' => $target->id,
                'requester_work_date' => $reqDate,
                'target_work_date' => $targetDate,
                'requester_original_shift_id' => $resolved['requester_shift']->id,
                'target_original_shift_id' => $resolved['target_shift']->id,
                'requester_original_schedule_type' => 'work',
                'target_original_schedule_type' => 'work',
                'status' => ShiftSwapRequest::STATUS_PENDING_TARGET,
                'requester_reason' => trim($data['reason']),
            ]);

            $this->audit('shift_swap.requested', $swap, null, $swap->getAttributes(), trim($data['reason']), $requester->user);

            if ($target->user) {
                $target->user->notify(new ShiftSwapNotification($swap, 'requested'));
            }

            return $swap;
        });
    }

    public function respondByTarget(ShiftSwapRequest $swap, User $targetActor, string $action, ?string $reason = null): ShiftSwapRequest
    {
        if (! $swap->isPendingTarget()) {
            throw new \InvalidArgumentException('Permintaan tukar jadwal ini sudah tidak berada pada status menunggu rekan.');
        }

        if ($targetActor->employee_id !== $swap->target_employee_id) {
            throw new \InvalidArgumentException('Akses ditolak. Anda bukan karyawan tujuan dari permohonan tukar jadwal ini.');
        }

        return DB::transaction(function () use ($swap, $targetActor, $action, $reason) {
            $swap = ShiftSwapRequest::lockForUpdate()->findOrFail($swap->id);

            if ($action === 'reject') {
                $this->ensureReason($reason);
                $before = $swap->getAttributes();
                $swap->update([
                    'status' => ShiftSwapRequest::STATUS_REJECTED_BY_TARGET,
                    'target_responded_at' => now(),
                    'target_response_reason' => trim($reason),
                ]);

                $this->audit('shift_swap.target_rejected', $swap, $before, $swap->fresh()->getAttributes(), trim($reason), $targetActor);

                if ($swap->requester?->user) {
                    $swap->requester->user->notify(new ShiftSwapNotification($swap, 'target_rejected'));
                }

                return $swap->fresh();
            }

            if ($action === 'accept') {
                // Revalidate conflicts before accepting
                $this->validateEligibilityAndConflicts(
                    $swap->requester, $swap->target,
                    $swap->requester_work_date->format('Y-m-d'),
                    $swap->target_work_date->format('Y-m-d'),
                    $swap
                );

                $before = $swap->getAttributes();
                $swap->update([
                    'status' => ShiftSwapRequest::STATUS_PENDING_ADMIN,
                    'target_responded_at' => now(),
                    'target_response_reason' => $reason ? trim($reason) : null,
                ]);

                $this->audit('shift_swap.target_approved', $swap, $before, $swap->fresh()->getAttributes(), $reason ? trim($reason) : 'Disetujui rekan', $targetActor);

                if ($swap->requester?->user) {
                    $swap->requester->user->notify(new ShiftSwapNotification($swap, 'target_accepted'));
                }

                // Notify Admins / Owners
                $admins = $this->outletScopeService
                    ->scopeNotificationRecipientsForOutlet(User::query(), (int) $swap->requester->outlet_id, ['owner', 'superadmin'])
                    ->get();
                foreach ($admins as $admin) {
                    $admin->notify(new ShiftSwapNotification($swap, 'admin_pending'));
                }

                return $swap->fresh();
            }

            throw new \InvalidArgumentException('Aksi respon tidak valid.');
        });
    }

    public function respondByAdmin(ShiftSwapRequest $swap, User $adminActor, string $action, ?string $reason = null): ShiftSwapRequest
    {
        $this->ensureAdminRole($adminActor);

        if (! $swap->isPendingAdmin()) {
            throw new \InvalidArgumentException('Permintaan tukar jadwal ini tidak berada pada status menunggu admin.');
        }

        return DB::transaction(function () use ($swap, $adminActor, $action, $reason) {
            $swap = ShiftSwapRequest::lockForUpdate()->findOrFail($swap->id);

            if ($action === 'reject') {
                $this->ensureReason($reason);
                $before = $swap->getAttributes();
                $swap->update([
                    'status' => ShiftSwapRequest::STATUS_REJECTED_BY_ADMIN,
                    'admin_responded_at' => now(),
                    'admin_responded_by' => $adminActor->id,
                    'admin_response_reason' => trim($reason),
                ]);

                $this->audit('shift_swap.admin_rejected', $swap, $before, $swap->fresh()->getAttributes(), trim($reason), $adminActor);

                if ($swap->requester?->user) {
                    $swap->requester->user->notify(new ShiftSwapNotification($swap, 'admin_rejected'));
                }
                if ($swap->target?->user) {
                    $swap->target->user->notify(new ShiftSwapNotification($swap, 'admin_rejected'));
                }

                return $swap->fresh();
            }

            if ($action === 'approve') {
                if (! $swap->requester || ! $swap->target) {
                    throw ValidationException::withMessages([
                        'shift_swap' => 'Data karyawan pada permintaan tukar jadwal ini tidak ditemukan.',
                    ]);
                }

                // Rule P: Stale Schedule Check
                $reqEffective = $this->effectiveService->resolve($swap->requester, $swap->requester_work_date->format('Y-m-d'));
                $targetEffective = $this->effectiveService->resolve($swap->target, $swap->target_work_date->format('Y-m-d'));

                if (! $reqEffective['is_working_day'] || ! $targetEffective['is_working_day'] ||
                    $reqEffective['shift']?->id !== $swap->requester_original_shift_id ||
                    $targetEffective['shift']?->id !== $swap->target_original_shift_id) {
                    $swap->update(['status' => ShiftSwapRequest::STATUS_INVALIDATED]);
                    throw ValidationException::withMessages([
                        'shift_swap' => 'Jadwal telah berubah sejak permintaan dibuat. Permintaan tukar jadwal harus dibuat ulang.',
                    ]);
                }

                // Final Revalidation of all conflicts
                $this->validateEligibilityAndConflicts(
                    $swap->requester, $swap->target,
                    $swap->requester_work_date->format('Y-m-d'),
                    $swap->target_work_date->format('Y-m-d'),
                    $swap
                );

                $before = $swap->getAttributes();

                // Apply Override WORK for both employees
                EmployeeScheduleOverride::create([
                    'employee_id' => $swap->requester_employee_id,
                    'date' => $swap->requester_work_date->format('Y-m-d'),
                    'override_type' => 'work',
                    'shift_id' => $swap->target_original_shift_id,
                    'reason' => "Shift swap #{$swap->id}",
                    'created_by' => $adminActor->id,
                ]);

                EmployeeScheduleOverride::create([
                    'employee_id' => $swap->target_employee_id,
                    'date' => $swap->target_work_date->format('Y-m-d'),
                    'override_type' => 'work',
                    'shift_id' => $swap->requester_original_shift_id,
                    'reason' => "Shift swap #{$swap->id}",
                    'created_by' => $adminActor->id,
                ]);

                $swap->update([
                    'status' => ShiftSwapRequest::STATUS_APPROVED,
                    'approved_at' => now(),
                    'admin_responded_at' => now(),
                    'admin_responded_by' => $adminActor->id,
                    'admin_response_reason' => $reason ? trim($reason) : 'Disetujui admin',
                ]);

                $this->audit(
                    'shift_swap.admin_approved', $swap, $before, $swap->fresh()->getAttributes(),
                    $reason ? trim($reason) : 'Disetujui admin', $adminActor,
                    [
                        'before_requester_shift_id' => $swap->requester_original_shift_id,
                        'before_target_shift_id' => $swap->target_original_shift_id,
                        'after_requester_shift_id' => $swap->target_original_shift_id,
                        'after_target_shift_id' => $swap->requester_original_shift_id,
                    ]
                );

                if ($swap->requester?->user) {
                    $swap->requester->user->notify(new ShiftSwapNotification($swap, 'admin_approved'));
                }
                if ($swap->target?->user) {
                    $swap->target->user->notify(new ShiftSwapNotification($swap, 'admin_approved'));
                }

                return $swap->fresh();
            }

            throw new \InvalidArgumentException('Aksi admin tidak valid.');
        });
    }

    public function cancelRequest(ShiftSwapRequest $swap, User $actor): ShiftSwapRequest
    {
        if ($actor->employee_id !== $swap->requester_employee_id) {
            throw new \InvalidArgumentException('Akses ditolak. Hanya pemohon yang dapat membatalkan permintaan tukar jadwal.');
        }

        if ($swap->isApproved()) {
            throw new \InvalidArgumentException('Permintaan tukar jadwal yang sudah disetujui tidak dapat dibatalkan.');
        }

        if (! in_array($swap->status, [ShiftSwapRequest::STATUS_PENDING_TARGET, ShiftSwapRequest::STATUS_PENDING_ADMIN], true)) {
            throw new \InvalidArgumentException('Permintaan tukar jadwal ini sudah tidak dapat dibatalkan.');
        }

        return DB::transaction(function () use ($swap, $actor) {
            $swap = ShiftSwapRequest::lockForUpdate()->findOrFail($swap->id);
            $before = $swap->getAttributes();

            $swap->update(['status' => ShiftSwapRequest::STATUS_CANCELLED]);

            $this->audit('shift_swap.cancelled', $swap, $before, $swap->fresh()->getAttributes(), 'Dibatalkan pemohon', $actor);

            if ($swap->target?->user) {
                $swap->target->user->notify(new ShiftSwapNotification($swap, 'cancelled'));
            }

            return $swap->fresh();
        });
    }

    /**
     * Revalidates eligibility, period locks, date windows, attendance/leave/overtime conflicts, & active swap collisions.
     *
     * @return array{requester_shift:Shift, target_shift:Shift}
     *
     * @throws ValidationException
     */
    public function validateEligibilityAndConflicts(
        Employee $requester,
        Employee $target,
        string $reqDate,
        string $targetDate,
        ?ShiftSwapRequest $existingRequest = null
    ): array {
        // 1. Self Swap Check
        if ($requester->id === $target->id) {
            throw ValidationException::withMessages(['target_employee_id' => 'Anda tidak dapat mengajukan tukar jadwal dengan diri sendiri.']);
        }

        // 1b. Same Outlet Check
        if ($requester->outlet_id !== null && $target->outlet_id !== null && (int) $requester->outlet_id !== (int) $target->outlet_id) {
            throw ValidationException::withMessages(['target_employee_id' => 'Pertukaran jadwal hanya dapat dilakukan dengan karyawan pada outlet yang sama.']);
        }

        // 2. Active Employee & Attendance Enabled Check
        if ($requester->status !== 'active' || ! $requester->participatesInAttendance()) {
            throw ValidationException::withMessages(['requester' => 'Karyawan pemohon tidak aktif atau tidak terdaftar sebagai peserta presensi.']);
        }
        if ($target->status !== 'active' || ! $target->participatesInAttendance()) {
            throw ValidationException::withMessages(['target_employee_id' => 'Karyawan tujuan tidak aktif atau tidak terdaftar sebagai peserta presensi.']);
        }

        // 3. User Account Check
        if (! $requester->user || ! $requester->user->is_active) {
            throw ValidationException::withMessages(['requester' => 'Akun pengguna pemohon tidak aktif.']);
        }
        if (! $target->user || ! $target->user->is_active) {
            throw ValidationException::withMessages(['target_employee_id' => 'Akun pengguna karyawan tujuan tidak aktif.']);
        }

        // 4. Past Date Check
        $today = now(config('app.timezone'))->toDateString();
        if ($reqDate < $today || $targetDate < $today) {
            throw ValidationException::withMessages(['work_date' => 'Tukar jadwal tidak dapat diajukan untuk tanggal masa lalu.']);
        }

        // 5. Period Lock Check
        $this->periodService->assertPeriodOpen($reqDate, 'Periode kehadiran untuk jadwal tersebut sudah ditutup.');
        if ($reqDate !== $targetDate) {
            $this->periodService->assertPeriodOpen($targetDate, 'Periode kehadiran untuk jadwal tersebut sudah ditutup.');
        }

        // 6. Effective Schedule Resolution
        $reqEffective = $this->effectiveService->resolve($requester, $reqDate);
        $targetEffective = $this->effectiveService->resolve($target, $targetDate);

        if (! $reqEffective['is_working_day'] || ! $reqEffective['shift']) {
            throw ValidationException::withMessages(['requester_work_date' => 'Jadwal pemohon pada tanggal tersebut bukan shift kerja aktif (WORK).']);
        }
        if (! $targetEffective['is_working_day'] || ! $targetEffective['shift']) {
            throw ValidationException::withMessages(['target_work_date' => 'Jadwal karyawan tujuan pada tanggal tersebut bukan shift kerja aktif (WORK).']);
        }

        // 7. Manual Override Collision Check (Rule R)
        if ($reqEffective['source'] === 'employee_override' || $targetEffective['source'] === 'employee_override') {
            throw ValidationException::withMessages(['override' => 'Jadwal tanggal ini memiliki override khusus dan tidak dapat ditukar otomatis.']);
        }

        // 8. Same Day Shift Window Check (If work_date == today)
        $now = now(config('app.timezone'));
        if ($reqDate === $today) {
            $window = $this->effectiveService->displaySchedule($reqEffective);
            $shiftStart = Carbon::parse($reqDate.' '.$reqEffective['shift']->start_time, config('app.timezone'));
            if ($now->greaterThanOrEqualTo($shiftStart)) {
                throw ValidationException::withMessages(['work_date' => 'Shift pada hari ini sudah dimulai atau absensi sudah tercatat, tidak dapat ditukar.']);
            }
        }
        if ($targetDate === $today) {
            $shiftStart = Carbon::parse($targetDate.' '.$targetEffective['shift']->start_time, config('app.timezone'));
            if ($now->greaterThanOrEqualTo($shiftStart)) {
                throw ValidationException::withMessages(['work_date' => 'Shift pada hari ini sudah dimulai atau absensi sudah tercatat, tidak dapat ditukar.']);
            }
        }

        // 9. Attendance Conflict Check (Rule I)
        $reqAttExists = AttendanceRecord::where('employee_id', $requester->id)->whereDate('work_date', $reqDate)->exists();
        $targetAttExists = AttendanceRecord::where('employee_id', $target->id)->whereDate('work_date', $targetDate)->exists();
        if ($reqAttExists || $targetAttExists) {
            throw ValidationException::withMessages(['attendance' => 'Sudah ada data absensi pada tanggal tersebut.']);
        }

        // 10. Leave Conflict Check (Rule J)
        // Requester incoming targetDate leave check
        $reqLeaveExists = LeaveRequest::where('employee_id', $requester->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $targetDate)
            ->whereDate('end_date', '>=', $targetDate)
            ->exists();
        // Target incoming reqDate leave check
        $targetLeaveExists = LeaveRequest::where('employee_id', $target->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $reqDate)
            ->whereDate('end_date', '>=', $reqDate)
            ->exists();
        if ($reqLeaveExists || $targetLeaveExists) {
            throw ValidationException::withMessages(['leave' => 'Salah satu karyawan memiliki izin/cuti disetujui pada tanggal jadwal yang akan diterima.']);
        }

        // 11. Overtime Conflict Check (Rule K)
        $reqOtSession = OvertimeSession::where('employee_id', $requester->id)->whereDate('work_date', $reqDate)->where('status', 'active')->exists();
        $targetOtSession = OvertimeSession::where('employee_id', $target->id)->whereDate('work_date', $targetDate)->where('status', 'active')->exists();
        if ($reqOtSession || $targetOtSession) {
            throw ValidationException::withMessages(['overtime' => 'Terdapat sesi lembur aktif pada tanggal jadwal terkait.']);
        }

        // 12. Active Duplicate Swap Request Collision Check (Rule L)
        $activeStatuses = [ShiftSwapRequest::STATUS_PENDING_TARGET, ShiftSwapRequest::STATUS_PENDING_ADMIN];

        $reqCollision = ShiftSwapRequest::whereIn('status', $activeStatuses)
            ->when($existingRequest, fn ($q) => $q->where('id', '!=', $existingRequest->id))
            ->where(function ($q) use ($requester, $reqDate) {
                $q->where(fn ($q2) => $q2->where('requester_employee_id', $requester->id)->whereDate('requester_work_date', $reqDate))
                    ->orWhere(fn ($q2) => $q2->where('target_employee_id', $requester->id)->whereDate('target_work_date', $reqDate));
            })
            ->exists();

        $targetCollision = ShiftSwapRequest::whereIn('status', $activeStatuses)
            ->when($existingRequest, fn ($q) => $q->where('id', '!=', $existingRequest->id))
            ->where(function ($q) use ($target, $targetDate) {
                $q->where(fn ($q2) => $q2->where('requester_employee_id', $target->id)->whereDate('requester_work_date', $targetDate))
                    ->orWhere(fn ($q2) => $q2->where('target_employee_id', $target->id)->whereDate('target_work_date', $targetDate));
            })
            ->exists();

        if ($reqCollision || $targetCollision) {
            throw ValidationException::withMessages(['duplicate' => 'Karyawan sudah memiliki permintaan tukar jadwal aktif yang berjalan pada tanggal tersebut.']);
        }

        return [
            'requester_shift' => $reqEffective['shift'],
            'target_shift' => $targetEffective['shift'],
        ];
    }

    protected function formatDate(string|Carbon $date): string
    {
        return $date instanceof Carbon
            ? $date->format('Y-m-d')
            : Carbon::parse($date, config('app.timezone'))->format('Y-m-d');
    }

    protected function ensureReason(string $reason): void
    {
        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['reason' => 'Alasan wajib diisi minimal 5 karakter.']);
        }
    }

    protected function ensureAdminRole(User $actor): void
    {
        if (! in_array($actor->role, ['admin', 'owner', 'superadmin'], true)) {
            throw new \InvalidArgumentException('Akses ditolak. Anda tidak memiliki wewenang untuk memproses tukar jadwal.');
        }
    }

    protected function audit(string $action, ShiftSwapRequest $swap, ?array $before, ?array $after, string $reason, ?User $actor, array $extraMetadata = []): void
    {
        AuditLog::log(
            action: $action,
            model: $swap,
            before: $before,
            after: $after,
            user: $actor,
            reason: $reason,
            metadata: array_merge([
                'swap_id' => $swap->id,
                'requester_employee_id' => $swap->requester_employee_id,
                'target_employee_id' => $swap->target_employee_id,
                'requester_work_date' => $swap->requester_work_date->format('Y-m-d'),
                'target_work_date' => $swap->target_work_date->format('Y-m-d'),
                'status' => $swap->status,
            ], $extraMetadata)
        );
    }
}
