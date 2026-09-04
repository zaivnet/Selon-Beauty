<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Shift;
use App\Notifications\AutoCheckoutNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoCheckoutService
{
    /**
     * Process all open attendance records eligible for automatic shift checkout.
     *
     * @return array{processed: int, checked_out: int, skipped: int, errors: int, details: array<int, mixed>}
     */
    public function process(?Carbon $referenceTime = null): array
    {
        $timezone = (string) config('app.timezone', 'Asia/Jakarta');
        $now = $referenceTime ? $referenceTime->copy()->timezone($timezone) : Carbon::now($timezone);

        $results = [
            'processed' => 0,
            'checked_out' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => [],
        ];

        // Retrieve candidate open attendance records using indexed check_out_at + auto_checkout_boundary
        // Query strictly selects unclosed check-ins that have an immutable snapshot boundary due on or before $now
        $candidateQuery = AttendanceRecord::with(['employee.user', 'schedule.shift', 'outlet'])
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->whereNotNull('auto_checkout_boundary')
            ->where('auto_checkout_boundary', '<=', $now)
            ->orderBy('id', 'asc');

        $candidateQuery->chunkById(100, function ($records) use (&$results, $now, $timezone) {
            foreach ($records as $record) {
                $results['processed']++;

                try {
                    $status = $this->processRecord($record, $now, $timezone);

                    if ($status['action'] === 'checked_out') {
                        $results['checked_out']++;
                    } else {
                        $results['skipped']++;
                    }

                    $results['details'][] = [
                        'record_id' => $record->id,
                        'employee_id' => $record->employee_id,
                        'work_date' => $record->work_date instanceof Carbon ? $record->work_date->toDateString() : substr((string) $record->work_date, 0, 10),
                        'action' => $status['action'],
                        'reason' => $status['reason'],
                    ];
                } catch (\Throwable $e) {
                    $results['errors']++;
                    Log::error('Error during auto checkout processing for record ID '.$record->id.': '.$e->getMessage(), [
                        'record_id' => $record->id,
                        'employee_id' => $record->employee_id,
                        'exception' => $e,
                    ]);

                    $results['details'][] = [
                        'record_id' => $record->id,
                        'employee_id' => $record->employee_id,
                        'action' => 'error',
                        'reason' => $e->getMessage(),
                    ];
                }
            }
        });

        return $results;
    }

    /**
     * Process an individual attendance record safely within a database transaction.
     *
     * @return array{action: string, reason: string}
     */
    public function processRecord(AttendanceRecord $record, Carbon $now, string $timezone): array
    {
        $employee = $record->employee;
        if (! $employee) {
            return ['action' => 'skipped', 'reason' => 'Employee not found'];
        }

        // SNAPSHOT-ONLY RULE: If auto_checkout_boundary snapshot is null, skip unconditionally.
        if (! $record->auto_checkout_boundary) {
            return ['action' => 'skipped', 'reason' => 'Auto checkout boundary snapshot is unavailable'];
        }

        $workDateStr = $record->work_date instanceof Carbon
            ? $record->work_date->toDateString()
            : substr((string) $record->work_date, 0, 10);

        $shift = $record->schedule?->shift;
        $autoCheckoutBoundary = $record->auto_checkout_boundary->copy()->timezone($timezone);

        // Boundary not yet reached
        if ($now->lessThan($autoCheckoutBoundary)) {
            return ['action' => 'skipped', 'reason' => 'Auto checkout boundary not reached yet'];
        }

        // Execute atomic update with row locking
        return DB::transaction(function () use ($record, $shift, $workDateStr, $autoCheckoutBoundary, $timezone, $now) {
            /** @var AttendanceRecord|null $lockedRecord */
            $lockedRecord = AttendanceRecord::where('id', $record->id)
                ->whereNotNull('check_in_at')
                ->whereNull('check_out_at')
                ->whereNotNull('auto_checkout_boundary')
                ->lockForUpdate()
                ->first();

            if (! $lockedRecord || ! $lockedRecord->check_in_at || ! $lockedRecord->auto_checkout_boundary) {
                return ['action' => 'skipped', 'reason' => 'Attendance already checked out or missing boundary snapshot'];
            }

            // INCOMPLETE SNAPSHOT GUARD: scheduled_shift_end_at and break_minutes_snapshot must be present
            if ($lockedRecord->scheduled_shift_end_at === null || $lockedRecord->break_minutes_snapshot === null) {
                Log::warning("Incomplete auto checkout snapshots on AttendanceRecord ID {$lockedRecord->id}. Skipping auto checkout without guessing mutable shift values.", [
                    'record_id' => $lockedRecord->id,
                    'employee_id' => $lockedRecord->employee_id,
                    'scheduled_shift_end_at' => $lockedRecord->scheduled_shift_end_at,
                    'break_minutes_snapshot' => $lockedRecord->break_minutes_snapshot,
                ]);

                return ['action' => 'skipped', 'reason' => 'Incomplete metric snapshots (scheduled_shift_end_at or break_minutes_snapshot is missing)'];
            }

            // Boundary re-verification under row lock
            $lockedBoundary = $lockedRecord->auto_checkout_boundary->copy()->timezone($timezone);
            if ($now->lessThan($lockedBoundary)) {
                return ['action' => 'skipped', 'reason' => 'Auto checkout boundary not reached yet'];
            }

            $beforeData = $lockedRecord->toArray();

            // Calculate worked duration deterministically using immutable boundary snapshot and check-in time
            $checkInTime = Carbon::createFromFormat('Y-m-d H:i:s', $lockedRecord->check_in_at->format('Y-m-d H:i:s'), $timezone);
            $grossMinutes = max(0, (int) floor($checkInTime->diffInMinutes($lockedBoundary, false)));
            $breakMinutes = (int) $lockedRecord->break_minutes_snapshot;
            $workedMinutes = max(0, $grossMinutes - $breakMinutes);

            // Shift end calculation using immutable scheduled_shift_end_at snapshot
            $scheduledEnd = Carbon::createFromFormat('Y-m-d H:i:s', $lockedRecord->scheduled_shift_end_at->format('Y-m-d H:i:s'), $timezone);

            $earlyLeaveMinutes = $lockedBoundary->lessThan($scheduledEnd)
                ? max(0, (int) floor($lockedBoundary->diffInMinutes($scheduledEnd, false)))
                : 0;

            $overtimeMinutes = $lockedBoundary->greaterThan($scheduledEnd)
                ? max(0, (int) floor($scheduledEnd->diffInMinutes($lockedBoundary, false)))
                : 0;

            // Deterministic update using immutable boundary snapshot
            $lockedRecord->update([
                'check_out_at' => $lockedBoundary,
                'checkout_source' => 'auto_shift_end',
                'worked_minutes' => $workedMinutes,
                'early_leave_minutes' => $earlyLeaveMinutes,
                'overtime_minutes' => $overtimeMinutes,
            ]);

            $afterData = $lockedRecord->fresh()->toArray();

            // Record audit trail
            AuditLog::log(
                action: 'attendance.auto_checkout',
                model: $lockedRecord,
                before: $beforeData,
                after: $afterData,
                user: null,
                reason: 'Automatic checkout after shift grace period',
                metadata: [
                    'employee_id' => $lockedRecord->employee_id,
                    'work_date' => $workDateStr,
                    'shift' => $shift?->name ?? 'Unknown',
                    'shift_code' => $shift?->code ?? 'UNKNOWN',
                    'auto_checkout_time' => $lockedBoundary->toDateTimeString(),
                    'grace_minutes' => (int) ($shift?->auto_checkout_grace_minutes ?? 10),
                    'source' => 'auto_shift_end',
                    'actor' => 'system',
                ]
            );

            // Send system notification to employee user safely after transaction commits
            DB::afterCommit(function () use ($lockedRecord, $shift, $lockedBoundary) {
                $lockedRecord->loadMissing('employee.user');
                $user = $lockedRecord->employee?->user;
                if ($user) {
                    $targetUrl = route('employee.dashboard', ['attendance' => $lockedRecord->id]);
                    $user->notify(new AutoCheckoutNotification(
                        shiftName: $shift?->name ?? 'Shift Kerja',
                        checkoutTime: $lockedBoundary->format('H:i'),
                        targetUrl: $targetUrl,
                    ));
                }
            });

            return [
                'action' => 'checked_out',
                'reason' => "Checked out automatically at {$lockedBoundary->toDateTimeString()}",
            ];
        });
    }

    /**
     * Resolve the exact, deterministic auto checkout boundary datetime for a given shift and work date.
     */
    public function resolveAutoCheckoutBoundary(Shift $shift, string $workDate, ?string $timezone = null): Carbon
    {
        $tz = $timezone ?? (string) config('app.timezone', 'Asia/Jakarta');

        $shiftStart = Carbon::parse($workDate.' '.$shift->start_time, $tz);
        $shiftEnd = Carbon::parse($workDate.' '.$shift->end_time, $tz);

        if ($shift->crosses_midnight || $shiftEnd->lessThanOrEqualTo($shiftStart)) {
            $shiftEnd->addDay();
        }

        $graceMinutes = (int) ($shift->auto_checkout_grace_minutes ?? 10);

        return $shiftEnd->copy()->addMinutes($graceMinutes);
    }
}
