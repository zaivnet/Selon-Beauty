<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
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
            $query->where('action', 'like', '%' . $request->input('action') . '%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $logs = $query->paginate(25)->withQueryString();

        return view('admin.audit_logs.index', [
            'logs' => $logs,
            'actionFilter' => $request->input('action'),
        ]);
    }
}
