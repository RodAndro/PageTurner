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
        // Materialized views are PostgreSQL-specific; skip for SQLite
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'pgsql') {
            return;
        }
        
        $sql = "
            CREATE MATERIALIZED VIEW bestsellers AS
            SELECT 
                b.id,
                b.title,
                b.author,
                b.isbn,
                b.price,
                b.category_id,
                c.name as category_name,
                b.publication_date,
                b.rating,
                b.stock_quantity,
                oi.total_sold,
                oi.total_revenue,
                oi.avg_rating,
                oi.review_count,
                oi.popularity_score,
                oi.best_seller_rank,
                oi.sales_velocity,
                oi.recent_sales_trend,
                oi.created_at as last_updated
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.id
            LEFT JOIN (
                SELECT 
                    oi.book_id,
                    SUM(oi.quantity) as total_sold,
                    SUM(oi.quantity * oi.price) as total_revenue,
                    AVG(r.rating) as avg_rating,
                    COUNT(r.id) as review_count,
                    -- Calculate popularity score (sales * rating * recency)
                    (SUM(oi.quantity) * COALESCE(AVG(r.rating), 0) * 
                     EXP(-DATEDIFF(CURRENT_DATE, MAX(oi.created_at)) / 365)) as popularity_score,
                    -- Rank within category
                    RANK() OVER (PARTITION BY b.category_id ORDER BY SUM(oi.quantity) DESC) as best_seller_rank,
                    -- Sales velocity (units per day since publication)
                    CASE 
                        WHEN b.publication_date IS NOT NULL 
                        THEN SUM(oi.quantity) / GREATEST(DATEDIFF(CURRENT_DATE, b.publication_date), 1)
                        ELSE 0 
                    END as sales_velocity,
                    -- Recent sales trend (last 90 days vs previous 90 days)
                    CASE 
                        WHEN COUNT(CASE WHEN oi.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 90 DAY) THEN 1 END) > 0
                        THEN 
                            (SUM(CASE WHEN oi.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 90 DAY) THEN oi.quantity END) /
                             COUNT(CASE WHEN oi.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 90 DAY) THEN 1 END)) -
                            (SUM(CASE WHEN oi.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 180 DAY) 
                                     AND oi.created_at < DATE_SUB(CURRENT_DATE, INTERVAL 90 DAY) THEN oi.quantity END) /
                             COUNT(CASE WHEN oi.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 180 DAY) 
                                     AND oi.created_at < DATE_SUB(CURRENT_DATE, INTERVAL 90 DAY) THEN 1 END))
                        ELSE 0
                    END as recent_sales_trend,
                    MAX(oi.created_at) as last_updated
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                LEFT JOIN reviews r ON r.book_id = oi.book_id
                WHERE o.status IN ('delivered', 'shipped', 'processing')
                  AND oi.book_id IS NOT NULL
                GROUP BY oi.book_id
            ) oi ON b.id = oi.book_id
            WHERE b.stock_quantity > 0
              AND b.publication_date IS NOT NULL
            HAVING oi.total_sold > 0
        ";

        DB::statement($sql);

        // Create indexes for the materialized view
        DB::statement("
            CREATE INDEX idx_bestsellers_category_rank 
            ON bestsellers (category_id, best_seller_rank)
        ");

        DB::statement("
            CREATE INDEX idx_bestsellers_popularity 
            ON bestsellers (popularity_score DESC)
        ");

        DB::statement("
            CREATE INDEX idx_bestsellers_sales_velocity 
            ON bestsellers (sales_velocity DESC)
        ");

        DB::statement("
            CREATE INDEX idx_bestsellers_recent_sales 
            ON bestsellers (recent_sales_trend DESC)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Materialized views are PostgreSQL-specific; skip for SQLite
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'pgsql') {
            return;
        }
        
        DB::statement("DROP MATERIALIZED VIEW IF EXISTS bestsellers");
        
        DB::statement("DROP INDEX IF EXISTS idx_bestsellers_category_rank");
        DB::statement("DROP INDEX IF EXISTS idx_bestsellers_popularity");
        DB::statement("DROP INDEX IF EXISTS idx_bestsellers_sales_velocity");
        DB::statement("DROP INDEX IF EXISTS idx_bestsellers_recent_sales");
    }
};
