<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('outlet_access_mode', 20)->default('selected')->after('outlet_id');
        });

        Schema::create('admin_outlet_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'id', 'aoa_user_fk')->cascadeOnDelete();
            $table->foreignId('outlet_id')->constrained('outlets', 'id', 'aoa_outlet_fk')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'outlet_id'], 'aoa_user_outlet_uq');
            $table->index(['outlet_id', 'user_id'], 'aoa_outlet_user_idx');
        });

        $now = now();
        DB::table('users')
            ->where('role', 'admin')
            ->whereNotNull('outlet_id')
            ->orderBy('id')
            ->chunkById(200, function ($admins) use ($now): void {
                DB::table('admin_outlet_assignments')->insertOrIgnore(
                    $admins->map(fn ($admin) => [
                        'user_id' => $admin->id,
                        'outlet_id' => $admin->outlet_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_outlet_assignments');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('outlet_access_mode');
        });
    }
};
