<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\AttendancePeriodService;
use App\Services\MonthlyAttendanceRecapService;
use App\Services\MonthlyRecapExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonthlyRecapController extends Controller
{
    public function __construct(
        protected MonthlyAttendanceRecapService $recapService,
        protected MonthlyRecapExportService $exportService,
        protected AttendancePeriodService $periodService,
    ) {}

    public function show(Request $request): View
    {
        [$year, $month] = $this->period($request);
        $employee = $request->user()->employee;
        abort_unless($employee, 404, 'Profil karyawan tidak ditemukan.');

        return view('employee.monthly_recaps.show', [
            'recap' => $this->recapService->forEmployee($employee, $year, $month),
            'navigation' => $this->navigation($year, $month),
            'attendancePeriod' => $this->periodService->getOrCreatePeriod($year, $month),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$year, $month] = $this->period($request);
        $employee = $request->user()->employee;
        abort_unless($employee, 404, 'Profil karyawan tidak ditemukan.');
        $data = $this->recapService->generate($year, $month, ['employee_id' => $employee->id]);

        return $this->exportService->detail($data, sprintf('rekap-saya-%04d-%02d.csv', $year, $month));
    }

    public function print(Request $request): View
    {
        [$year, $month] = $this->period($request);
        $employee = $request->user()->employee;
        abort_unless($employee, 404, 'Profil karyawan tidak ditemukan.');

        return view('monthly_recaps.print', [
            'recap' => $this->recapService->forEmployee($employee, $year, $month),
            'generatedAt' => now(config('app.timezone'))->translatedFormat('d F Y H:i:s T'),
            'attendancePeriod' => $this->periodService->getOrCreatePeriod($year, $month),
        ]);
    }

    /** @return array{0:int,1:int} */
    protected function period(Request $request): array
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);
        $now = Carbon::now(config('app.timezone'));

        return [(int) ($validated['year'] ?? $now->year), (int) ($validated['month'] ?? $now->month)];
    }

    /** @return array<string, array{year:int,month:int}> */
    protected function navigation(int $year, int $month): array
    {
        $current = Carbon::create($year, $month, 1, 0, 0, 0, config('app.timezone'));

        return [
            'previous' => ['year' => $current->copy()->subMonth()->year, 'month' => $current->copy()->subMonth()->month],
            'next' => ['year' => $current->copy()->addMonth()->year, 'month' => $current->copy()->addMonth()->month],
        ];
    }
}
