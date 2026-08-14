<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'before_data',
        'after_data',
        'reason',
        'metadata',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'before_data' => 'array',
            'after_data' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function sanitizeData(?array $data): ?array
    {
        if (is_null($data)) {
            return null;
        }

        $sensitiveKeys = ['password', 'password_confirmation', 'remember_token', 'secret', 'api_token', 'token', 'app_key', 'db_password', 'mail_password'];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::sanitizeData($value);
            } elseif (in_array(strtolower($key), $sensitiveKeys, true)) {
                $data[$key] = '[REDACTED]';
            }
        }

        return $data;
    }

    public static function log(
        string $action,
        ?Model $model = null,
        ?array $before = null,
        ?array $after = null,
        ?User $user = null,
        ?string $reason = null,
        ?array $metadata = null,
    ): self {
        return self::create([
            'user_id' => $user?->id ?? request()->user()?->id,
            'action' => $action,
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id' => $model?->getKey(),
            'before_data' => self::sanitizeData($before),
            'after_data' => self::sanitizeData($after),
            'reason' => $reason ? trim($reason) : null,
            'metadata' => self::sanitizeData($metadata),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Audit log bersifat immutable.'));
        static::deleting(fn () => throw new \LogicException('Audit log bersifat immutable.'));
    }
}
