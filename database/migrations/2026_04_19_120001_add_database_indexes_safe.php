<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Safely adds indexes for frequent query patterns with existence checks.
     * Works with both SQLite and MySQL.
     */
    public function up(): void
    {
        // Books table indexes
        Schema::table('books', function (Blueprint $table) {
            $this->addIndexIfNotExists('books', ['isbn']);
            $this->addIndexIfNotExists('books', ['category_id']);
            $this->addIndexIfNotExists('books', ['created_at']);
        });

        // Orders table indexes
        Schema::table('orders', function (Blueprint $table) {
            $this->addIndexIfNotExists('orders', ['user_id']);
            $this->addIndexIfNotExists('orders', ['created_at']);
            $this->addIndexIfNotExists('orders', ['status']);
            $this->addIndexIfNotExists('orders', ['user_id', 'status']);
        });

        // Order items table indexes
        Schema::table('order_items', function (Blueprint $table) {
            $this->addIndexIfNotExists('order_items', ['order_id']);
            $this->addIndexIfNotExists('order_items', ['book_id']);
        });

        // Reviews table indexes
        Schema::table('reviews', function (Blueprint $table) {
            $this->addIndexIfNotExists('reviews', ['user_id']);
            $this->addIndexIfNotExists('reviews', ['book_id']);
            $this->addIndexIfNotExists('reviews', ['user_id', 'book_id']);
            $this->addIndexIfNotExists('reviews', ['rating']);
        });

        // Categories table indexes
        Schema::table('categories', function (Blueprint $table) {
            $this->addIndexIfNotExists('categories', ['name']);
        });

        // Audit logs table indexes (if exists)
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $this->addIndexIfNotExists('audit_logs', ['user_id']);
                $this->addIndexIfNotExists('audit_logs', ['action']);
                $this->addIndexIfNotExists('audit_logs', ['entity_type']);
                $this->addIndexIfNotExists('audit_logs', ['created_at']);
                $this->addIndexIfNotExists('audit_logs', ['entity_type', 'entity_id', 'created_at']);
                $this->addIndexIfNotExists('audit_logs', ['user_id', 'action', 'created_at']);
            });
        }

        // Import/Export logs table indexes (if exists)
        if (Schema::hasTable('import_export_logs')) {
            Schema::table('import_export_logs', function (Blueprint $table) {
                $this->addIndexIfNotExists('import_export_logs', ['user_id']);
                $this->addIndexIfNotExists('import_export_logs', ['operation_type']);
                $this->addIndexIfNotExists('import_export_logs', ['created_at']);
            });
        }

        // Backup logs table indexes (if exists)
        if (Schema::hasTable('backup_logs')) {
            Schema::table('backup_logs', function (Blueprint $table) {
                $this->addIndexIfNotExists('backup_logs', ['status']);
                $this->addIndexIfNotExists('backup_logs', ['created_at']);
            });
        }
    }

    /**
     * Safely add an index if it doesn't already exist.
     * 
     * @param string $table
     * @param array $columns
     * @return void
     */
    private function addIndexIfNotExists(string $table, array $columns): void
    {
        // Generate index name
        $indexName = $table . '_' . implode('_', $columns) . '_index';
        
        // Check if index exists (works for SQLite)
        $indexExists = false;
        
        if (DB::getDriverName() === 'sqlite') {
            // SQLite: Check pragma index info
            $indexes = DB::select("PRAGMA index_list($table)");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    $indexExists = true;
                    break;
                }
            }
        } else {
            // MySQL/MariaDB/PostgreSQL: Use information schema
            $existingIndexes = DB::select(
                "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS 
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?",
                [DB::getDatabaseName(), $table, $indexName]
            );
            $indexExists = count($existingIndexes) > 0;
        }

        // Add index if it doesn't exist
        if (!$indexExists) {
            Schema::table($table, function (Blueprint $table) use ($columns) {
                $table->index($columns);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safely drop indexes if they exist
        Schema::table('books', function (Blueprint $table) {
            $this->dropIndexIfExists('books', ['isbn']);
            $this->dropIndexIfExists('books', ['category_id']);
            $this->dropIndexIfExists('books', ['created_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $this->dropIndexIfExists('orders', ['user_id']);
            $this->dropIndexIfExists('orders', ['created_at']);
            $this->dropIndexIfExists('orders', ['status']);
            $this->dropIndexIfExists('orders', ['user_id', 'status']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $this->dropIndexIfExists('order_items', ['order_id']);
            $this->dropIndexIfExists('order_items', ['book_id']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $this->dropIndexIfExists('reviews', ['user_id']);
            $this->dropIndexIfExists('reviews', ['book_id']);
            $this->dropIndexIfExists('reviews', ['user_id', 'book_id']);
            $this->dropIndexIfExists('reviews', ['rating']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $this->dropIndexIfExists('categories', ['name']);
        });

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $this->dropIndexIfExists('audit_logs', ['user_id']);
                $this->dropIndexIfExists('audit_logs', ['action']);
                $this->dropIndexIfExists('audit_logs', ['entity_type']);
                $this->dropIndexIfExists('audit_logs', ['created_at']);
                $this->dropIndexIfExists('audit_logs', ['entity_type', 'entity_id', 'created_at']);
                $this->dropIndexIfExists('audit_logs', ['user_id', 'action', 'created_at']);
            });
        }

        if (Schema::hasTable('import_export_logs')) {
            Schema::table('import_export_logs', function (Blueprint $table) {
                $this->dropIndexIfExists('import_export_logs', ['user_id']);
                $this->dropIndexIfExists('import_export_logs', ['operation_type']);
                $this->dropIndexIfExists('import_export_logs', ['created_at']);
            });
        }

        if (Schema::hasTable('backup_logs')) {
            Schema::table('backup_logs', function (Blueprint $table) {
                $this->dropIndexIfExists('backup_logs', ['status']);
                $this->dropIndexIfExists('backup_logs', ['created_at']);
            });
        }
    }

    /**
     * Safely drop an index if it exists.
     * 
     * @param string $table
     * @param array $columns
     * @return void
     */
    private function dropIndexIfExists(string $table, array $columns): void
    {
        $indexName = $table . '_' . implode('_', $columns) . '_index';
        
        try {
            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->dropIndex($indexName);
            });
        } catch (\Exception $e) {
            // Index doesn't exist or couldn't be dropped, continue
        }
    }
};
