<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\AttendanceRecord;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\Holiday;
use App\Models\OvertimeRequest;
use App\Services\EffectiveScheduleService;
use App\Services\OvertimeRequestService;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OvertimeRequestController extends Controller
{
    public function __construct(
        protected OvertimeRequestService $overtimeService,
        protected EffectiveScheduleService $effectiveScheduleService,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            return redirect()->route('employee.dashboard')
                ->with('error', 'Profil karyawan belum terhubung.');
        }

        $requests = OvertimeRequest::where('employee_id', $employee->id)
            ->with(['reviewer', 'session'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Load attendance context for each request
        $workDates = $requests->pluck('work_date')->map(fn ($d) => $d->format('Y-m-d'))->toArray();

        $attendances = AttendanceRecord::where('employee_id', $employee->id)
            ->whereIn('work_date', $workDates)
            ->get()
            ->keyBy(fn ($a) => $a->work_date->format('Y-m-d'));

        $requestRegular = EmployeeSchedule::with('shift')->where('employee_id', $employee->id)
            ->whereIn('work_date', $workDates)->get()->keyBy(fn ($item) => $item->work_date->format('Y-m-d'));
        $requestOverrides = EmployeeScheduleOverride::with('shift')->where('employee_id', $employee->id)
            ->whereIn('date', $workDates)->get()->keyBy(fn ($item) => $item->date->format('Y-m-d'));
        $requestCalendars = Holiday::whereIn('date', $workDates)->get()->keyBy(fn ($item) => $item->date->format('Y-m-d'));
        $schedules = collect($workDates)->mapWithKeys(function (string $date) use ($employee, $requestRegular, $requestOverrides, $requestCalendars) {
            $effective = $this->effectiveScheduleService->resolveFromModels(
                $employee, $date, $requestRegular->get($date), $requestOverrides->get($date), $requestCalendars->get($date),
            );

            return [$date => $this->effectiveScheduleService->displaySchedule($effective)];
        });

        // Available work schedules for new requests (WORK type, last 30 days to next 7 days)
        $rangeStart = now(config('app.timezone'))->subDays(30)->startOfDay();
        $rangeEnd = now(config('app.timezone'))->addDays(7)->startOfDay();
        $regularRange = EmployeeSchedule::with('shift')->where('employee_id', $employee->id)
            ->whereBetween('work_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->get()->keyBy(fn ($item) => $item->work_date->format('Y-m-d'));
        $overrideRange = EmployeeScheduleOverride::with('shift')->where('employee_id', $employee->id)
            ->whereBetween('date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->get()->keyBy(fn ($item) => $item->date->format('Y-m-d'));
        $calendarRange = Holiday::whereBetween('date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->get()->keyBy(fn ($item) => $item->date->format('Y-m-d'));
        $availableSchedules = collect(CarbonPeriod::create($rangeStart, $rangeEnd))->map(function ($date) use ($employee, $regularRange, $overrideRange, $calendarRange) {
            $key = $date->format('Y-m-d');
            $effective = $this->effectiveScheduleService->resolveFromModels(
                $employee, $key, $regularRange->get($key), $overrideRange->get($key), $calendarRange->get($key),
            );

            return $effective['source'] === 'none' ? null : $this->effectiveScheduleService->displaySchedule($effective);
        })->filter()->sortByDesc('work_date')->values();

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
            'requireSelfie' => (bool) AppSetting::get('attendance_require_selfie', true),
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
