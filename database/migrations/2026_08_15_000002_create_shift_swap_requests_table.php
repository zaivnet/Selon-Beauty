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
        Schema::create('shift_swap_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('target_employee_id')->constrained('employees')->cascadeOnDelete();

            $table->date('requester_work_date');
            $table->date('target_work_date');

            $table->foreignId('requester_original_shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignId('target_original_shift_id')->constrained('shifts')->cascadeOnDelete();

            $table->string('requester_original_schedule_type', 30)->default('work');
            $table->string('target_original_schedule_type', 30)->default('work');

            $table->string('status', 30)->default('pending_target')->index();
            $table->text('requester_reason')->nullable();

            $table->timestamp('target_responded_at')->nullable();
            $table->text('target_response_reason')->nullable();

            $table->timestamp('admin_responded_at')->nullable();
            $table->foreignId('admin_responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_response_reason')->nullable();

            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['requester_employee_id', 'requester_work_date']);
            $table->index(['target_employee_id', 'target_work_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_swap_requests');
    }
};
