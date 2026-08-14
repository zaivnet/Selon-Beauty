<?php

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\AttendanceLocationController;
use App\Http\Controllers\Admin\AttendanceSettingController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\JobTitleController;
use App\Http\Controllers\Admin\LeaveRequestController as AdminLeaveRequestController;
use App\Http\Controllers\Admin\MonthlyRecapController as AdminMonthlyRecapController;
use App\Http\Controllers\Admin\OperationalExceptionController;
use App\Http\Controllers\Admin\OvertimeRequestController as AdminOvertimeRequestController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ScheduleController as AdminScheduleController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\Admin\WorkCalendarController;
use App\Http\Controllers\AttendanceSelfieController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Employee\AttendanceController as EmployeeAttendanceController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\LeaveRequestController as EmployeeLeaveRequestController;
use App\Http\Controllers\Employee\MonthlyRecapController as EmployeeMonthlyRecapController;
use App\Http\Controllers\Employee\OvertimeRequestController as EmployeeOvertimeRequestController;
use App\Http\Controllers\Employee\OvertimeSessionController as EmployeeOvertimeSessionController;
use App\Http\Controllers\Employee\ProfileController as EmployeeProfileController;
use App\Http\Controllers\Employee\ScheduleController as EmployeeScheduleController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\LeaveAttachmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OvertimeSelfieController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Health Check Endpoint (Public)
Route::get('/up', HealthCheckController::class)->name('health');
Route::get('/health', HealthCheckController::class);

// PWA Dynamic Assets (Sprint 15 & 17.5)
Route::get('/manifest.webmanifest', \App\Http\Controllers\PwaManifestController::class)->name('pwa.manifest');
Route::get('/sw.js', function () {
    return response(file_get_contents(public_path('sw.js')), 200, [
        'Content-Type' => 'application/javascript',
    ]);
});
Route::get('/offline.html', function () {
    return response(file_get_contents(public_path('offline.html')), 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
    ]);
});

// Root entry point
Route::get('/', function () {
    if (Auth::check()) {
        $role = Auth::user()->role;
        return $role === 'employee'
            ? redirect()->route('employee.dashboard')
            : redirect()->route('admin.dashboard');
    }

    return redirect()->route('login');
})->name('root');

// First-Run Setup Routes (Sprint 18.5.5)
Route::middleware(['throttle:10,1'])->group(function () {
    Route::get('/setup', [\App\Http\Controllers\Auth\SetupController::class, 'showForm'])->name('setup');
    Route::post('/setup', [\App\Http\Controllers\Auth\SetupController::class, 'processSetup'])->name('setup.store');
});

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

// Authenticated Logout Route
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

