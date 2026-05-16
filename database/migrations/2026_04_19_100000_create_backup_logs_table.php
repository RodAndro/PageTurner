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
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['manual', 'scheduled', 'health-check'])->default('scheduled');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('backup_name')->nullable();
            $table->string('backup_file_path')->nullable();
            $table->integer('file_size_mb')->nullable(); // Size in MB
            $table->integer('duration_seconds')->nullable(); // How long the backup took
            $table->integer('total_rows')->nullable(); // Estimated total database rows
            $table->text('error_message')->nullable();
            $table->json('details')->nullable(); // Additional metadata (disks used, tables included, etc.)
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            // Indexes for fast queries
            $table->index('status');
            $table->index('type');
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });

        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('task_name'); // E.g., 'backup:run', 'session:cleanup'
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->string('command')->nullable(); // The artisan command
            $table->text('output')->nullable(); // Command output
            $table->text('error_message')->nullable();
            $table->integer('duration_seconds')->nullable(); // How long the task took
            $table->boolean('was_manual')->default(false); // Was it triggered manually?
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('task_name');
            $table->index('status');
            $table->index(['task_name', 'status']);
            $table->index('created_at');
        });

        Schema::create('backup_health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('backup_name');
            $table->enum('status', ['healthy', 'unhealthy'])->default('healthy');
            $table->integer('age_days')->nullable(); // Days since last backup
            $table->integer('storage_mb')->nullable(); // Total storage used
            $table->text('issues')->nullable(); // JSON array of issues found
            $table->dateTime('last_checked_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('backup_name');
            $table->index('status');
            $table->index('last_checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
        Schema::dropIfExists('maintenance_logs');
        Schema::dropIfExists('backup_health_checks');
    }
};
