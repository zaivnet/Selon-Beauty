<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OperationalExceptionService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected OperationalExceptionService $exceptionService) {}

    public function index(Request $request): View
    {
        $now = Carbon::now(config('app.timezone'));
        $includeBackupHealth = in_array($request->user()->role, ['owner', 'superadmin'], true);

        return view('admin.dashboard', [
            'exceptions' => $this->exceptionService->generate($now->toDateString(), [
                'include_backup_health' => $includeBackupHealth,
            ], $now),
            'todayFormatted' => $now->locale('id')->isoFormat('dddd, D MMMM YYYY'),
        ]);
    }
}
