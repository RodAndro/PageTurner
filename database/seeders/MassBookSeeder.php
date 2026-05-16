<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Book;
use App\Models\Category;

class MassBookSeeder extends Seeder
{
    private const CHUNK_SIZE = 5000; // Optimal for MySQL / PostgreSQL
    private const TOTAL_RECORDS = 1000000;
    
    public function run(): void
    {
        if (Category::query()->count() === 0) {
            Category::factory()->count(10)->create();
        }

        $chunkSize = DB::connection()->getDriverName() === 'sqlite' ? 250 : self::CHUNK_SIZE;

        Log::info('Starting mass book seeding', [
            'total_records' => self::TOTAL_RECORDS,
            'chunk_size' => $chunkSize,
        ]);

        $startTime = microtime(true);
        $inserted = 0;
        
        // Use chunked batch inserts for memory efficiency
        while ($inserted < self::TOTAL_RECORDS) {
            $batchSize = min($chunkSize, self::TOTAL_RECORDS - $inserted);
            
            $books = Book::factory()->count($batchSize)->raw();
            
            // Raw batch insert for maximum throughput
            DB::table('books')->insert($books);
            
            $inserted += $batchSize;
            
            // Progress logging
            $progress = round(($inserted / self::TOTAL_RECORDS) * 100, 2);
            $elapsedTime = microtime(true) - $startTime;
            $rate = round($inserted / $elapsedTime, 2);
            
            Log::info("Mass seeding progress", [
                'inserted' => $inserted,
                'progress_percent' => $progress,
                'elapsed_seconds' => round($elapsedTime, 2),
                'records_per_second' => $rate,
            ]);
            
            // Force garbage collection every 10 chunks
            if ($inserted % ($chunkSize * 10) === 0) {
                unset($books);
                gc_collect_cycles();
            }
        }
        
        $totalTime = microtime(true) - $startTime;
        
        Log::info('Mass book seeding completed', [
            'total_records' => $inserted,
            'total_time_seconds' => round($totalTime, 2),
            'records_per_second' => round($inserted / $totalTime, 2),
            'avg_time_per_1000' => round(($totalTime / $inserted) * 1000, 4),
        ]);
    }
}
