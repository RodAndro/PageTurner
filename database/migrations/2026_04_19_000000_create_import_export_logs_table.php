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
        // Import Logs Table
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('module_type'); // 'books', 'users', etc.
            $table->string('file_name');
            $table->string('file_path');
            $table->string('status'); // 'pending', 'processing', 'completed', 'failed'
            $table->integer('total_rows')->default(0);
            $table->integer('successful_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->longText('error_details')->nullable(); // JSON with detailed error messages
            $table->longText('failure_report')->nullable(); // CSV or JSON with failed records
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'module_type', 'status']);
        });

        // Export Logs Table
        Schema::create('export_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('module_type'); // 'books', 'orders', 'users', etc.
            $table->string('export_format'); // 'csv', 'xlsx', 'pdf'
            $table->string('file_name');
            $table->string('file_path');
            $table->string('status'); // 'pending', 'processing', 'completed', 'failed'
            $table->integer('total_records')->default(0);
            $table->longText('filters')->nullable(); // JSON filters applied
            $table->string('selected_columns')->nullable(); // JSON array of selected columns
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->longText('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'module_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_logs');
        Schema::dropIfExists('export_logs');
    }
};
