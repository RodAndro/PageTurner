<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MaintenanceLog;
use App\Models\Order;
use Illuminate\Support\Facades\File;

class GenerateDailyReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:generate-daily {--date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate daily sales report';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = now();
        $log = MaintenanceLog::create([
            'task_name' => 'report:generate-daily',
            'status' => 'running',
            'command' => 'report:generate-daily',
            'started_at' => $startTime,
        ]);

        try {
            $date = $this->option('date') ? new \DateTime($this->option('date')) : now();
            $dateStr = $date->format('Y-m-d');

            // Generate report
            $orders = Order::where('status', 'Delivered')
                ->whereDate('updated_at', $dateStr)
                ->get();

            $totalRevenue = $orders->sum('total_amount');
            $totalOrders = $orders->count();
            $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
            $subtotal = $totalRevenue / 1.10;
            $tax = $totalRevenue - $subtotal;

            $reportData = [
                'date' => $dateStr,
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
                'subtotal' => round($subtotal, 2),
                'tax' => round($tax, 2),
                'average_order_value' => $avgOrderValue,
                'generated_at' => now()->toDateTimeString(),
            ];

            // Save report
            $reportPath = storage_path("reports/daily-{$dateStr}.json");
            File::ensureDirectoryExists(dirname($reportPath));
            File::put($reportPath, json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $duration = now()->diffInSeconds($startTime);

            $log->update([
                'status' => 'completed',
                'output' => "Report generated: {$totalOrders} orders, \${$totalRevenue} revenue",
                'duration_seconds' => $duration,
                'completed_at' => now(),
            ]);

            $this->info("Daily report generated for {$dateStr}");
        } catch (\Exception $e) {
            $duration = now()->diffInSeconds($startTime);

            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'duration_seconds' => $duration,
                'completed_at' => now(),
            ]);

            $this->error("Report generation failed: " . $e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
