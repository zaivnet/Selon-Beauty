<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Employee $employee) {
            if ($employee->outlet_id === null && ! array_key_exists('outlet_id', $employee->getAttributes())) {
                $defaultId = Outlet::where('code', 'PUSAT')->value('id') ?? Outlet::value('id');
                if ($defaultId) {
                    $employee->outlet_id = $defaultId;
                }
            }
        });
    }

    protected $attributes = [
        'attendance_enabled' => true,
    ];

    protected $fillable = [
        'employee_code',
        'full_name',
        'phone',
        'email',
        'job_title_id',
        'outlet_id',
        'join_date',
        'status',
        'attendance_enabled',
        'profile_photo_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'attendance_enabled' => 'boolean',
        ];
    }

    public function participatesInAttendance(): bool
    {
        return (bool) $this->attendance_enabled;
    }

    public function isCurrentAttendanceWorkforceMember(): bool
    {
        return $this->participatesInAttendance() && $this->user?->role !== 'superadmin';
    }

    public function scopeAttendanceEnabled(Builder $query): Builder
    {
        return $query->where('attendance_enabled', true);
    }

    public function scopeCurrentAttendanceWorkforce(Builder $query): Builder
    {
        return $query->attendanceEnabled()
            ->whereDoesntHave('user', fn (Builder $userQuery) => $userQuery->where('role', 'superadmin'));
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class, 'job_title_id');
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'employee_id');
    }

    public function overtimeSessions(): HasMany
    {
        return $this->hasMany(OvertimeSession::class);
    }
}
