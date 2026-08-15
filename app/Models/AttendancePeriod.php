<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendancePeriod extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'year',
        'month',
        'status',
        'closed_at',
        'closed_by',
        'reopened_at',
        'reopened_by',
        'close_reason',
        'reopen_reason',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'closed_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function isOpen(): bool
    {
        return strtolower((string) $this->status) === self::STATUS_OPEN;
    }

    public function isClosed(): bool
    {
        return strtolower((string) $this->status) === self::STATUS_CLOSED;
    }
}
