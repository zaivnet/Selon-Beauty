<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_schedules', function (Blueprint $table) {
            $table->foreignId('work_outlet_id')->nullable()->after('shift_id')->constrained('outlets')->nullOnDelete();
            $table->index(['work_date', 'work_outlet_id', 'employee_id'], 'idx_work_schedules_date_outlet_emp');
        });

        Schema::table('employee_schedule_overrides', function (Blueprint $table) {
            $table->foreignId('work_outlet_id')->nullable()->after('shift_id')->constrained('outlets')->nullOnDelete();
            $table->index(['date', 'work_outlet_id', 'employee_id'], 'idx_schedule_overrides_date_outlet_emp');
        });
    }

    public function down(): void
    {
        Schema::table('employee_schedule_overrides', function (Blueprint $table) {
            $table->dropIndex('idx_schedule_overrides_date_outlet_emp');
            $table->dropConstrainedForeignId('work_outlet_id');
        });

        Schema::table('work_schedules', function (Blueprint $table) {
            $table->dropIndex('idx_work_schedules_date_outlet_emp');
            $table->dropConstrainedForeignId('work_outlet_id');
        });
    }
};
