<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Services\OutletScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveAttachmentController extends Controller
{
    public function __construct(protected OutletScopeService $outletScopeService) {}

    public function show(Request $request, LeaveRequest $leaveRequest): StreamedResponse
    {
        $user = Auth::user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        // Authorization check: Superadmin/Owner, Admin (scoped), or the owning Employee
        if ($user->role === 'admin') {
            $this->outletScopeService->ensureCanManageLeave($user, $leaveRequest);
        } elseif (! $this->outletScopeService->isGlobalScope($user)) {
            $isOwnRecord = $user->employee_id && $leaveRequest->employee_id === $user->employee_id;
            if (! $isOwnRecord) {
                abort(403, 'Akses ditolak. Anda tidak berhak melihat lampiran ini.');
            }
        }

        $path = $leaveRequest->attachment_path;
        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404, 'File lampiran tidak ditemukan.');
        }

        return Storage::disk('local')->response($path);
    }
}
