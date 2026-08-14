<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'overtime_request_id', 'employee_id', 'work_schedule_id', 'work_date', 'status',
        'check_in_at', 'check_out_at', 'check_in_latitude', 'check_in_longitude',
        'check_in_accuracy_meters', 'check_in_distance_meters', 'check_out_latitude',
        'check_out_longitude', 'check_out_accuracy_meters', 'check_out_distance_meters',
        'check_in_selfie_path', 'check_out_selfie_path', 'actual_minutes', 'credited_minutes',
        'started_at', 'completed_at',
        'corrected_at', 'corrected_by', 'completed_by_user_id', 'completion_source',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date', 'check_in_at' => 'datetime', 'check_out_at' => 'datetime',
            'started_at' => 'datetime', 'completed_at' => 'datetime',
            'corrected_at' => 'datetime',
            'check_in_latitude' => 'decimal:7', 'check_in_longitude' => 'decimal:7',
            'check_in_accuracy_meters' => 'decimal:2', 'check_in_distance_meters' => 'decimal:2',
            'check_out_latitude' => 'decimal:7', 'check_out_longitude' => 'decimal:7',
            'check_out_accuracy_meters' => 'decimal:2', 'check_out_distance_meters' => 'decimal:2',
            'actual_minutes' => 'integer', 'credited_minutes' => 'integer',
        ];
    }

    public function overtimeRequest(): BelongsTo
    {
        return $this->belongsTo(OvertimeRequest::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(EmployeeSchedule::class, 'work_schedule_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }

    public function getRemainingApprovedMinutesAttribute(): int
    {
        $elapsed = $this->isActive() ? $this->runningMinutes() : (int) $this->actual_minutes;

        return max(0, (int) $this->overtimeRequest?->approved_minutes - $elapsed);
    }

    public static function formatMinutes(?int $minutes): string
    {
        $minutes = max(0, (int) $minutes);
        $hours = intdiv($minutes, 60);
        $remainder = $minutes % 60;

        return $hours > 0 ? "{$hours}j {$remainder}m" : "{$remainder}m";
    }

    public function runningMinutes(?CarbonInterface $now = null): int
    {
        if (! $this->check_in_at) {
            return 0;
        }

        return max(0, (int) floor($this->check_in_at->diffInMinutes($this->check_out_at ?? $now ?? now(config('app.timezone')), false)));
    }
}
