<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\BackupRecord;
use App\Services\BackupService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(protected BackupService $backupService) {}

    public function index(): View
    {
        $backups = BackupRecord::with('creator')
            ->where('status', '!=', 'deleted')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $totalCount = BackupRecord::where('status', 'completed')->count();
        $totalSize = BackupRecord::where('status', 'completed')->sum('file_size');
        $latestBackup = BackupRecord::where('status', 'completed')->orderBy('created_at', 'desc')->first();
        $latestScheduled = BackupRecord::where('status', 'completed')->whereNull('created_by')->orderBy('created_at', 'desc')->first();

        $scheduleSettings = [
            'enabled' => AppSetting::get('backup_scheduled_enabled', false),
            'frequency' => AppSetting::get('backup_scheduled_frequency', 'daily'),
            'time' => AppSetting::get('backup_scheduled_time', '02:00'),
            'day_of_week' => AppSetting::get('backup_scheduled_day', 0),
            'type' => AppSetting::get('backup_scheduled_type', 'full'),
            'retention_count' => AppSetting::get('backup_scheduled_retention_count', 14),
        ];

        return view('admin.settings.backups', [
            'backups' => $backups,
            'totalCount' => $totalCount,
            'totalSize' => $totalSize,
            'latestBackup' => $latestBackup,
            'latestScheduled' => $latestScheduled,
            'scheduleSettings' => $scheduleSettings,
            'engine' => $this->backupService->detectEngine(),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $request->validate([
            'type' => 'required|string|in:database,full',
        ]);

        try {
            $type = $request->input('type');
            $record = $this->backupService->createBackup($type, $request->user());

            return redirect()->back()->with('success', "Backup ({$type}) berhasil dibuat: " . basename($record->file_path));
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal membuat backup: ' . $e->getMessage());
        }
    }

    public function download(Request $request, BackupRecord $backup): BinaryFileResponse|RedirectResponse
    {
        if (! in_array($request->user()->role, ['superadmin', 'owner'], true)) {
            abort(403, 'Akses ditolak. Hanya Superadmin dan Owner yang dapat mengunduh file backup.');
        }

        if ($backup->status !== 'completed') {
            return redirect()->back()->with('error', 'File backup ini tidak tersedia untuk didownload.');
        }

        $fullPath = storage_path('app/' . $backup->file_path);
        if (! file_exists($fullPath)) {
            return redirect()->back()->with('error', 'Physical file backup tidak ditemukan pada storage server.');
        }

        AuditLog::log('backup.downloaded', $backup, null, null, $request->user());

        return response()->download($fullPath, basename($backup->file_path), [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'no-cache, no-store, must-revalidate, private',
            'Pragma' => 'no-cache',
        ]);
    }

    public function restore(Request $request, BackupRecord $backup): RedirectResponse
    {
        if ($request->user()->role !== 'superadmin') {
            abort(403, 'Akses ditolak. Restore backup hanya dapat dilakukan oleh Superadmin.');
        }

        $request->validate([
            'password' => 'required|string',
            'confirm_phrase' => 'nullable|string',
        ], [
            'password.required' => 'Password konfirmasi Superadmin wajib diisi.',
        ]);

        try {
            $this->backupService->restoreBackup($backup, $request->input('password'), $request->user());

            return redirect()->route('admin.dashboard')->with('success', 'Restore data aplikasi dan database berhasil dilakukan dari backup #' . $backup->backup_uuid);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal Restore: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, BackupRecord $backup): RedirectResponse
    {
        try {
            $this->backupService->deleteBackup($backup, $request->user());
            return redirect()->back()->with('success', 'Backup berhasil dihapus.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menghapus backup: ' . $e->getMessage());
        }
    }

    public function updateSchedule(Request $request): RedirectResponse
    {
        $request->validate([
            'enabled' => 'nullable|boolean',
            'frequency' => 'required|string|in:daily,weekly',
            'time' => 'required|string|regex:/^\d{2}:\d{2}$/',
            'day_of_week' => 'required|integer|between:0,6',
            'type' => 'required|string|in:database,full',
            'retention_count' => 'required|integer|between:3,100',
        ]);

        $enabled = $request->boolean('enabled');
        AppSetting::set('backup_scheduled_enabled', $enabled ? '1' : '0', 'boolean', true);
        AppSetting::set('backup_scheduled_frequency', $request->input('frequency'), 'string', true);
        AppSetting::set('backup_scheduled_time', $request->input('time'), 'string', true);
        AppSetting::set('backup_scheduled_day', (string) $request->input('day_of_week'), 'integer', true);
        AppSetting::set('backup_scheduled_type', $request->input('type'), 'string', true);
        AppSetting::set('backup_scheduled_retention_count', (string) $request->input('retention_count'), 'integer', true);

        AuditLog::log('backup_schedule.updated', null, null, [
            'enabled' => $enabled,
            'frequency' => $request->input('frequency'),
            'time' => $request->input('time'),
            'type' => $request->input('type'),
            'retention' => $request->input('retention_count'),
        ], $request->user());

        return redirect()->back()->with('success', 'Pengaturan Backup Otomatis dan Retention Policy berhasil diperbarui.');
    }
}
