<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeScheduleOverride;
use App\Models\Holiday;
use App\Models\Shift;
use App\Services\EffectiveScheduleService;
use App\Services\WorkCalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkCalendarController extends Controller
{
    public function __construct(
        protected WorkCalendarService $calendarService,
        protected EffectiveScheduleService $effectiveScheduleService,
    ) {}

    public function index(Request $request): View
    {
        $query = Holiday::with('creator')->orderByDesc('date');
        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }
        if ($request->filled('date')) {
            $query->whereDate('date', $request->string('date'));
        }

        $employees = Employee::where('status', 'active')->orderBy('full_name')->get();
        $overrides = EmployeeScheduleOverride::with(['employee', 'shift', 'creator'])
            ->orderByDesc('date')->limit(50)->get();

        return view('admin.work_calendar.index', [
            'calendarDays' => $query->paginate(20)->withQueryString(),
            'overrides' => $overrides,
            'employees' => $employees,
            'activeShifts' => Shift::where('is_active', true)->orderBy('name')->get(),
            'today' => Carbon::now(config('app.timezone'))->toDateString(),
        ]);
    }

    public function effectivePreview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);
        $effective = $this->effectiveScheduleService->resolve(
            Employee::findOrFail($data['employee_id']),
            $data['date'],
        );

        return response()->json([
            'source' => $effective['source'],
            'label' => $effective['label'],
            'is_working_day' => $effective['is_working_day'],
            'holiday_name' => $effective['holiday_name'],
            'regular' => $effective['regular_schedule'] ? [
                'type' => $effective['regular_schedule']->schedule_type,
                'shift' => $effective['regular_schedule']->shift?->name,
                'hours' => $effective['regular_schedule']->shift?->formatted_work_hours,
            ] : null,
            'effective_shift' => $effective['shift'] ? [
                'name' => $effective['shift']->name,
                'hours' => $effective['shift']->formatted_work_hours,
            ] : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCalendar($request);

        return $this->run(fn () => $this->calendarService->createCalendarDay($data, $request->user()), 'Hari kalender kerja berhasil ditambahkan.');
    }

    public function update(Request $request, Holiday $holiday): RedirectResponse
    {
        $data = $this->validateCalendar($request);

        return $this->run(fn () => $this->calendarService->updateCalendarDay($holiday, $data, $request->user()), 'Hari kalender kerja berhasil diperbarui.');
    }

    public function destroy(Request $request, Holiday $holiday): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);

        return $this->run(fn () => $this->calendarService->deleteCalendarDay($holiday, $data['reason'], $request->user()), 'Hari kalender kerja dihapus tanpa menghapus histori absensi.');
    }

    public function storeOverride(Request $request): RedirectResponse
    {
        $data = $this->validateOverride($request);

        return $this->run(fn () => $this->calendarService->saveOverride($data, $request->user()), 'Jadwal khusus karyawan berhasil disimpan.');
    }

    public function updateOverride(Request $request, EmployeeScheduleOverride $override): RedirectResponse
    {
        $data = $this->validateOverride($request);

        return $this->run(fn () => $this->calendarService->saveOverride($data, $request->user(), $override), 'Jadwal khusus karyawan berhasil diperbarui.');
    }

    public function destroyOverride(Request $request, EmployeeScheduleOverride $override): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);

        return $this->run(fn () => $this->calendarService->deleteOverride($override, $data['reason'], $request->user()), 'Jadwal khusus dihapus dan jadwal efektif kembali dihitung.');
    }

    private function validateCalendar(Request $request): array
    {
        return $request->validate([
            'date' => ['required', 'date'],
            'type' => ['required', 'in:public_holiday,company_holiday,special_working_day'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'audit_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
    }

    private function validateOverride(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'date' => ['required', 'date'],
            'override_type' => ['required', 'in:work,off'],
            'shift_id' => ['nullable', 'required_if:override_type,work', 'exists:shifts,id'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
    }

    private function run(callable $action, string $message): RedirectResponse
    {
        try {
            $action();

            return back()->with('success', $message);
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }
    }
}
