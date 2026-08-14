<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreScheduleRequest;
use App\Http\Requests\Admin\UpdateScheduleRequest;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\Holiday;
use App\Models\Shift;
use App\Services\EffectiveScheduleService;
use App\Services\EmployeeScheduleService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __construct(
        protected EmployeeScheduleService $scheduleService,
        protected EffectiveScheduleService $effectiveScheduleService,
    ) {}

    public function index(Request $request): View
    {
        $startDateParam = $request->input('start_date');

        if ($startDateParam) {
            $startDate = Carbon::parse($startDateParam)->startOfWeek();
        } else {
            $startDate = Carbon::now()->startOfWeek();
        }

        $endDate = (clone $startDate)->endOfWeek();

        $weekDays = [];
        $currentDay = clone $startDate;
        for ($i = 0; $i < 7; $i++) {
            $weekDays[] = [
                'date' => $currentDay->format('Y-m-d'),
                'day_name' => $currentDay->locale('id')->isoFormat('dddd'),
                'short_date' => $currentDay->format('d/m'),
                'is_today' => $currentDay->isToday(),
            ];
            $currentDay->addDay();
        }

        $employees = Employee::where('status', 'active')
            ->orderBy('full_name')
            ->get();

        $shifts = Shift::orderBy('name')->get();
        $activeShifts = $shifts->where('is_active', true);

        // Fetch existing schedules for the week
        $schedulesRaw = EmployeeSchedule::with(['shift'])
            ->whereBetween('work_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        // Key by employee_id + work_date
        $scheduleMatrix = [];
        foreach ($schedulesRaw as $sch) {
            $key = $sch->employee_id.'_'.$sch->work_date->format('Y-m-d');
            $scheduleMatrix[$key] = $sch;
        }

        $overrides = EmployeeScheduleOverride::with('shift')
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()->keyBy(fn ($item) => $item->employee_id.'_'.$item->date->format('Y-m-d'));
        $calendarDays = Holiday::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()->keyBy(fn ($item) => $item->date->format('Y-m-d'));

        $effectiveScheduleMatrix = [];
        foreach ($employees as $employee) {
            foreach ($weekDays as $day) {
                $key = $employee->id.'_'.$day['date'];
                $effectiveScheduleMatrix[$key] = $this->effectiveScheduleService->resolveFromModels(
                    $employee, $day['date'], $scheduleMatrix[$key] ?? null,
                    $overrides->get($key), $calendarDays->get($day['date']),
                );
            }
        }

        $prevWeekDate = (clone $startDate)->subWeek()->format('Y-m-d');
        $nextWeekDate = (clone $startDate)->addWeek()->format('Y-m-d');

        // Copy week preview data if requested
        $copyPreview = null;
        if ($request->boolean('show_copy_preview')) {
            $copyPreview = $this->scheduleService->previewCopyPreviousWeek($startDate->format('Y-m-d'));
        }

        return view('admin.schedules.index', compact(
            'startDate',
            'endDate',
            'weekDays',
            'employees',
            'shifts',
            'activeShifts',
            'scheduleMatrix',
            'effectiveScheduleMatrix',
            'prevWeekDate',
            'nextWeekDate',
            'copyPreview'
        ));
    }

    public function store(StoreScheduleRequest $request): RedirectResponse
    {
        try {
            $this->scheduleService->assignSchedule($request->validated(), $request->user());

            return redirect()->back()
                ->with('success', 'Jadwal kerja berhasil ditambahkan.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function update(UpdateScheduleRequest $request, EmployeeSchedule $schedule): RedirectResponse
    {
        try {
            $this->scheduleService->updateSchedule($schedule, $request->validated(), $request->user());

            return redirect()->back()
                ->with('success', 'Jadwal kerja berhasil diperbarui.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function markOff(Request $request): RedirectResponse
    {
        $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'work_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->scheduleService->markOff(
            (int) $request->input('employee_id'),
            $request->input('work_date'),
            $request->input('notes'),
            $request->user()
        );

        return redirect()->back()
            ->with('success', 'Karyawan berhasil ditandai Libur (OFF).');
    }

    public function copyWeekExecute(Request $request): RedirectResponse
    {
        $request->validate([
            'target_start_date' => ['required', 'date'],
            'overwrite' => ['nullable', 'boolean'],
        ]);

        $result = $this->scheduleService->executeCopyPreviousWeek(
            $request->input('target_start_date'),
            $request->boolean('overwrite'),
            $request->user()
        );

        $msg = "Penyalinan jadwal minggu lalu selesai. {$result['copied_count']} jadwal disalin";
        if ($result['skipped_count'] > 0) {
            $msg .= ", {$result['skipped_count']} jadwal dilewati karena ada jadwal existing.";
        }

        return redirect()->route('admin.schedules.index', ['start_date' => $request->input('target_start_date')])
            ->with('success', $msg);
    }

    public function destroy(Request $request, EmployeeSchedule $schedule): RedirectResponse
    {
        try {
            $this->scheduleService->deleteSchedule($schedule, $request->user());

            return redirect()->back()
                ->with('success', 'Jadwal kerja berhasil dihapus.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
