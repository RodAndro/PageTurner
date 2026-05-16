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
        Schema::create('books_partitioned', function (Blueprint $table) {
            $table->id();
            $table->string('title', 500);
            $table->string('author', 200);
            $table->string('isbn', 13)->unique();
            $table->decimal('price', 8, 2);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('publisher', 200)->nullable();
            $table->date('publication_date')->nullable();
            $table->string('language', 50)->default('English');
            $table->unsignedInteger('page_count')->nullable();
            $table->string('format', 50)->default('Paperback');
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->string('cover_image', 500)->nullable();
            $table->text('metadata')->nullable(); // JSON for additional data
            // Use SQLite-compatible expression for publication_year
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'sqlite') {
                $table->integer('publication_year')->storedAs("cast(strftime('%Y', publication_date) as integer)");
            } else {
                $table->integer('publication_year')->storedAs('year(publication_date)');
            }
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['publication_year']);
            $table->index(['category_id']);
            $table->index(['author']);
            $table->index(['isbn']);
            $table->index(['price']);
            $table->index(['rating']);
            $table->index(['created_at']);
            
            // Composite indexes for common queries
            $table->index(['publication_year', 'category_id']);
            $table->index(['publication_year', 'price']);
            $table->index(['category_id', 'rating']);
        });

        // Create partitions by publication year (1900-2030)
        $this->createPartitions();

        // Copy existing data if books table exists
        if (Schema::hasTable('books')) {
            $this->migrateExistingData();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop partitions first
        $this->dropPartitions();
        
        Schema::dropIfExists('books_partitioned');
    }

    /**
     * Create yearly partitions (only for PostgreSQL)
     */
    protected function createPartitions(): void
    {
        // SQLite doesn't support table partitioning, only PostgreSQL does
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'pgsql') {
            return;
        }
        
        $startYear = 1900;
        $endYear = 2030;
        
        for ($year = $startYear; $year <= $endYear; $year++) {
            $partitionName = "books_partition_{$year}";
            
            DB::statement("
                CREATE TABLE IF NOT EXISTS {$partitionName} (
                    LIKE books_partitioned INCLUDING ALL
                )
            ");
            
            DB::statement("
                ALTER TABLE books_partitioned 
                ATTACH PARTITION {$partitionName}
                FOR VALUES FROM ({$year}-01-01') TO ({$year}-12-31')
            ");
        }
    }

    /**
     * Drop all partitions (only for PostgreSQL)
     */
    protected function dropPartitions(): void
    {
        // SQLite doesn't support table partitioning, only PostgreSQL does
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'pgsql') {
            return;
        }
        
        $startYear = 1900;
        $endYear = 2030;
        
        for ($year = $startYear; $year <= $endYear; $year++) {
            $partitionName = "books_partition_{$year}";
            
            DB::statement("
                ALTER TABLE books_partitioned 
                DETACH PARTITION {$partitionName}
            ");
            
            DB::statement("
                DROP TABLE IF EXISTS {$partitionName}
            ");
        }
    }

    /**
     * Migrate existing data to partitioned table
     */
    protected function migrateExistingData(): void
    {
        // Migrate data in chunks to avoid memory issues
        $offset = 0;
        $chunkSize = 10000;
        
        do {
            $books = DB::table('books')
                ->offset($offset)
                ->limit($chunkSize)
                ->get();
            
            if ($books->isEmpty()) {
                break;
            }
            
            // Transform and insert into partitioned table
            $transformedBooks = $books->map(function ($book) {
                return [
                    'title' => $book->title,
                    'author' => $book->author,
                    'isbn' => $book->isbn,
                    'price' => $book->price,
                    'description' => $book->description,
                    'category_id' => $book->category_id,
                    'publisher' => $book->publisher,
                    'publication_date' => $book->publication_date,
                    'language' => $book->language ?? 'English',
                    'page_count' => $book->page_count,
                    'format' => $book->format ?? 'Paperback',
                    'rating' => $book->rating,
                    'stock_quantity' => $book->stock_quantity ?? 0,
                    'cover_image' => $book->cover_image,
                    'metadata' => $book->metadata,
                    'publication_year' => $book->publication_date ? date('Y', strtotime($book->publication_date)) : null,
                    'created_at' => $book->created_at,
                    'updated_at' => $book->updated_at,
                ];
            })->toArray();
            
            // Insert into appropriate partitions
            foreach ($transformedBooks as $book) {
                $year = $book['publication_year'];
                if ($year) {
                    $partitionName = "books_partition_{$year}";
                    
                    DB::table($partitionName)->insert($book);
                } else {
                    // Handle null publication year
                    DB::table('books_partitioned')->insert($book);
                }
            }
            
            $offset += $chunkSize;
            
        } while (true);
    }
};
