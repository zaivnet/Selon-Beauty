<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendancePeriod;
use App\Models\AuditLog;
use App\Models\OvertimeSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendancePeriodService
{
    public function getOrCreatePeriod(int $year, int $month): AttendancePeriod
    {
        $this->validateYearMonth($year, $month);

        return AttendancePeriod::firstOrCreate(
            ['year' => $year, 'month' => $month],
            ['status' => AttendancePeriod::STATUS_OPEN]
        );
    }

    public function getPeriodForDate(string|Carbon $date): AttendancePeriod
    {
        $carbon = $this->parseDate($date);

        return $this->getOrCreatePeriod($carbon->year, $carbon->month);
    }

    public function isOpen(string|Carbon|int $dateOrYear, ?int $month = null): bool
    {
        if (is_numeric($dateOrYear) && $month !== null) {
            $period = $this->getOrCreatePeriod((int) $dateOrYear, $month);

            return $period->isOpen();
        }

        /** @var string|Carbon $dateOrYear */
        $period = $this->getPeriodForDate($dateOrYear);

        return $period->isOpen();
    }

    public function isClosed(string|Carbon|int $dateOrYear, ?int $month = null): bool
    {
        return ! $this->isOpen($dateOrYear, $month);
    }

    /**
     * Assert that the period for the given date is open. Throws ValidationException if closed.
     *
     * @throws ValidationException
     */
    public function assertPeriodOpen(string|Carbon $date, ?string $customMessage = null): void
    {
        $carbon = $this->parseDate($date);
        $period = $this->getOrCreatePeriod($carbon->year, $carbon->month);

        if ($period->isClosed()) {
            $formattedDate = $carbon->locale('id')->translatedFormat('d F Y');
            $message = $customMessage ?? "Periode kehadiran untuk tanggal ini ({$formattedDate}) sudah ditutup.";

            throw ValidationException::withMessages([
                'attendance_period' => $message,
            ]);
        }
    }

    /**
     * Validate whether a monthly attendance period is eligible to be closed.
     *
     * @return array{is_eligible: bool, issues: array<int, string>, details: array<string, int>}
     */
    public function validateCloseEligibility(int $year, int $month): array
    {
        $this->validateYearMonth($year, $month);
        $timezone = config('app.timezone');
        $start = Carbon::create($year, $month, 1, 0, 0, 0, $timezone)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $startDateStr = $start->toDateString();
        $endDateStr = $end->toDateString();

        // 1. Missing Checkouts (check-in present, check-out null)
        $missingCheckoutCount = AttendanceRecord::whereDate('work_date', '>=', $startDateStr)
            ->whereDate('work_date', '<=', $endDateStr)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->count();

        // 2. Active Overtime Sessions
        $activeOvertimeCount = OvertimeSession::whereDate('work_date', '>=', $startDateStr)
            ->whereDate('work_date', '<=', $endDateStr)
            ->where('status', 'active')
            ->count();

        $issues = [];
        if ($missingCheckoutCount > 0) {
            $issues[] = "terdapat {$missingCheckoutCount} presensi yang belum check-out";
        }
        if ($activeOvertimeCount > 0) {
            $issues[] = "terdapat {$activeOvertimeCount} sesi lembur yang masih aktif";
        }

        $isEligible = empty($issues);

        return [
            'is_eligible' => $isEligible,
            'issues' => $issues,
            'details' => [
                'missing_checkout_count' => $missingCheckoutCount,
                'active_overtime_count' => $activeOvertimeCount,
            ],
        ];
    }

    /**
     * Close a monthly attendance period.
     *
     * @throws ValidationException|\InvalidArgumentException
     */
    public function closePeriod(int $year, int $month, User $actor, string $reason): AttendancePeriod
    {
        $this->validateRolePermission($actor);

        $reason = trim($reason);
        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages([
                'close_reason' => 'Alasan penutupan periode wajib diisi minimal 5 karakter.',
            ]);
        }

        return DB::transaction(function () use ($year, $month, $actor, $reason): AttendancePeriod {
            // Ensure record exists & lock row
            $period = $this->getOrCreatePeriod($year, $month);
            $lockedPeriod = AttendancePeriod::whereKey($period->id)->lockForUpdate()->firstOrFail();

            // Idempotency: if already closed, return without duplicate log
            if ($lockedPeriod->isClosed()) {
                return $lockedPeriod;
            }

            // Check eligibility before closing
            $eligibility = $this->validateCloseEligibility($year, $month);
            if (! $eligibility['is_eligible']) {
                $issueText = implode(' dan ', $eligibility['issues']);
                $message = "Periode belum dapat ditutup karena masih {$issueText}.";

                throw ValidationException::withMessages([
                    'close_period' => $message,
                ]);
            }

            $before = $lockedPeriod->toArray();
            $now = Carbon::now(config('app.timezone'));

            $lockedPeriod->update([
                'status' => AttendancePeriod::STATUS_CLOSED,
                'closed_at' => $now,
                'closed_by' => $actor->id,
                'close_reason' => $reason,
            ]);

            $after = $lockedPeriod->fresh()->toArray();

            AuditLog::log(
                action: 'attendance_period.closed',
                model: $lockedPeriod,
                before: $before,
                after: $after,
                user: $actor,
                reason: $reason,
                metadata: [
                    'year' => $year,
                    'month' => $month,
                    'period_key' => sprintf('%04d-%02d', $year, $month),
                ]
            );

            return $lockedPeriod->fresh(['closedBy', 'reopenedBy']);
        });
    }

    /**
     * Reopen a closed monthly attendance period.
     *
     * @throws ValidationException|\InvalidArgumentException
     */
    public function reopenPeriod(int $year, int $month, User $actor, string $reason): AttendancePeriod
    {
        $this->validateRolePermission($actor);

        $reason = trim($reason);
        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages([
                'reopen_reason' => 'Alasan pembukaan kembali periode wajib diisi minimal 5 karakter.',
            ]);
        }

        return DB::transaction(function () use ($year, $month, $actor, $reason): AttendancePeriod {
            $period = $this->getOrCreatePeriod($year, $month);
            $lockedPeriod = AttendancePeriod::whereKey($period->id)->lockForUpdate()->firstOrFail();

            // Idempotency: if already open, return without duplicate log
            if ($lockedPeriod->isOpen()) {
                return $lockedPeriod;
            }

            $before = $lockedPeriod->toArray();
            $now = Carbon::now(config('app.timezone'));

            $lockedPeriod->update([
                'status' => AttendancePeriod::STATUS_OPEN,
                'reopened_at' => $now,
                'reopened_by' => $actor->id,
                'reopen_reason' => $reason,
            ]);

            $after = $lockedPeriod->fresh()->toArray();

            AuditLog::log(
                action: 'attendance_period.reopened',
                model: $lockedPeriod,
                before: $before,
                after: $after,
                user: $actor,
                reason: $reason,
                metadata: [
                    'year' => $year,
                    'month' => $month,
                    'period_key' => sprintf('%04d-%02d', $year, $month),
                ]
            );

            return $lockedPeriod->fresh(['closedBy', 'reopenedBy']);
        });
    }

    protected function validateRolePermission(User $actor): void
    {
        if (! in_array($actor->role, ['superadmin', 'owner'], true)) {
            throw new \InvalidArgumentException('Akses ditolak. Penutupan/pembukaan periode hanya dapat dilakukan oleh Owner dan Superadmin.');
        }
    }

    protected function validateYearMonth(int $year, int $month): void
    {
        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            throw new \InvalidArgumentException('Tahun dan bulan periode tidak valid.');
        }
    }

    protected function parseDate(string|Carbon $date): Carbon
    {
        $timezone = config('app.timezone');

        return $date instanceof Carbon
            ? $date->copy()->timezone($timezone)
            : Carbon::parse($date, $timezone);
    }
}
