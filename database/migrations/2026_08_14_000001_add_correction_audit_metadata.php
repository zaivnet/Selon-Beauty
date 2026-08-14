<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->text('reason')->nullable()->after('after_data');
            $table->json('metadata')->nullable()->after('reason');
            $table->index(['action', 'created_at'], 'idx_audit_action_created');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->timestamp('corrected_at')->nullable()->after('is_manually_adjusted');
            $table->foreignId('corrected_by')->nullable()->after('corrected_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('overtime_sessions', function (Blueprint $table) {
            $table->timestamp('corrected_at')->nullable()->after('completed_at');
            $table->foreignId('corrected_by')->nullable()->after('corrected_at')->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by_user_id')->nullable()->after('corrected_by')->constrained('users')->nullOnDelete();
            $table->string('completion_source', 30)->nullable()->after('completed_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('overtime_sessions', function (Blueprint $table) {
            $table->dropForeign(['corrected_by']);
            $table->dropForeign(['completed_by_user_id']);
            $table->dropColumn(['corrected_at', 'corrected_by', 'completed_by_user_id', 'completion_source']);
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropForeign(['corrected_by']);
            $table->dropColumn(['corrected_at', 'corrected_by']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_action_created');
            $table->dropColumn(['reason', 'metadata']);
        });
    }
};
