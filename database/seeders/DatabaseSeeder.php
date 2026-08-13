<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed default application settings only (no dummy employees/attendance)
        DB::table('app_settings')->insertOrIgnore([
            [
                'key' => 'company_name',
                'value' => 'SELON BEAUTY',
                'type' => 'string',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'timezone',
                'value' => 'Asia/Jakarta',
                'type' => 'string',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'selfie_quality',
                'value' => '0.8',
                'type' => 'number',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}

