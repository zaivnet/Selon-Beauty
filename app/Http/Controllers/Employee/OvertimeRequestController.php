<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\EmployeeSchedule;
use App\Models\OvertimeRequest;
use App\Services\OvertimeRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OvertimeRequestController extends Controller
{
    public function __construct(protected OvertimeRequestService $overtimeService) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            return redirect()->route('employee.dashboard')
                ->with('error', 'Profil karyawan belum terhubung.');
        }

        $requests = OvertimeRequest::where('employee_id', $employee->id)
            ->with(['reviewer'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Load attendance context for each request
        $workDates = $requests->pluck('work_date')->map(fn ($d) => $d->format('Y-m-d'))->toArray();
        
        $attendances = AttendanceRecord::where('employee_id', $employee->id)
            ->whereIn('work_date', $workDates)
            ->get()
            ->keyBy(fn ($a) => $a->work_date->format('Y-m-d'));

        $schedules = EmployeeSchedule::where('employee_id', $employee->id)
            ->whereIn('work_date', $workDates)
            ->with('shift')
            ->get()
            ->keyBy(fn ($s) => $s->work_date->format('Y-m-d'));

        // Available work schedules for new requests (WORK type, last 30 days to next 7 days)
        $availableSchedules = EmployeeSchedule::where('employee_id', $employee->id)
            ->where('schedule_type', 'work')
            ->whereBetween('work_date', [now()->subDays(30)->format('Y-m-d'), now()->addDays(7)->format('Y-m-d')])
            ->with(['shift'])
            ->orderBy('work_date', 'desc')
            ->get();

        // Map attendance details onto available schedules for context display
        $availableAttendances = AttendanceRecord::where('employee_id', $employee->id)
            ->whereIn('work_date', $availableSchedules->pluck('work_date')->map(fn ($d) => $d->format('Y-m-d'))->toArray())
            ->get()
            ->keyBy(fn ($a) => $a->work_date->format('Y-m-d'));

        return view('employee.overtime_requests.index', [
            'employee' => $employee,
            'requests' => $requests,
            'attendances' => $attendances,
            'schedules' => $schedules,
            'availableSchedules' => $availableSchedules,
            'availableAttendances' => $availableAttendances,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            return redirect()->route('employee.dashboard')
                ->with('error', 'Profil karyawan tidak ditemukan.');
        }

        $validated = $request->validate([
            'work_date' => ['required', 'date_format:Y-m-d'],
            'requested_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'work_date.required' => 'Tanggal lembur wajib dipilih.',
            'requested_minutes.required' => 'Durasi lembur wajib diisi.',
            'requested_minutes.min' => 'Durasi lembur minimal 1 menit.',
            'reason.required' => 'Alasan pengajuan lembur wajib diisi.',
            'reason.min' => 'Alasan pengajuan lembur minimal 5 karakter.',
        ]);

        $this->overtimeService->submitRequest($employee, $validated);

        return redirect()->route('employee.overtime-requests.index')
            ->with('success', 'Pengajuan lembur berhasil dikirim.');
    }

    public function cancel(OvertimeRequest $overtimeRequest): RedirectResponse
    {
        $user = Auth::user();

        if ($overtimeRequest->employee_id !== $user->employee_id) {
            abort(403, 'Akses ditolak. Pengajuan ini bukan milik Anda.');
        }

        $this->overtimeService->cancelRequest($overtimeRequest, $user);

        return redirect()->route('employee.overtime-requests.index')
            ->with('success', 'Pengajuan lembur berhasil dibatalkan.');
    }
}
