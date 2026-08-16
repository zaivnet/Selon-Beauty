<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Services\OperationalExceptionService;
use App\Services\OutletScopeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OperationalExceptionController extends Controller
{
    public function __construct(
        protected OperationalExceptionService $exceptionService,
        protected OutletScopeService $outletScopeService,
    ) {}

    public function index(Request $request): View
    {
        $now = Carbon::now(config('app.timezone'));
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.$now->toDateString()],
            'severity' => ['nullable', Rule::in(['critical', 'warning', 'info'])],
            'category' => ['nullable', Rule::in(array_keys($this->exceptionService->categories()))],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'job_title_id' => ['nullable', 'integer', 'exists:job_titles,id'],
        ]);
        $date = $validated['date'] ?? $now->toDateString();
        $filters = [
            'severity' => $validated['severity'] ?? null,
            'category' => $validated['category'] ?? null,
            'employee_id' => $validated['employee_id'] ?? null,
            'job_title_id' => $validated['job_title_id'] ?? null,
        ];
        $data = $this->exceptionService->generate($date, [
            ...$filters,
            'include_backup_health' => in_array($request->user()->role, ['owner', 'superadmin'], true),
        ], $now);

        $allItems = collect($data['items']);
        if ($request->user()->role === 'admin') {
            $adminOutletId = $this->outletScopeService->getAdminOutletId($request->user());
            $allItems = $allItems->filter(function ($item) use ($adminOutletId) {
                return isset($item['employee']) ? ((int) $item['employee']->outlet_id === (int) $adminOutletId) : true;
            });
        }

        $page = max(1, $request->integer('page', 1));
        $perPage = 30;
        $items = new LengthAwarePaginator(
            $allItems->forPage($page, $perPage)->values(),
            $allItems->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        $employeesQuery = Employee::whereNull('deleted_at')->where('status', 'active')->currentAttendanceWorkforce();
        $employeesQuery = $this->outletScopeService->scopeEmployeesFor($request->user(), $employeesQuery);

        return view('admin.operational_exceptions.index', [
            'exceptions' => $data,
            'items' => $items,
            'filters' => ['date' => $date, ...$filters],
            'categories' => $this->exceptionService->categories(),
            'employees' => $employeesQuery->orderBy('full_name')->get(),
            'jobTitles' => JobTitle::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
