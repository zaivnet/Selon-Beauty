<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftSwapRequest extends Model
{
    public const STATUS_PENDING_TARGET = 'pending_target';

    public const STATUS_PENDING_ADMIN = 'pending_admin';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED_BY_TARGET = 'rejected_by_target';

    public const STATUS_REJECTED_BY_ADMIN = 'rejected_by_admin';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_INVALIDATED = 'invalidated';

    protected $fillable = [
        'requester_employee_id',
        'target_employee_id',
        'requester_work_date',
        'target_work_date',
        'requester_original_shift_id',
        'target_original_shift_id',
        'requester_original_schedule_type',
        'target_original_schedule_type',
        'status',
        'requester_reason',
        'target_responded_at',
        'target_response_reason',
        'admin_responded_at',
        'admin_responded_by',
        'admin_response_reason',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'requester_work_date' => 'date',
            'target_work_date' => 'date',
            'target_responded_at' => 'datetime',
            'admin_responded_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requester_employee_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'target_employee_id');
    }

    public function requesterShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'requester_original_shift_id');
    }

    public function targetShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'target_original_shift_id');
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_responded_by');
    }

    public function isPendingTarget(): bool
    {
        return $this->status === self::STATUS_PENDING_TARGET;
    }

    public function isPendingAdmin(): bool
    {
        return $this->status === self::STATUS_PENDING_ADMIN;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return in_array($this->status, [self::STATUS_REJECTED_BY_TARGET, self::STATUS_REJECTED_BY_ADMIN], true);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isInvalidated(): bool
    {
        return $this->status === self::STATUS_INVALIDATED;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_TARGET => 'Menunggu Rekan',
            self::STATUS_PENDING_ADMIN => 'Menunggu Admin',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED_BY_TARGET => 'Ditolak Rekan',
            self::STATUS_REJECTED_BY_ADMIN => 'Ditolak Admin',
            self::STATUS_CANCELLED => 'Dibatalkan',
            self::STATUS_INVALIDATED => 'Kadaluarsa / Berubah',
            default => strtoupper($this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_TARGET => 'border-amber-200 bg-amber-50 text-amber-900',
            self::STATUS_PENDING_ADMIN => 'border-indigo-200 bg-indigo-50 text-indigo-900',
            self::STATUS_APPROVED => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            self::STATUS_REJECTED_BY_TARGET, self::STATUS_REJECTED_BY_ADMIN => 'border-rose-200 bg-rose-50 text-rose-900',
            self::STATUS_CANCELLED, self::STATUS_INVALIDATED => 'border-slate-200 bg-slate-100 text-slate-700',
            default => 'border-slate-200 bg-slate-100 text-slate-700',
        };
    }
}
