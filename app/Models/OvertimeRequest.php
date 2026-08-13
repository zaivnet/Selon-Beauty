<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OvertimeRequest extends Model
{
    use HasFactory;

    protected $table = 'overtime_requests';

    protected $fillable = [
        'employee_id',
        'work_date',
        'requested_minutes',
        'approved_minutes',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'reviewer_note',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'requested_minutes' => 'integer',
            'approved_minutes' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function session(): HasOne
    {
        return $this->hasOne(OvertimeSession::class);
    }

    public function isStartDateValid(?EmployeeSchedule $schedule = null, ?Carbon $now = null): bool
    {
        $today = ($now ?? Carbon::now(config('app.timezone')))->copy()->startOfDay();
        $workDate = $this->work_date->copy()->startOfDay();

        return $today->equalTo($workDate)
            || ($schedule?->shift?->crosses_midnight && $today->equalTo($workDate->copy()->addDay()));
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
            'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
            'cancelled' => 'bg-slate-100 text-slate-600 border-slate-200',
            default => 'bg-slate-100 text-slate-600 border-slate-200',
        };
    }

    public function getFormattedRequestedDurationAttribute(): string
    {
        $hours = (int) floor($this->requested_minutes / 60);
        $mins = $this->requested_minutes % 60;
        if ($hours > 0 && $mins > 0) {
            return "{$hours}j {$mins}m ({$this->requested_minutes} menit)";
        } elseif ($hours > 0) {
            return "{$hours} jam ({$this->requested_minutes} menit)";
        }

        return "{$this->requested_minutes} menit";
    }

    public function getFormattedApprovedDurationAttribute(): ?string
    {
        if ($this->approved_minutes === null) {
            return null;
        }

        $hours = (int) floor($this->approved_minutes / 60);
        $mins = $this->approved_minutes % 60;
        if ($hours > 0 && $mins > 0) {
            return "{$hours}j {$mins}m ({$this->approved_minutes} menit)";
        } elseif ($hours > 0) {
            return "{$hours} jam ({$this->approved_minutes} menit)";
        }

        return "{$this->approved_minutes} menit";
    }
}
