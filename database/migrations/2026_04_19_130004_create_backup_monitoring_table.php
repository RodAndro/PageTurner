<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates backup_monitoring table to track backup execution and verification.
     */
    public function up(): void
    {
        Schema::create('backup_monitoring', function (Blueprint $table) {
            $table->id();
            $table->string('backup_name')->unique();
            $table->enum('backup_type', ['full', 'incremental', 'differential'])->default('full');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed', 'verified', 'corrupted'])->default('pending');
            $table->string('source_location'); // e.g., database path, file path
            $table->string('destination_location'); // Storage location
            $table->decimal('size_mb', 12, 2)->nullable();
            $table->decimal('duration_seconds', 10, 2)->nullable();
            $table->integer('total_files')->nullable();
            $table->integer('total_tables')->nullable();
            $table->string('compression_type')->nullable(); // gzip, bzip2, etc.
            $table->string('checksum')->nullable(); // SHA256 or similar
            $table->boolean('is_encrypted')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('backup_started_at');
            $table->timestamp('backup_completed_at')->nullable();
            $table->timestamp('verification_started_at')->nullable();
            $table->timestamp('verification_completed_at')->nullable();
            $table->timestamp('next_backup_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retention_days')->default(30);
            $table->timestamp('scheduled_delete_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['status', 'backup_completed_at']);
            $table->index(['backup_type']);
            $table->index(['is_verified']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_monitoring');
    }
};
