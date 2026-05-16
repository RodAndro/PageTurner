<?php

namespace Database\Seeders;

use App\Models\ExportLog;
use Illuminate\Database\Seeder;

class ExportLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates sample export logs for demonstration and testing.
     */
    public function run(): void
    {
        // Sample completed exports
        ExportLog::create([
            'user_id' => 1,
            'export_type' => 'orders',
            'format' => 'csv',
            'total_records' => 45,
            'status' => 'completed',
            'file_path' => '/exports/orders_2026-04-19.csv',
            'file_name' => 'orders_2026-04-19.csv',
            'file_size_mb' => 0.85,
            'filters' => json_encode(['status' => 'completed', 'date_range' => 'last_30_days']),
            'downloaded_at' => now()->subDay(),
            'started_at' => now()->subDays(2),
            'completed_at' => now()->subDays(2)->addSeconds(30),
        ]);

        ExportLog::create([
            'user_id' => 1,
            'export_type' => 'personal_data',
            'format' => 'json',
            'total_records' => 1,
            'status' => 'completed',
            'file_path' => '/exports/personal_data_user_1.json',
            'file_name' => 'personal_data_user_1.json',
            'file_size_mb' => 0.25,
            'filters' => json_encode(['gdpr_compliant' => true]),
            'downloaded_at' => now()->subHours(3),
            'started_at' => now()->subHours(4),
            'completed_at' => now()->subHours(4)->addSeconds(15),
        ]);

        // Sample PDF export
        ExportLog::create([
            'user_id' => 1,
            'export_type' => 'orders',
            'format' => 'pdf',
            'total_records' => 12,
            'status' => 'completed',
            'file_path' => '/exports/order_history_2026.pdf',
            'file_name' => 'order_history_2026.pdf',
            'file_size_mb' => 2.15,
            'filters' => json_encode(['year' => 2026]),
            'downloaded_at' => now()->subHours(1),
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHours(2)->addSeconds(45),
        ]);

        // Sample Excel export
        ExportLog::create([
            'user_id' => 1,
            'export_type' => 'reading_history',
            'format' => 'excel',
            'total_records' => 156,
            'status' => 'completed',
            'file_path' => '/exports/reading_history_2026.xlsx',
            'file_name' => 'reading_history_2026.xlsx',
            'file_size_mb' => 1.42,
            'filters' => json_encode(['include_reviews' => true, 'include_wishlist' => true]),
            'downloaded_at' => now()->subHours(2),
            'started_at' => now()->subHours(3),
            'completed_at' => now()->subHours(3)->addMinutes(1),
        ]);

        // Sample failed export
        ExportLog::create([
            'user_id' => 1,
            'export_type' => 'books',
            'format' => 'json',
            'total_records' => 0,
            'status' => 'failed',
            'file_path' => null,
            'file_name' => null,
            'file_size_mb' => null,
            'error_message' => 'Database connection timeout during export process',
            'started_at' => now()->subHours(5),
            'completed_at' => now()->subHours(5)->addSeconds(10),
        ]);

        // Sample pending export
        ExportLog::create([
            'user_id' => 1,
            'export_type' => 'personal_data',
            'format' => 'json',
            'total_records' => 0,
            'status' => 'processing',
            'file_path' => null,
            'file_name' => null,
            'filters' => json_encode(['gdpr_compliant' => true, 'anonymize_sensitive_data' => true]),
            'started_at' => now()->subMinutes(2),
            'completed_at' => null,
        ]);
    }
}
