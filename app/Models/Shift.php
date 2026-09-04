<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'start_time',
        'end_time',
        'check_in_open_minutes_before',
        'check_in_close_minutes_after',
        'check_out_open_minutes_before',
        'grace_period_minutes',
        'break_minutes',
        'crosses_midnight',
        'auto_checkout_enabled',
        'auto_checkout_grace_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'check_in_open_minutes_before' => 'integer',
            'check_in_close_minutes_after' => 'integer',
            'check_out_open_minutes_before' => 'integer',
            'grace_period_minutes' => 'integer',
            'break_minutes' => 'integer',
            'crosses_midnight' => 'boolean',
            'auto_checkout_enabled' => 'boolean',
            'auto_checkout_grace_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Calculate gross work duration in minutes, correctly handling cross-midnight shifts.
     */
    public function getWorkDurationMinutesAttribute(): int
    {
        if (! $this->start_time || ! $this->end_time) {
            return 0;
        }

        $start = Carbon::createFromTimeString($this->start_time);
        $end = Carbon::createFromTimeString($this->end_time);

        if ($end->lessThanOrEqualTo($start) || $this->crosses_midnight) {
            $end->addDay();
        }

        return (int) $start->diffInMinutes($end);
    }

    /**
     * Calculate net work duration in minutes (Gross work duration - break minutes).
     */
    public function getNetWorkDurationMinutesAttribute(): int
    {
        $gross = $this->work_duration_minutes;
        $net = $gross - ($this->break_minutes ?? 0);

        return max(0, $net);
    }

    /**
     * Return formatted work time range string (e.g. 09:00 - 17:00 or 22:00 - 06:00 (+1)).
     */
    public function getFormattedWorkHoursAttribute(): string
    {
        $startStr = substr($this->start_time, 0, 5);
        $endStr = substr($this->end_time, 0, 5);

        if ($this->crosses_midnight) {
            return "{$startStr} - {$endStr} (+1 hr)";
        }

        return "{$startStr} - {$endStr}";
    }

    /**
     * Check whether this shift is currently assigned to any employee schedules.
     */
    public function hasSchedules(): bool
    {
        if (class_exists(\App\Models\EmployeeSchedule::class)) {
            return \App\Models\EmployeeSchedule::where('shift_id', $this->id)->exists();
        }

        return false;
    }
}
