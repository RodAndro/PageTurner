<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates api_rate_limits table to track API rate limiting and throttling events.
     */
    public function up(): void
    {
        Schema::create('api_rate_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('ip_address')->nullable();
            $table->string('endpoint')->nullable(); // e.g., '/api/books', '/api/orders'
            $table->string('method')->default('GET'); // HTTP method
            $table->integer('requests_count')->default(1); // Number of requests in window
            $table->integer('limit')->nullable(); // Configured rate limit
            $table->integer('remaining')->nullable(); // Remaining requests
            $table->integer('reset_at_timestamp')->nullable(); // Unix timestamp for reset
            $table->boolean('is_throttled')->default(false);
            $table->enum('status', ['allowed', 'throttled', 'blocked'])->default('allowed');
            $table->text('reason')->nullable(); // Reason for throttling
            $table->timestamp('throttled_until')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index(['ip_address', 'created_at']);
            $table->index(['endpoint']);
            $table->index(['status', 'created_at']);
            $table->index(['is_throttled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_rate_limits');
    }
};
