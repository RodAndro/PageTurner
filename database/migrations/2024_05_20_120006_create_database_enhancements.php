<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create search index queue table for Scout
        Schema::create('search_index_queue', function (Blueprint $table) {
            $table->id();
            $table->string('model_type', 100);
            $table->unsignedBigInteger('model_id');
            $table->string('index_type', 50); // fulltext, spatial, etc.
            $table->json('index_data');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed']);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('retry_at')->nullable();
            
            $table->index(['model_type', 'model_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['model_type', 'index_type']);
        });

        // Create query performance logs table
        Schema::create('query_performance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('connection_name', 100);
            $table->string('query_hash', 64); // MD5 hash of query
            $table->longText('query_sql');
            $table->json('bindings')->nullable();
            $table->decimal('execution_time_ms', 10, 4); // 4 decimal places
            $table->integer('rows_examined')->default(0);
            $table->integer('rows_returned')->default(0);
            $table->text('explain_plan')->nullable(); // Query explain output
            $table->string('query_type', 50); // SELECT, INSERT, UPDATE, DELETE
            $table->string('table_name', 100);
            $table->json('index_usage')->nullable(); // Which indexes were used
            $table->timestamp('executed_at');
            
            $table->index(['connection_name', 'executed_at']);
            $table->index(['execution_time_ms']);
            $table->index(['query_type', 'table_name']);
            $table->index(['query_hash']);
        });

        // Create enhanced books table with partitioning support
        if (!Schema::hasTable('books_enhanced')) {
            Schema::create('books_enhanced', function (Blueprint $table) {
                $table->id();
                $table->string('title', 500);
                $table->string('author', 200);
                $table->string('isbn', 13)->unique();
                $table->decimal('price', 8, 2);
                $table->text('description')->nullable();
                $table->unsignedBigInteger('category_id');
                $table->string('publisher', 200);
                $table->date('publication_date');
                $table->string('language', 50);
                $table->string('format', 50);
                $table->unsignedInteger('page_count');
                $table->decimal('rating', 3, 2);
                $table->unsignedInteger('stock_quantity');
                $table->string('cover_image', 500);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                // Enhanced indexes for performance
                $table->index(['category_id', 'publication_date', 'is_active'], 'idx_enhanced_catalog_filter');
                $table->index(['price', 'stock_quantity', 'id'], 'idx_enhanced_price_stock');
                
                // Fulltext index - skip for SQLite
                $driver = Schema::getConnection()->getDriverName();
                if ($driver !== 'sqlite') {
                    $table->fullText(['title', 'description'], 'idx_enhanced_fulltext');
                }
                
                $table->index('is_active', 'idx_enhanced_active');
                $table->index('isbn', 'idx_enhanced_isbn');
                $table->index(['publication_date', 'id'], 'idx_enhanced_published_sort');
                $table->index(['format', 'publication_date'], 'idx_enhanced_format_date');
                $table->index(['language', 'publication_date'], 'idx_enhanced_language_date');
            });
        }

        // Create materialized view refresh strategy table
        Schema::create('materialized_view_refresh_log', function (Blueprint $table) {
            $table->id();
            $table->string('view_name', 100);
            $table->enum('refresh_type', ['manual', 'scheduled', 'triggered']);
            $table->text('refresh_sql')->nullable();
            $table->decimal('execution_time_seconds', 10, 4);
            $table->integer('rows_affected')->default(0);
            $table->enum('status', ['started', 'running', 'completed', 'failed']);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('next_refresh_at')->nullable();
            
            $table->index(['view_name', 'status']);
            $table->index(['next_refresh_at']);
        });

        // Create database performance metrics table
        Schema::create('database_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_name', 100);
            $table->string('metric_type', 50); // counter, gauge, histogram
            $table->decimal('value', 15, 6); // Support large values
            $table->string('unit', 20); // ms, seconds, bytes, count, percentage
            $table->json('tags')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('recorded_at');
            
            $table->index(['metric_name', 'recorded_at']);
            $table->index(['metric_type', 'recorded_at']);
        });

        // Add partitioning support to existing books table
        if (Schema::hasTable('books') && !Schema::hasColumn('books', 'partition_key')) {
            Schema::table('books', function (Blueprint $table) {
                $table->string('partition_key', 50)->nullable(); // For manual partition management
                $table->index(['partition_key', 'id']);
            });
        }

        // Create cache invalidation log table
        Schema::create('cache_invalidation_log', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key', 255);
            $table->json('tags')->nullable();
            $table->string('invalidation_reason', 100);
            $table->string('triggered_by', 100); // model_event, manual, scheduled
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamp('invalidated_at');
            
            $table->index(['cache_key', 'invalidated_at']);
            $table->index(['triggered_by', 'invalidated_at']);
        });

        // Log migration completion
        $timestamp = Schema::getConnection()->getDriverName() === 'sqlite' ? 'CURRENT_TIMESTAMP' : 'NOW()';
        DB::statement("INSERT INTO database_performance_metrics (metric_name, metric_type, value, unit, description, recorded_at) VALUES 
            ('migration_completed', 'counter', 1, 'count', 'Database enhancements migration completed', {$timestamp})");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_index_queue');
        Schema::dropIfExists('query_performance_logs');
        Schema::dropIfExists('books_enhanced');
        Schema::dropIfExists('materialized_view_refresh_log');
        Schema::dropIfExists('database_performance_metrics');
        Schema::dropIfExists('cache_invalidation_log');
        
        // Remove partition key from books table if it exists
        if (Schema::hasTable('books') && Schema::hasColumn('books', 'partition_key')) {
            Schema::table('books', function (Blueprint $table) {
                $table->dropIndex(['partition_key', 'id']);
                $table->dropColumn('partition_key');
            });
        }
        
        // Log migration rollback
        $timestamp = Schema::getConnection()->getDriverName() === 'sqlite' ? 'CURRENT_TIMESTAMP' : 'NOW()';
        DB::statement("INSERT INTO database_performance_metrics (metric_name, metric_type, value, unit, description, recorded_at) VALUES 
            ('migration_rollback', 'counter', 1, 'count', 'Database enhancements migration rollback', {$timestamp})");
    }
};
