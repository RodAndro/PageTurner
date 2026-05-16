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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index(); // For distributed systems
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('event'); // 'created', 'updated', 'deleted', 'login', 'logout', etc.
            $table->string('auditable_type')->nullable(); // Model class name
            $table->unsignedBigInteger('auditable_id')->nullable(); // Model ID
            $table->string('entity_type')->nullable()->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->string('action')->nullable()->index();
            $table->json('old_values')->nullable(); // Before changes
            $table->json('new_values')->nullable(); // After changes
            $table->json('metadata')->nullable(); // IP, user agent, URL, method, etc.
            $table->text('description')->nullable(); // Human-readable description
            $table->string('level')->default('info'); // info, warning, critical
            $table->string('checksum')->nullable(); // SHA256 hash for tamper detection
            $table->boolean('is_sensitive')->default(false); // Flag for sensitive operations
            $table->dateTime('archived_at')->nullable(); // Moved to archive storage
            $table->timestamps();

            // Indexes for fast queries
            $table->index('user_id');
            $table->index('event');
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('level');
            $table->index('created_at');
            $table->index('is_sensitive');
            
            // Full-text search (MySQL/MariaDB only, not supported in SQLite)
            if (config('database.default') !== 'sqlite') {
                $table->fullText(['description']);
            }
        });

        // Archive table for old logs (1+ year old)
        Schema::create('audit_logs_archive', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event')->index();
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->text('description')->nullable();
            $table->string('level')->default('info');
            $table->string('checksum')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->dateTime('archived_at');
            $table->timestamps();

            // Indexes for archive table
            $table->index('user_id');
            $table->index('created_at');
        });

        Schema::create('audit_log_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_log_id')->constrained('audit_logs')->onDelete('cascade');
            $table->string('alert_type'); // 'email', 'slack', 'discord'
            $table->json('recipients'); // Email addresses or webhook URLs
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->string('error_message')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('alert_type');
        });

        Schema::create('audit_log_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Saved search name
            $table->json('filters'); // Search filters (user, date range, event type, etc.)
            $table->text('query')->nullable(); // Full-text search query
            $table->boolean('is_public')->default(false); // Share with other admins
            $table->timestamps();

            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_log_searches');
        Schema::dropIfExists('audit_log_alerts');
        Schema::dropIfExists('audit_logs_archive');
        Schema::dropIfExists('audit_logs');
    }
};
