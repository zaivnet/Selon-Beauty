<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Services\AttendancePeriodService;
use App\Services\MonthlyAttendanceRecapService;
use App\Services\MonthlyRecapExportService;
use App\Services\OutletScopeService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonthlyRecapController extends Controller
{
    public function __construct(
        protected MonthlyAttendanceRecapService $recapService,
        protected MonthlyRecapExportService $exportService,
        protected AttendancePeriodService $periodService,
        protected OutletScopeService $outletScopeService,
    ) {}

    public function index(Request $request): View
    {
        [$year, $month] = $this->period($request);
        $filters = $this->filters($request);
        $data = $this->recapService->generate($year, $month, $filters);
        $page = max(1, $request->integer('page', 1));
        $perPage = 20;
        $recaps = collect($data['recaps']);
        $paginator = new LengthAwarePaginator(
            $recaps->forPage($page, $perPage)->values(), $recaps->count(), $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        $attendancePeriod = $this->periodService->getOrCreatePeriod($year, $month);
        $closeEligibility = $this->periodService->validateCloseEligibility($year, $month);

        $employeesQuery = Employee::whereNull('deleted_at')->currentAttendanceWorkforce();
        $employeesQuery = $this->outletScopeService->scopeByRequestedOutlet($request->user(), $employeesQuery, $filters['outlet_id'] ?? null);
        $employees = $employeesQuery->orderBy('full_name')->get();

        return view('admin.monthly_recaps.index', [
            'recapData' => $data,
            'recaps' => $paginator,
            'employees' => $employees,
            'jobTitles' => JobTitle::where('is_active', true)->orderBy('name')->get(),
            'filters' => [...$filters, 'year' => $year, 'month' => $month],
            'attendancePeriod' => $attendancePeriod,
            'closeEligibility' => $closeEligibility,
        ]);
    }

    public function show(Request $request, Employee $employee): View
    {
        $this->outletScopeService->ensureCanManageEmployee($request->user(), $employee);

        [$year, $month] = $this->period($request);
        $attendancePeriod = $this->periodService->getOrCreatePeriod($year, $month);
        $closeEligibility = $this->periodService->validateCloseEligibility($year, $month);

        return view('admin.monthly_recaps.show', [
            'recap' => $this->recapService->forEmployee($employee, $year, $month),
            'navigation' => $this->navigation($year, $month),
            'returnFilters' => $this->returnFilters($request),
            'attendancePeriod' => $attendancePeriod,
            'closeEligibility' => $closeEligibility,
        ]);
    }

    public function print(Request $request, Employee $employee): View
    {
        $this->outletScopeService->ensureCanManageEmployee($request->user(), $employee);

        [$year, $month] = $this->period($request);

        return view('monthly_recaps.print', [
            'recap' => $this->recapService->forEmployee($employee, $year, $month),
            'generatedAt' => now(config('app.timezone'))->translatedFormat('d F Y H:i:s T'),
            'attendancePeriod' => $this->periodService->getOrCreatePeriod($year, $month),
        ]);
    }

    public function summaryCsv(Request $request): StreamedResponse
    {
        [$year, $month] = $this->period($request);
        $data = $this->recapService->generate($year, $month, $this->filters($request));

        return $this->exportService->summary($data, sprintf('rekap-kehadiran-%04d-%02d-summary.csv', $year, $month));
    }

    public function detailCsv(Request $request): StreamedResponse
    {
        [$year, $month] = $this->period($request);
        $data = $this->recapService->generate($year, $month, $this->filters($request));

        return $this->exportService->detail($data, sprintf('rekap-kehadiran-%04d-%02d-detail.csv', $year, $month));
    }

    public function closePeriod(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'reason' => ['required', 'string', 'min:5'],
        ]);

        try {
            $this->periodService->closePeriod((int) $validated['year'], (int) $validated['month'], $request->user(), $validated['reason']);

            return redirect()->back()->with('success', 'Periode kehadiran berhasil ditutup.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function reopenPeriod(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'reason' => ['required', 'string', 'min:5'],
        ]);

        try {
            $this->periodService->reopenPeriod((int) $validated['year'], (int) $validated['month'], $request->user(), $validated['reason']);

            return redirect()->back()->with('success', 'Periode kehadiran berhasil dibuka kembali.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
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

    /** @return array<string, mixed> */
    protected function filters(Request $request): array
    {
        $validated = $request->validate([
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'job_title_id' => ['nullable', 'integer', 'exists:job_titles,id'],
            'outlet_id' => ['nullable', 'integer'],
        ]);

        $inputOutletId = $request->has('outlet_id') ? (int) $request->input('outlet_id') : null;
        $requestedOutletId = $this->outletScopeService->resolveRequestedOutlet($request->user(), $inputOutletId);

        return [
            'employee_id' => $validated['employee_id'] ?? null,
            'job_title_id' => $validated['job_title_id'] ?? null,
            'outlet_id' => $requestedOutletId,
            'actor' => $request->user(),
        ];
    }

    /** @return array<string, int|null> */
    protected function returnFilters(Request $request): array
    {
        $validated = $request->validate([
            'return_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'return_job_title_id' => ['nullable', 'integer', 'exists:job_titles,id'],
            'return_page' => ['nullable', 'integer', 'min:1'],
        ]);

        return [
            'employee_id' => $validated['return_employee_id'] ?? null,
            'job_title_id' => $validated['return_job_title_id'] ?? null,
            'page' => $validated['return_page'] ?? null,
        ];
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
