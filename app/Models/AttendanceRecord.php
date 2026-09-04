<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $table = 'attendance_records';

    protected $fillable = [
        'employee_id',
        'work_schedule_id',
        'work_date',
        'attendance_location_id',
        'outlet_id',
        'status',
        'check_in_at',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_accuracy_meters',
        'check_in_distance_meters',
        'check_in_selfie_path',
        'check_in_ip',
        'check_in_user_agent',
        'check_out_at',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_accuracy_meters',
        'check_out_distance_meters',
        'check_out_selfie_path',
        'check_out_ip',
        'check_out_user_agent',
        'checkout_source',
        'auto_checkout_boundary',
        'scheduled_shift_end_at',
        'break_minutes_snapshot',
        'late_minutes',
        'early_leave_minutes',
        'worked_minutes',
        'overtime_minutes',
        'is_manually_adjusted',
        'corrected_at',
        'corrected_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'auto_checkout_boundary' => 'datetime',
            'scheduled_shift_end_at' => 'datetime',
            'break_minutes_snapshot' => 'integer',
            'check_in_latitude' => 'decimal:7',
            'check_in_longitude' => 'decimal:7',
            'check_in_accuracy_meters' => 'decimal:2',
            'check_in_distance_meters' => 'decimal:2',
            'check_out_latitude' => 'decimal:7',
            'check_out_longitude' => 'decimal:7',
            'check_out_accuracy_meters' => 'decimal:2',
            'check_out_distance_meters' => 'decimal:2',
            'late_minutes' => 'integer',
            'early_leave_minutes' => 'integer',
            'worked_minutes' => 'integer',
            'overtime_minutes' => 'integer',
            'is_manually_adjusted' => 'boolean',
            'corrected_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id')->withTrashed();
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(EmployeeSchedule::class, 'work_schedule_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(AttendanceLocation::class, 'attendance_location_id');
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlet_id')->withTrashed();
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }
}
