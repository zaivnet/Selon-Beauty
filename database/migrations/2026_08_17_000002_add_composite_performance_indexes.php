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
        // 1. attendance_records composite index for multi-outlet queries
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->index(['outlet_id', 'work_date'], 'att_outlet_date_idx');
        });

        // 2. employees composite index for status & outlet scoping
        Schema::table('employees', function (Blueprint $table) {
            $table->index(['outlet_id', 'status'], 'emp_outlet_status_idx');
        });

        // 3. overtime_requests composite index for employee status lookups
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->index(['employee_id', 'status'], 'ot_emp_status_idx');
        });

        // 4. overtime_sessions composite index for active session lookups
        Schema::table('overtime_sessions', function (Blueprint $table) {
            $table->index(['employee_id', 'status'], 'ot_sess_emp_status_idx');
        });

        // 5. leave_requests composite index for range & status lookups
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->index(['employee_id', 'status', 'start_date', 'end_date'], 'leave_emp_status_dates_idx');
        });

        // 6. shift_swap_requests composite indexes for requester/target status lookups
        Schema::table('shift_swap_requests', function (Blueprint $table) {
            $table->index(['requester_employee_id', 'status'], 'swap_req_status_idx');
            $table->index(['target_employee_id', 'status'], 'swap_tgt_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropIndex('att_outlet_date_idx');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('emp_outlet_status_idx');
        });

        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->dropIndex('ot_emp_status_idx');
        });

        Schema::table('overtime_sessions', function (Blueprint $table) {
            $table->dropIndex('ot_sess_emp_status_idx');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropIndex('leave_emp_status_dates_idx');
        });

        Schema::table('shift_swap_requests', function (Blueprint $table) {
            $table->dropIndex('swap_req_status_idx');
            $table->dropIndex('swap_tgt_status_idx');
        });
    }
};
