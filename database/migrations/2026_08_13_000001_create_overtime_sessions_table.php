<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('overtime_request_id')->unique()->constrained('overtime_requests')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('work_schedule_id')->nullable()->constrained('work_schedules')->nullOnDelete();
            $table->date('work_date');
            $table->string('status', 30)->default('active');
            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->decimal('check_in_accuracy_meters', 8, 2)->nullable();
            $table->decimal('check_in_distance_meters', 10, 2)->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->decimal('check_out_accuracy_meters', 8, 2)->nullable();
            $table->decimal('check_out_distance_meters', 10, 2)->nullable();
            $table->string('check_in_selfie_path')->nullable();
            $table->string('check_out_selfie_path')->nullable();
            $table->unsignedInteger('actual_minutes')->default(0);
            $table->unsignedInteger('credited_minutes')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'work_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_sessions');
    }
};
