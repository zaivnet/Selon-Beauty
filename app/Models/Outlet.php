<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Outlet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'address',
        'latitude',
        'longitude',
        'radius_meters',
        'max_accuracy_meters',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'radius_meters' => 'integer',
            'max_accuracy_meters' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'outlet_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'outlet_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'outlet_id');
    }

    public function assignedAdmins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'admin_outlet_assignments')
            ->withTimestamps();
    }
}
