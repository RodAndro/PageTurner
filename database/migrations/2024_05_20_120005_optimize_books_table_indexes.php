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
        // Only run if books table exists (it might have been renamed to books_partitioned)
        if (!Schema::hasTable('books')) {
            return;
        }
        
        Schema::table('books', function (Blueprint $table) {
            // Composite index for common filtering patterns
            $table->index(
                ['category_id', 'published_at', 'is_active'],
                'idx_books_catalog_filter'
            );
            
            // Covering index for price range queries
            $table->index(
                ['price', 'stock_quantity', 'id'],
                'idx_books_price_stock'
            );
            
            // Full-text index for search (MySQL 5.7+ / PostgreSQL) - skip for SQLite
            $driver = Schema::getConnection()->getDriverName();
            if ($driver !== 'sqlite') {
                $table->fullText(['title', 'description'], 'idx_books_fulltext');
            }
            
            // Index for active-book filtering
            $table->index('is_active', 'idx_books_active');
            
            // ISBN lookup index for exact searches
            $table->index('isbn', 'idx_books_isbn_lookup');
            
            // Composite index for sorting optimization
            $table->index(
                ['published_at', 'id'],
                'idx_books_published_sort'
            );
        });

        // Analyze table for query optimizer (MySQL-specific)
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ANALYZE TABLE books');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex('idx_books_catalog_filter');
            $table->dropIndex('idx_books_price_stock');
            
            // Drop fulltext index only if it was created (not SQLite)
            $driver = Schema::getConnection()->getDriverName();
            if ($driver !== 'sqlite') {
                $table->dropIndex('idx_books_fulltext');
            }
            
            $table->dropIndex('idx_books_active');
            $table->dropIndex('idx_books_isbn_lookup');
            $table->dropIndex('idx_books_published_sort');
        });
    }
};
