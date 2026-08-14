<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\EmployeeSchedule;
use App\Models\LeaveRequest;
use Carbon\Carbon;

class AttendanceStatusResolver
{
    /**
     * Resolve the operational status for an employee schedule / attendance record context.
     */
    public function resolve(
        ?EmployeeSchedule $schedule,
        ?AttendanceRecord $record = null,
        ?LeaveRequest $approvedLeave = null,
        ?Carbon $nowServerTime = null
    ): array {
        $timezone = config('app.timezone');
        $now = $nowServerTime ? $nowServerTime->copy()->timezone($timezone) : Carbon::now($timezone);

        // Priority 1: Schedule type OFF
        if ($schedule && $schedule->schedule_type === 'off') {
            return [
                'key' => 'off',
                'label' => 'OFF',
                'badge_class' => 'bg-slate-100 text-slate-600 border-slate-200',
            ];
        }

        // Priority 2: Schedule type HOLIDAY
        if ($schedule && $schedule->schedule_type === 'holiday') {
            return [
                'key' => 'holiday',
                'label' => 'LIBUR',
                'badge_class' => 'bg-purple-50 text-purple-700 border-purple-200',
            ];
        }

        // Priority 3: Approved Leave (Permission, Sick, Leave)
        if ($approvedLeave) {
            $type = strtolower($approvedLeave->type);
            $label = match ($type) {
                'sick' => 'SAKIT',
                'permission' => 'IZIN',
                'leave' => 'CUTI',
                default => strtoupper($type),
            };

            return [
                'key' => $type,
                'label' => $label,
                'badge_class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            ];
        }

        // Priority 4: Existing Attendance Record
        if ($record) {
            if ($record->check_in_at !== null || in_array($record->status, ['present', 'late'], true)) {
                if ($record->status === 'late' || (int) $record->late_minutes > 0) {
                    return [
                        'key' => 'late',
                        'label' => 'TERLAMBAT',
                        'badge_class' => 'bg-amber-50 text-amber-700 border-amber-200',
                    ];
                }

                return [
                    'key' => 'present',
                    'label' => 'HADIR',
                    'badge_class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                ];
            }

            if (in_array($record->status, ['sick', 'permission', 'leave'], true)) {
                $type = strtolower($record->status);
                $label = match ($type) {
                    'sick' => 'SAKIT',
                    'permission' => 'IZIN',
                    'leave' => 'CUTI',
                    default => strtoupper($type),
                };

                return [
                    'key' => $type,
                    'label' => $label,
                    'badge_class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                ];
            }
        }

        // Priority 5: Schedule WORK + No Attendance + No Approved Leave
        if ($schedule && $schedule->schedule_type === 'work') {
            $shift = $schedule->shift;
            $workDateStr = $schedule->work_date->format('Y-m-d');

            if ($shift) {
                $window = $this->calculateCheckInWindow($workDateStr, $shift);
                $openTime = $window['open_time'];
                $closeTime = $window['close_time'];

                if ($now->lt($openTime)) {
                    return [
                        'key' => 'not_started',
                        'label' => 'JADWAL BELUM DIMULAI',
                        'badge_class' => 'bg-slate-100 text-slate-600 border-slate-200',
                    ];
                }

                if ($now->lte($closeTime)) {
                    return [
                        'key' => 'pending',
                        'label' => 'BELUM CHECK-IN',
                        'badge_class' => 'bg-amber-50 text-amber-700 border-amber-200',
                    ];
                }

                // $now > $closeTime -> TIDAK HADIR
                return [
                    'key' => 'absent',
                    'label' => 'TIDAK HADIR',
                    'badge_class' => 'bg-rose-100 text-rose-800 border-rose-300 font-extrabold',
                ];
            }

            // Fallback if shift model is not loaded/missing
            $todayStr = $now->format('Y-m-d');
            if ($workDateStr < $todayStr) {
                return [
                    'key' => 'absent',
                    'label' => 'TIDAK HADIR',
                    'badge_class' => 'bg-rose-100 text-rose-800 border-rose-300 font-extrabold',
                ];
            }

            return [
                'key' => 'pending',
                'label' => 'BELUM CHECK-IN',
                'badge_class' => 'bg-amber-50 text-amber-700 border-amber-200',
            ];
        }

        // Priority 6: No Schedule
        return [
            'key' => 'unknown',
            'label' => 'BELUM DITETAPKAN',
            'badge_class' => 'bg-slate-100 text-slate-500 border-slate-200',
        ];
    }

    /**
     * Calculate check-in window open and close timestamps dynamically in Asia/Jakarta.
     *
     * @param  string  $workDateStr  (Y-m-d)
     * @return array ['open_time' => Carbon, 'close_time' => Carbon, 'start_time' => Carbon, 'end_time' => Carbon]
     */
    public function calculateCheckInWindow(string $workDateStr, mixed $shift): array
    {
        $startTimeStr = is_object($shift) ? $shift->start_time : $shift['start_time'];
        $endTimeStr = is_object($shift) ? $shift->end_time : $shift['end_time'];
        $openBeforeMins = (int) (is_object($shift) ? ($shift->check_in_open_minutes_before ?? 0) : ($shift['check_in_open_minutes_before'] ?? 0));
        $closeAfterMins = is_object($shift) ? $shift->check_in_close_minutes_after : ($shift['check_in_close_minutes_after'] ?? null);

        // Normalize time strings H:i:s -> H:i
        $startFormatted = substr((string) $startTimeStr, 0, 5);
        $endFormatted = substr((string) $endTimeStr, 0, 5);

        $timezone = config('app.timezone');
        $shiftStart = Carbon::parse("{$workDateStr} {$startFormatted}:00", $timezone);

        // Handle cross-midnight for shiftEnd
        if ($endFormatted <= $startFormatted) {
            $shiftEnd = Carbon::parse("{$workDateStr} {$endFormatted}:00", $timezone)->addDay();
        } else {
            $shiftEnd = Carbon::parse("{$workDateStr} {$endFormatted}:00", $timezone);
        }

        $openTime = (clone $shiftStart)->subMinutes($openBeforeMins);

        if ($closeAfterMins !== null && $closeAfterMins !== '') {
            $closeTime = (clone $shiftStart)->addMinutes((int) $closeAfterMins);
        } else {
            // Fallback to shift end_time
            $closeTime = clone $shiftEnd;
        }

        return [
            'open_time' => $openTime,
            'close_time' => $closeTime,
            'start_time' => $shiftStart,
            'end_time' => $shiftEnd,
        ];
    }
}
