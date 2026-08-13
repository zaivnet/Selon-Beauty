<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. work_schedules indexes
        Schema::table('work_schedules', function (Blueprint $table) {
            $table->index(['employee_id', 'work_date'], 'idx_work_schedules_emp_date');
            $table->index(['work_date', 'schedule_type'], 'idx_work_schedules_date_type');
        });

        // 2. attendance_records indexes
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->index(['employee_id', 'work_date'], 'idx_attendance_emp_date');
            $table->index(['work_date', 'status'], 'idx_attendance_date_status');
        });

        // 3. leave_requests indexes
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->index(['employee_id', 'status'], 'idx_leave_emp_status');
            $table->index(['status', 'start_date', 'end_date'], 'idx_leave_status_dates');
        });

        // 4. overtime_requests indexes
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->index(['employee_id', 'work_date'], 'idx_overtime_emp_date');
            $table->index(['status', 'work_date'], 'idx_overtime_status_date');
        });

        // 5. notifications indexes (if notification table exists)
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->index(['notifiable_type', 'notifiable_id', 'read_at', 'created_at'], 'idx_notifications_lookup');
            });
        }

        // 6. audit_logs indexes
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'idx_audit_logs_user_date');
            $table->index(['action'], 'idx_audit_logs_action');
        });

        // 7. backup_records indexes
        Schema::table('backup_records', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'idx_backup_records_status_date');
            $table->index(['type', 'status'], 'idx_backup_records_type_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_schedules', function (Blueprint $table) {
            $table->dropIndex('idx_work_schedules_emp_date');
            $table->dropIndex('idx_work_schedules_date_type');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropIndex('idx_attendance_emp_date');
            $table->dropIndex('idx_attendance_date_status');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropIndex('idx_leave_emp_status');
            $table->dropIndex('idx_leave_status_dates');
        });

        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->dropIndex('idx_overtime_emp_date');
            $table->dropIndex('idx_overtime_status_date');
        });

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropIndex('idx_notifications_lookup');
            });
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_logs_user_date');
            $table->dropIndex('idx_audit_logs_action');
        });

        Schema::table('backup_records', function (Blueprint $table) {
            $table->dropIndex('idx_backup_records_status_date');
            $table->dropIndex('idx_backup_records_type_status');
        });
    }
};