// Password Reset Routes (Sprint 18.6)
Route::middleware(['throttle:6,1'])->group(function () {
    Route::get('/forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('password.update');
});

// Admin, Owner & Superadmin Protected Routes (/admin/*)
Route::middleware(['auth', 'role:superadmin,owner,admin', 'prevent.private.cache'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/operational-exceptions', [OperationalExceptionController::class, 'index'])->name('operational-exceptions.index');

    // Job Title Management (Sprint 02)
    Route::resource('job-titles', JobTitleController::class)->except(['create', 'show', 'edit']);
    Route::post('/job-titles/{job_title}/toggle-status', [JobTitleController::class, 'toggleStatus'])->name('job-titles.toggle-status');

    // Employee Management (Sprint 02)
    Route::resource('employees', EmployeeController::class);
    Route::post('/employees/{employee}/toggle-status', [EmployeeController::class, 'toggleStatus'])->name('employees.toggle-status');

    // Superadmin Administrative Reset Password (Sprint 18.6)
    Route::post('/employees/{employee}/reset-password', [\App\Http\Controllers\Admin\AdminPasswordResetController::class, 'reset'])
        ->middleware('role:superadmin')
        ->name('employees.reset-password');

    // Attendance Settings & Store Locations (Sprint 03)
    Route::get('/settings/attendance', [AttendanceSettingController::class, 'index'])->name('settings.attendance');
    Route::get('/settings/locations', [AttendanceSettingController::class, 'index'])->name('settings.locations.index');
    Route::post('/settings/attendance', [AttendanceSettingController::class, 'update'])->name('settings.attendance.update');
    Route::post('/settings/locations', [AttendanceLocationController::class, 'store'])->name('settings.locations.store');
    Route::put('/settings/locations/{location}', [AttendanceLocationController::class, 'update'])->name('settings.locations.update');
    Route::post('/settings/locations/{location}/toggle-status', [AttendanceLocationController::class, 'toggleStatus'])->name('settings.locations.toggle-status');
    Route::delete('/settings/locations/{location}', [AttendanceLocationController::class, 'destroy'])->name('settings.locations.destroy');

    // Application Branding Settings (Superadmin + Owner Only)
    Route::middleware('role:superadmin,owner')->group(function () {
        Route::get('/settings/branding', [\App\Http\Controllers\Admin\BrandingSettingController::class, 'index'])->name('settings.branding.index');
        Route::post('/settings/branding', [\App\Http\Controllers\Admin\BrandingSettingController::class, 'update'])->name('settings.branding.update');

        // Audit Logs (Superadmin + Owner Only)
        Route::get('/audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs.index');
    });

    // Backup System (Superadmin + Owner for Manage; Restore Superadmin Only)
    Route::middleware('role:superadmin,owner')->group(function () {
        Route::get('/settings/backups', [\App\Http\Controllers\Admin\BackupController::class, 'index'])->name('settings.backups.index');
        Route::post('/settings/backups', [\App\Http\Controllers\Admin\BackupController::class, 'create'])->name('settings.backups.create');
        Route::get('/settings/backups/{backup}/download', [\App\Http\Controllers\Admin\BackupController::class, 'download'])->name('settings.backups.download');
        Route::delete('/settings/backups/{backup}', [\App\Http\Controllers\Admin\BackupController::class, 'destroy'])->name('settings.backups.destroy');
        Route::post('/settings/backups/schedule', [\App\Http\Controllers\Admin\BackupController::class, 'updateSchedule'])->name('settings.backups.schedule');
    });

    // Restore Backup (Superadmin Only)
    Route::post('/settings/backups/{backup}/restore', [\App\Http\Controllers\Admin\BackupController::class, 'restore'])
        ->middleware('role:superadmin')
        ->name('settings.backups.restore');

    // Shift Management (Sprint 04)
    Route::resource('shifts', ShiftController::class);
    Route::post('/shifts/{shift}/toggle-status', [ShiftController::class, 'toggleStatus'])->name('shifts.toggle-status');

    // Employee Scheduling (Sprint 05)
    Route::get('/schedules', [AdminScheduleController::class, 'index'])->name('schedules.index');
    Route::post('/schedules', [AdminScheduleController::class, 'store'])->name('schedules.store');
    Route::put('/schedules/{schedule}', [AdminScheduleController::class, 'update'])->name('schedules.update');
    Route::post('/schedules/mark-off', [AdminScheduleController::class, 'markOff'])->name('schedules.mark-off');
    Route::post('/schedules/copy-week/execute', [AdminScheduleController::class, 'copyWeekExecute'])->name('schedules.copy-week.execute');
    Route::delete('/schedules/{schedule}', [AdminScheduleController::class, 'destroy'])->name('schedules.destroy');
    Route::get('/work-calendar', [WorkCalendarController::class, 'index'])->name('work-calendar.index');
    Route::get('/work-calendar/effective-preview', [WorkCalendarController::class, 'effectivePreview'])->name('work-calendar.effective-preview');
    Route::post('/work-calendar', [WorkCalendarController::class, 'store'])->name('work-calendar.store');
    Route::put('/work-calendar/{holiday}', [WorkCalendarController::class, 'update'])->name('work-calendar.update');
    Route::delete('/work-calendar/{holiday}', [WorkCalendarController::class, 'destroy'])->name('work-calendar.destroy');
    Route::post('/schedule-overrides', [WorkCalendarController::class, 'storeOverride'])->name('schedule-overrides.store');
    Route::put('/schedule-overrides/{override}', [WorkCalendarController::class, 'updateOverride'])->name('schedule-overrides.update');
    Route::delete('/schedule-overrides/{override}', [WorkCalendarController::class, 'destroyOverride'])->name('schedule-overrides.destroy');

    // Attendance Monitoring Dashboard (Sprint 09)
    Route::get('/attendance', [AdminAttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/{attendance}', [AdminAttendanceController::class, 'show'])->name('attendance.show');
    Route::post('/attendance/{attendance}/correct', [AdminAttendanceController::class, 'correct'])->name('attendance.correct');

    // Leave Requests Management (Sprint 10)
    Route::get('/leave-requests', [AdminLeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::post('/leave-requests/{leaveRequest}/approve', [AdminLeaveRequestController::class, 'approve'])->name('leave-requests.approve');
    Route::post('/leave-requests/{leaveRequest}/reject', [AdminLeaveRequestController::class, 'reject'])->name('leave-requests.reject');

    // Overtime Requests Management (Sprint 11)
    Route::get('/overtime-requests', [AdminOvertimeRequestController::class, 'index'])->name('overtime-requests.index');
    Route::post('/overtime-requests/{overtimeRequest}/approve', [AdminOvertimeRequestController::class, 'approve'])->name('overtime-requests.approve');
    Route::post('/overtime-requests/{overtimeRequest}/reject', [AdminOvertimeRequestController::class, 'reject'])->name('overtime-requests.reject');
    Route::post('/overtime-sessions/{overtimeSession}/force-finish', [AdminOvertimeRequestController::class, 'forceFinish'])->name('overtime-sessions.force-finish');
    Route::post('/overtime-sessions/{overtimeSession}/cancel', [AdminOvertimeRequestController::class, 'cancelSession'])->name('overtime-sessions.cancel');
    Route::post('/overtime-sessions/{overtimeSession}/correct', [AdminOvertimeRequestController::class, 'correctSession'])->name('overtime-sessions.correct');

    // Reports & Export Management (Sprint 12)
    Route::get('/reports/attendance', [AdminReportController::class, 'attendance'])->name('reports.attendance');
    Route::get('/reports/attendance/print', [AdminReportController::class, 'printView'])->name('reports.attendance.print');
    Route::get('/reports/attendance/export-csv', [AdminReportController::class, 'exportCsv'])->name('reports.attendance.export-csv');
    Route::get('/monthly-recaps', [AdminMonthlyRecapController::class, 'index'])->name('monthly-recaps.index');
    Route::get('/monthly-recaps/export-summary', [AdminMonthlyRecapController::class, 'summaryCsv'])->name('monthly-recaps.export-summary');
    Route::get('/monthly-recaps/export-detail', [AdminMonthlyRecapController::class, 'detailCsv'])->name('monthly-recaps.export-detail');
    Route::get('/monthly-recaps/{employee}', [AdminMonthlyRecapController::class, 'show'])->name('monthly-recaps.show');
    Route::get('/monthly-recaps/{employee}/print', [AdminMonthlyRecapController::class, 'print'])->name('monthly-recaps.print');
});

// Employee & Owner Protected Routes (/app/*)
Route::middleware(['auth', 'role:owner,employee', 'prevent.private.cache'])->prefix('app')->name('employee.')->group(function () {
    Route::get('/dashboard', [EmployeeDashboardController::class, 'index'])->name('dashboard');

    // Employee Personal Schedule View (Sprint 05)
    Route::get('/schedules', [EmployeeScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/monthly-recap', [EmployeeMonthlyRecapController::class, 'show'])->name('monthly-recap.show');
    Route::get('/monthly-recap/export-csv', [EmployeeMonthlyRecapController::class, 'exportCsv'])->name('monthly-recap.export-csv');
    Route::get('/monthly-recap/print', [EmployeeMonthlyRecapController::class, 'print'])->name('monthly-recap.print');

    // Employee Core Attendance Engine (Sprint 06 & Sprint 08)
    Route::post('/attendance/check-in', [EmployeeAttendanceController::class, 'checkIn'])->name('attendance.check-in');
    Route::post('/attendance/check-out', [EmployeeAttendanceController::class, 'checkOut'])->name('attendance.check-out');

    // Leave Requests (Sprint 10)
    Route::get('/leave-requests', [EmployeeLeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::post('/leave-requests', [EmployeeLeaveRequestController::class, 'store'])->name('leave-requests.store');
    Route::post('/leave-requests/{leaveRequest}/cancel', [EmployeeLeaveRequestController::class, 'cancel'])->name('leave-requests.cancel');

    // Overtime Requests (Sprint 11)
    Route::get('/overtime-requests', [EmployeeOvertimeRequestController::class, 'index'])->name('overtime-requests.index');
    Route::post('/overtime-requests', [EmployeeOvertimeRequestController::class, 'store'])->name('overtime-requests.store');
    Route::post('/overtime-requests/{overtimeRequest}/cancel', [EmployeeOvertimeRequestController::class, 'cancel'])->name('overtime-requests.cancel');
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/overtime-requests/{overtimeRequest}/start', [EmployeeOvertimeSessionController::class, 'start'])->name('overtime-requests.start');
        Route::post('/overtime-sessions/{overtimeSession}/finish', [EmployeeOvertimeSessionController::class, 'finish'])->name('overtime-sessions.finish');
    });

    // Profile & Account Settings (Sprint 14)
    Route::get('/profile', [EmployeeProfileController::class, 'index'])->name('profile.index');
    Route::post('/profile/password', [EmployeeProfileController::class, 'updatePassword'])->name('profile.password');
});

// Authorized Private Serving & In-App Notification Routes (Sprint 08, 10, & 13)
Route::middleware(['auth'])->group(function () {
    Route::get('/attendance/selfie/{record}/{type}', [AttendanceSelfieController::class, 'show'])->name('attendance.selfie');
    Route::get('/leave-requests/attachment/{leaveRequest}', [LeaveAttachmentController::class, 'show'])->name('leave-requests.attachment');
    Route::get('/overtime-sessions/{overtimeSession}/selfie/{type}', [OvertimeSelfieController::class, 'show'])->name('overtime-sessions.selfie');

    // In-App Notifications (Sprint 13)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::get('/api/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
});
