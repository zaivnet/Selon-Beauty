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
        // 1. Add auto checkout configuration fields to shifts table (defaults to false for existing shifts)
        Schema::table('shifts', function (Blueprint $table) {
            $table->boolean('auto_checkout_enabled')->default(false)->after('crosses_midnight');
            $table->unsignedInteger('auto_checkout_grace_minutes')->default(10)->after('auto_checkout_enabled');
        });

        // 2. Add checkout_source, auto_checkout_boundary, and metric snapshot fields to attendance_records table
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->string('checkout_source', 30)->nullable()->after('check_out_user_agent');
            $table->timestamp('auto_checkout_boundary')->nullable()->after('checkout_source');
            $table->timestamp('scheduled_shift_end_at')->nullable()->after('auto_checkout_boundary');
            $table->unsignedInteger('break_minutes_snapshot')->nullable()->after('scheduled_shift_end_at');
            $table->index(['check_out_at', 'auto_checkout_boundary'], 'att_checkout_boundary_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropIndex('att_checkout_boundary_idx');
            $table->dropColumn([
                'checkout_source',
                'auto_checkout_boundary',
                'scheduled_shift_end_at',
                'break_minutes_snapshot',
            ]);
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['auto_checkout_enabled', 'auto_checkout_grace_minutes']);
        });
    }
};
