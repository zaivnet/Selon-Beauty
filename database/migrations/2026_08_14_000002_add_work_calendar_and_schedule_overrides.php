<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->string('type', 30)->default('company_holiday')->after('date')->index();
            $table->boolean('is_working_day')->default(false)->after('description');
            $table->boolean('applies_to_all_employees')->default(true)->after('is_working_day');
            $table->foreignId('created_by')->nullable()->after('applies_to_all_employees')->constrained('users')->nullOnDelete();
        });

        Schema::create('employee_schedule_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('date');
            $table->string('override_type', 20)->index();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->text('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_schedule_overrides');

        Schema::table('holidays', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['type', 'is_working_day', 'applies_to_all_employees', 'created_by']);
        });
    }
};
