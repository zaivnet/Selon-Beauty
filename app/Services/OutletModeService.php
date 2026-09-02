<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\Outlet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OutletModeService
{
    public const MODE_SINGLE = 'single';
    public const MODE_MULTI = 'multi';

    /**
     * Read-only resolution of current outlet mode.
     * Guaranteed side-effect free (does NOT write to database).
     */
    public function getMode(): string
    {
        $setting = AppSetting::get('outlet_mode');

        if ($setting === self::MODE_SINGLE || $setting === self::MODE_MULTI) {
            return $setting;
        }

        // Fail-safe read fallback:
        // If an existing installation has >= 2 active outlets, treat as multi.
        // If <= 1 active outlet, treat as single.
        $activeCount = Outlet::where('is_active', true)->count();

        return ($activeCount >= 2) ? self::MODE_MULTI : self::MODE_SINGLE;
    }

    /**
     * Check if application is in Single Outlet Mode.
     */
    public function isSingleOutlet(): bool
    {
        return $this->getMode() === self::MODE_SINGLE;
    }

    /**
     * Check if application is in Multi Outlet Mode.
     */
    public function isMultiOutlet(): bool
    {
        return $this->getMode() === self::MODE_MULTI;
    }

    /**
     * Get the single active operational outlet record.
     *
     * Rules:
     * - Exactly ONE active outlet -> returns that outlet.
     * - ZERO active outlets -> returns null.
     * - MORE THAN ONE active outlet -> returns null (unsafe/ambiguous state).
     *
     * Does NOT use any hardcoded company codes (e.g. 'PUSAT') or arbitrary selection.
     */
    public function getSingleOperationalOutlet(): ?Outlet
    {
        $activeOutlets = Outlet::where('is_active', true)->limit(2)->get();

        if ($activeOutlets->count() === 1) {
            return $activeOutlets->first();
        }

        return null;
    }

    /**
     * Explicit, idempotent initialization mechanism for application upgrade/bootstrap.
     * Persists setting ONLY if not already defined and active outlets exist.
     *
     * @param array|null $details Output metadata about initialization status.
     * @return string|null The resolved mode, or null if initialization was aborted (e.g. 0 active outlets).
     */
    public function initializeIfMissing(?array &$details = null): ?string
    {
        $existing = AppSetting::get('outlet_mode');
        $activeCount = Outlet::where('is_active', true)->count();

        if ($existing === self::MODE_SINGLE || $existing === self::MODE_MULTI) {
            $details = [
                'status' => 'already_configured',
                'mode' => $existing,
                'count' => $activeCount,
                'message' => "Outlet mode is already configured: [{$existing}]. No changes made.",
            ];
            return $existing;
        }

        if ($activeCount === 0) {
            $details = [
                'status' => 'no_active_outlets',
                'mode' => null,
                'count' => 0,
                'message' => 'Cannot initialize outlet mode: No active outlets detected. Please configure at least one active outlet first.',
            ];
            return null;
        }

        $mode = ($activeCount >= 2) ? self::MODE_MULTI : self::MODE_SINGLE;
        $label = ($activeCount === 1) ? '1 active outlet detected' : "{$activeCount} active outlets detected";

        AppSetting::set('outlet_mode', $mode, 'string', false);

        $details = [
            'status' => 'initialized',
            'mode' => $mode,
            'count' => $activeCount,
            'message' => "Outlet mode initialized: {$mode} ({$label})",
        ];

        return $mode;
    }

    /**
     * Server-side evaluation of blockers before switching Multi -> Single.
     *
     * @param string|null $blockerReason Output parameter detailing why switch is blocked.
     */
    public function canSwitchToSingleOutlet(?string &$blockerReason = null): bool
    {
        $activeOutlets = Outlet::where('is_active', true)->get();
        $activeCount = $activeOutlets->count();

        // Blocker 1: Must have active outlets
        if ($activeCount === 0) {
            $blockerReason = "Tidak ditemukan outlet aktif yang dapat dijadikan lokasi operasional tunggal.";
            return false;
        }

        // Blocker 2: Cannot have more than 1 active outlet
        if ($activeCount > 1) {
            $names = $activeOutlets->pluck('name')->implode(', ');
            $blockerReason = "Masih terdapat {$activeCount} outlet aktif ({$names}). Nonaktifkan atau hapus cabang tambahan terlebih dahulu.";
            return false;
        }

        $singleOutlet = $activeOutlets->first();
        if (! $singleOutlet) {
            $blockerReason = "Tidak ditemukan outlet aktif yang dapat dijadikan lokasi operasional tunggal.";
            return false;
        }

        // Blocker 3: Active employees belong to more than one HOME outlet
        $otherHomeEmployees = Employee::where('status', 'active')
            ->where('outlet_id', '!=', $singleOutlet->id)
            ->count();

        if ($otherHomeEmployees > 0) {
            $blockerReason = "Masih terdapat {$otherHomeEmployees} karyawan aktif yang terikat ke outlet selain {$singleOutlet->name}. Pindahkan seluruh karyawan ke {$singleOutlet->name} terlebih dahulu.";
            return false;
        }

        // Blocker 4: Future regular work schedules assigned to other outlets
        $today = Carbon::now(config('app.timezone', 'Asia/Jakarta'))->toDateString();

        $otherWorkSchedules = EmployeeSchedule::whereDate('work_date', '>=', $today)
            ->whereNotNull('work_outlet_id')
            ->where('work_outlet_id', '!=', $singleOutlet->id)
            ->count();

        if ($otherWorkSchedules > 0) {
            $blockerReason = "Masih terdapat {$otherWorkSchedules} jadwal kerja mendatang yang ditugaskan ke outlet selain {$singleOutlet->name}.";
            return false;
        }

        // Blocker 5: Future / active temporary schedule overrides assigned to other outlets
        $otherOverrides = EmployeeScheduleOverride::whereDate('date', '>=', $today)
            ->whereNotNull('work_outlet_id')
            ->where('work_outlet_id', '!=', $singleOutlet->id)
            ->count();

        if ($otherOverrides > 0) {
            $blockerReason = "Masih terdapat {$otherOverrides} penugasan khusus mendatang di outlet selain {$singleOutlet->name}.";
            return false;
        }

        return true;
    }

    /**
     * Atomically set outlet mode with validation and audit logging.
     *
     * @throws \InvalidArgumentException
     */
    public function setMode(string $mode, ?User $actor = null): bool
    {
        if (! in_array($mode, [self::MODE_SINGLE, self::MODE_MULTI], true)) {
            throw new \InvalidArgumentException("Mode outlet tidak valid: {$mode}");
        }

        $previousMode = $this->getMode();

        if ($mode === self::MODE_SINGLE) {
            $blockerReason = null;
            if (! $this->canSwitchToSingleOutlet($blockerReason)) {
                throw new \InvalidArgumentException("Gagal beralih ke Mode Single Outlet: {$blockerReason}");
            }
        }

        DB::transaction(function () use ($mode, $previousMode, $actor) {
            AppSetting::set('outlet_mode', $mode, 'string', false);

            if ($previousMode !== $mode) {
                AuditLog::log(
                    'setting.outlet_mode_updated',
                    null,
                    ['outlet_mode' => $previousMode],
                    ['outlet_mode' => $mode],
                    $actor,
                    "Perubahan Mode Operasional Outlet dari {$previousMode} ke {$mode}"
                );
            }
        });

        return true;
    }
}
