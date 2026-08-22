<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\CustomResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['employee_id', 'outlet_id', 'outlet_access_mode', 'name', 'email', 'phone', 'password', 'role', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'outlet_access_mode' => 'selected',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if ($user->outlet_id === null && ! array_key_exists('outlet_id', $user->getAttributes())) {
                try {
                    $defaultId = Outlet::where('code', 'PUSAT')->value('id') ?? Outlet::value('id');
                    if ($defaultId) {
                        $user->outlet_id = $defaultId;
                    }
                } catch (\Throwable $e) {
                }
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id')->withTrashed();
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    public function assignedOutlets(): BelongsToMany
    {
        return $this->belongsToMany(Outlet::class, 'admin_outlet_assignments')
            ->withTimestamps();
    }

    public function getEffectiveOutletId(): ?int
    {
        return $this->outlet_id ?? $this->employee?->outlet_id;
    }

    /**
     * Send the password reset notification using custom notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPasswordNotification($token));
    }
}
