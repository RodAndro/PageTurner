<?php

namespace Database\Seeders;

use App\Models\ImportLog;
use Illuminate\Database\Seeder;

class ImportLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates sample import logs for demonstration and testing.
     */
    public function run(): void
    {
        // Sample successful imports
        ImportLog::create([
            'user_id' => 1,
            'filename' => 'books_batch_001.csv',
            'operation_type' => 'books',
            'total_rows' => 150,
            'successful_rows' => 150,
            'failed_rows' => 0,
            'status' => 'completed',
            'file_size_mb' => 2.45,
            'started_at' => now()->subDays(5),
            'completed_at' => now()->subDays(5)->addMinutes(2),
        ]);

        ImportLog::create([
            'user_id' => 1,
            'filename' => 'categories_update.csv',
            'operation_type' => 'categories',
            'total_rows' => 25,
            'successful_rows' => 25,
            'failed_rows' => 0,
            'status' => 'completed',
            'file_size_mb' => 0.35,
            'started_at' => now()->subDays(3),
            'completed_at' => now()->subDays(3)->addSeconds(45),
        ]);

        // Sample import with failures
        ImportLog::create([
            'user_id' => 1,
            'filename' => 'orders_import.csv',
            'operation_type' => 'orders',
            'total_rows' => 100,
            'successful_rows' => 98,
            'failed_rows' => 2,
            'status' => 'completed',
            'file_size_mb' => 1.80,
            'error_details' => 'Row 45: Invalid order date format. Row 78: Missing user_id',
            'started_at' => now()->subDays(2),
            'completed_at' => now()->subDays(2)->addMinutes(1),
        ]);

        // Sample failed import
        ImportLog::create([
            'user_id' => 1,
            'filename' => 'users_bulk_import.csv',
            'operation_type' => 'users',
            'total_rows' => 50,
            'successful_rows' => 0,
            'failed_rows' => 50,
            'status' => 'failed',
            'file_size_mb' => 0.92,
            'error_details' => 'Database constraint violation: Duplicate email addresses detected in file',
            'started_at' => now()->subDay(),
            'completed_at' => now()->subDay()->addSeconds(5),
        ]);

        // Sample pending import
        ImportLog::create([
            'user_id' => 1,
            'filename' => 'reviews_queue.csv',
            'operation_type' => 'reviews',
            'total_rows' => 200,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'status' => 'processing',
            'file_size_mb' => 1.55,
            'started_at' => now()->subMinutes(5),
            'completed_at' => null,
        ]);
    }
}
