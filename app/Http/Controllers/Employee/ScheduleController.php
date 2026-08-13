<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeeSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ScheduleController extends Controller
{
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

        // Privacy Enforcement: Query ONLY schedules belonging to the authenticated employee
        $schedules = EmployeeSchedule::with(['shift'])
            ->where('employee_id', $employee->id)
            ->whereBetween('work_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('work_date')
            ->get();

        $prevWeekDate = (clone $startDate)->subWeek()->format('Y-m-d');
        $nextWeekDate = (clone $startDate)->addWeek()->format('Y-m-d');

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
