<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create outlets master table
        if (! Schema::hasTable('outlets')) {
            Schema::create('outlets', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('code', 50)->unique('outlets_code_unique');
                $table->text('address')->nullable();
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->unsignedInteger('radius_meters')->default(100);
                $table->unsignedInteger('max_accuracy_meters')->default(100);
                $table->boolean('is_active')->default(true)->index('outlets_is_active_idx');
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // 2. Add outlet_id to employees table if not present
        if (! Schema::hasColumn('employees', 'outlet_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('outlet_id')
                    ->nullable()
                    ->after('job_title_id')
                    ->constrained('outlets', 'id', 'emp_outlet_fk')
                    ->nullOnDelete();
            });
        }

        // 3. Add outlet_id to users table if not present
        if (! Schema::hasColumn('users', 'outlet_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('outlet_id')
                    ->nullable()
                    ->after('employee_id')
                    ->constrained('outlets', 'id', 'usr_outlet_fk')
                    ->nullOnDelete();
            });
        }

        // 4. Add outlet_id to attendance_records table if not present
        if (! Schema::hasColumn('attendance_records', 'outlet_id')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->foreignId('outlet_id')
                    ->nullable()
                    ->after('attendance_location_id')
                    ->constrained('outlets', 'id', 'att_outlet_fk')
                    ->nullOnDelete();
            });
        }

        // 5. Backfill: Create Default Outlet from existing active location & update existing records
        $activeLocation = null;
        if (Schema::hasTable('attendance_locations')) {
            $activeLocation = DB::table('attendance_locations')->where('is_active', true)->first();
        }

        $defaultOutletName = $activeLocation?->name ?? 'SELON PUSAT';
        $defaultAddress = $activeLocation?->address ?? null;
        $defaultLat = $activeLocation?->latitude ?? -6.2000000;
        $defaultLng = $activeLocation?->longitude ?? 106.8166660;
        $defaultRadius = $activeLocation?->radius_meters ?? 100;
        $defaultMaxAcc = $activeLocation?->max_accuracy_meters ?? 100;

        $existingDefault = DB::table('outlets')->where('code', 'PUSAT')->first();
        if (! $existingDefault) {
            $defaultOutletId = DB::table('outlets')->insertGetId([
                'name' => $defaultOutletName,
                'code' => 'PUSAT',
                'address' => $defaultAddress,
                'latitude' => $defaultLat,
                'longitude' => $defaultLng,
                'radius_meters' => $defaultRadius,
                'max_accuracy_meters' => $defaultMaxAcc,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $defaultOutletId = $existingDefault->id;
        }

        // Safe Backfill
        DB::table('employees')->whereNull('outlet_id')->update(['outlet_id' => $defaultOutletId]);
        DB::table('users')->where('role', 'admin')->whereNull('outlet_id')->update(['outlet_id' => $defaultOutletId]);
        DB::table('attendance_records')->whereNull('outlet_id')->update(['outlet_id' => $defaultOutletId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('attendance_records', 'outlet_id')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->dropForeign('att_outlet_fk');
                $table->dropColumn('outlet_id');
            });
        }

        if (Schema::hasColumn('users', 'outlet_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign('usr_outlet_fk');
                $table->dropColumn('outlet_id');
            });
        }

        if (Schema::hasColumn('employees', 'outlet_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropForeign('emp_outlet_fk');
                $table->dropColumn('outlet_id');
            });
        }

        Schema::dropIfExists('outlets');
    }
};
