<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates scheduled_tasks table for monitoring scheduled task execution.
     */
    public function up(): void
    {
        Schema::create('scheduled_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_name')->unique();
            $table->string('description')->nullable();
            $table->enum('task_type', ['backup', 'cleanup', 'report', 'archive', 'maintenance', 'sync'])->default('maintenance');
            $table->string('cron_expression')->nullable();
            $table->enum('status', ['enabled', 'disabled', 'paused'])->default('enabled');
            $table->enum('last_status', ['success', 'completed', 'failed', 'running', 'pending', 'skipped'])->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failed_at')->nullable();
            $table->integer('run_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->integer('duration_ms')->nullable(); // Last execution duration
            $table->text('last_error_message')->nullable();
            $table->integer('consecutive_failures')->default(0);
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['task_type']);
            $table->index(['status']);
            $table->index(['last_run_at']);
            $table->index(['last_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_tasks');
    }
};
