<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeOutletTransfer;
use App\Models\Outlet;
use App\Models\OvertimeSession;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EmployeeTransferService
{
    public function __construct(
        protected OutletScopeService $outletScopeService,
    ) {}

    /**
     * Transfer an employee permanently to a new destination outlet with audit history and safety checks.
     *
     * @throws ValidationException|AccessDeniedHttpException
     */
    public function transferOutlet(Employee $employee, Outlet $destinationOutlet, User $actor, ?string $notes = null): EmployeeOutletTransfer
    {
        // 1. Enforce Superadmin / Owner permission
        if (! $this->outletScopeService->isGlobalScope($actor)) {
            throw new AccessDeniedHttpException('Hanya Owner atau Administrator yang dapat memindahkan outlet karyawan.');
        }

        // 2. Validate destination outlet status
        if ($destinationOutlet->trashed() || ! $destinationOutlet->is_active) {
            throw ValidationException::withMessages([
                'destination_outlet_id' => ['Outlet tujuan tidak aktif.'],
            ]);
        }

        if ($employee->outlet_id === $destinationOutlet->id) {
            throw ValidationException::withMessages([
                'destination_outlet_id' => ['Karyawan sudah berada di outlet tersebut.'],
            ]);
        }

        $todayStr = Carbon::now(config('app.timezone'))->toDateString();

        // 3. Blocker Check 1: Active Attendance Session (checked in today, not checked out)
        $activeAttendanceExists = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('work_date', $todayStr)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->exists();

        if ($activeAttendanceExists) {
            throw ValidationException::withMessages([
                'employee_id' => ['Karyawan masih memiliki sesi presensi aktif hari ini. Lakukan check-out terlebih dahulu sebelum memindahkan outlet.'],
            ]);
        }

        // 4. Blocker Check 2: Active Overtime Session
        $activeOvertimeExists = OvertimeSession::where('employee_id', $employee->id)
            ->where(function ($q) {
                $q->where('status', 'in_progress')
                    ->orWhere(function ($subQ) {
                        $subQ->whereNotNull('check_in_at')
                            ->whereNull('check_out_at')
                            ->where('status', '!=', 'cancelled');
                    });
            })
            ->exists();

        if ($activeOvertimeExists) {
            throw ValidationException::withMessages([
                'employee_id' => ['Karyawan sedang memiliki sesi lembur aktif. Selesaikan sesi lembur terlebih dahulu.'],
            ]);
        }

        // 5. Blocker Check 3: Pending Shift Swap Request
        $pendingSwapExists = ShiftSwapRequest::where(function ($q) use ($employee) {
            $q->where('requester_employee_id', $employee->id)
                ->orWhere('target_employee_id', $employee->id);
        })
            ->whereIn('status', [ShiftSwapRequest::STATUS_PENDING_TARGET, ShiftSwapRequest::STATUS_PENDING_ADMIN])
            ->exists();

        if ($pendingSwapExists) {
            throw ValidationException::withMessages([
                'employee_id' => ['Karyawan memiliki permintaan tukar jadwal yang masih menunggu. Selesaikan atau batalkan permintaan tersebut sebelum memindahkan outlet.'],
            ]);
        }

        // 6. Execute atomic transfer transaction
        return DB::transaction(function () use ($employee, $destinationOutlet, $actor, $notes, $todayStr) {
            $fromOutletId = $employee->outlet_id;

            $transfer = EmployeeOutletTransfer::create([
                'employee_id' => $employee->id,
                'from_outlet_id' => $fromOutletId,
                'to_outlet_id' => $destinationOutlet->id,
                'effective_date' => $todayStr,
                'notes' => $notes,
                'transferred_by_user_id' => $actor->id,
            ]);

            $employee->update(['outlet_id' => $destinationOutlet->id]);

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'employee.outlet.transferred',
                'description' => "Memindahkan outlet karyawan {$employee->full_name} dari outlet ID {$fromOutletId} ke ID {$destinationOutlet->id}",
                'metadata' => [
                    'employee_id' => $employee->id,
                    'from_outlet_id' => $fromOutletId,
                    'to_outlet_id' => $destinationOutlet->id,
                    'effective_date' => $todayStr,
                    'notes' => $notes,
                ],
            ]);

            return $transfer;
        });
    }
}
