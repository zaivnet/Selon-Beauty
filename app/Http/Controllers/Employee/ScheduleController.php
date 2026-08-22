<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\Holiday;
use App\Services\EffectiveScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __construct(protected EffectiveScheduleService $effectiveScheduleService) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            return view('employee.schedules.index', [
                'schedules' => collect(),
                'employee' => null,
                'startDate' => Carbon::now()->startOfWeek(),
                'endDate' => Carbon::now()->endOfWeek(),
                'errorMsg' => 'Akun Anda belum terhubung dengan data karyawan.',
            ]);
        }

        $startDateParam = $request->input('start_date');
        $startDate = $startDateParam ? Carbon::parse($startDateParam)->startOfWeek() : Carbon::now()->startOfWeek();
        $endDate = (clone $startDate)->endOfWeek();
        $prevWeekDate = (clone $startDate)->subWeek()->format('Y-m-d');
        $nextWeekDate = (clone $startDate)->addWeek()->format('Y-m-d');

        if (! $employee->participatesInAttendance()) {
            return view('employee.schedules.index', [
                'schedules' => collect(),
                'employee' => $employee,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'prevWeekDate' => $prevWeekDate,
                'nextWeekDate' => $nextWeekDate,
            ]);
        }

        // Privacy Enforcement: Query ONLY schedules belonging to the authenticated employee
        $regular = EmployeeSchedule::with(['shift', 'workOutlet'])->where('employee_id', $employee->id)
            ->whereBetween('work_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()->keyBy(fn ($item) => $item->work_date->format('Y-m-d'));
        $overrides = EmployeeScheduleOverride::with(['shift', 'workOutlet'])->where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()->keyBy(fn ($item) => $item->date->format('Y-m-d'));
        $calendarDays = Holiday::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()->keyBy(fn ($item) => $item->date->format('Y-m-d'));
        $schedules = collect(range(0, 6))->map(function (int $offset) use ($employee, $startDate, $regular, $overrides, $calendarDays) {
            $date = $startDate->copy()->addDays($offset)->format('Y-m-d');

            return $this->effectiveScheduleService->resolveFromModels(
                $employee, $date, $regular->get($date), $overrides->get($date), $calendarDays->get($date),
            );
        });

        return view('employee.schedules.index', compact(
            'schedules',
            'employee',
            'startDate',
            'endDate',
            'prevWeekDate',
            'nextWeekDate'
        ));
    }
}
