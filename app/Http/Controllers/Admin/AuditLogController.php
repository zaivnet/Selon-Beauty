<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        if (! in_array($request->user()->role, ['superadmin', 'owner'], true)) {
            abort(403, 'Akses ditolak. Audit Logs hanya dapat diakses oleh Superadmin dan Owner.');
        }

        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('action')) {
            $query->where('action', 'like', '%'.$request->input('action').'%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('module')) {
            $query->where('action', 'like', $request->input('module').'.%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('employee_id')) {
            $query->where('metadata->employee_id', (int) $request->input('employee_id'));
        }

        $logs = $query->paginate(25)->withQueryString();

        return view('admin.audit_logs.index', [
            'logs' => $logs,
            'actors' => User::whereIn('role', ['admin', 'owner', 'superadmin'])->orderBy('name')->get(['id', 'name']),
            'employees' => Employee::orderBy('full_name')->get(['id', 'full_name', 'employee_code'])->keyBy('id'),
            'filters' => $request->only(['action', 'user_id', 'module', 'date_from', 'date_to', 'employee_id']),
        ]);
    }
}
