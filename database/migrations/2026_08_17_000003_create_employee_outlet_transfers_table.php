<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_outlet_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees', 'id', 'eot_emp_fk')->cascadeOnDelete();
            $table->foreignId('from_outlet_id')->constrained('outlets', 'id', 'eot_from_out_fk')->cascadeOnDelete();
            $table->foreignId('to_outlet_id')->constrained('outlets', 'id', 'eot_to_out_fk')->cascadeOnDelete();
            $table->date('effective_date');
            $table->text('notes')->nullable();
            $table->foreignId('transferred_by_user_id')->constrained('users', 'id', 'eot_actor_fk')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'effective_date'], 'eot_emp_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_outlet_transfers');
    }
};
