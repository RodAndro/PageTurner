<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('feature')->default('recommendation')->index();
            $table->string('provider')->index();
            $table->string('model')->nullable();
            $table->unsignedInteger('tokens_used')->default(0);
            $table->decimal('cost_estimate', 10, 6)->default(0);
            $table->boolean('success')->default(true)->index();
            $table->unsignedInteger('latency_ms')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['provider', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->nullable()->index();
            $table->string('status')->default('completed')->index();
            $table->text('prompt');
            $table->json('response')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('ai_usage_logs');
    }
};
