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
        Schema::create('backup_records', function (Blueprint $table) {
            $table->id();
            $table->string('backup_uuid', 64)->unique();
            $table->string('type', 30)->default('full')->index(); // database, full
            $table->string('file_path', 255);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('checksum', 128)->nullable();
            $table->string('status', 30)->default('creating')->index(); // creating, completed, failed, deleted
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_pre_restore')->default(false)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_records');
    }
};
