<?php

namespace App\Jobs;

use App\Exports\BooksExport;
use App\Exports\OrdersExport;
use App\Exports\UsersExport;
use App\Models\ExportLog;
use App\Services\SimplePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $exportLog;
    protected $exportClass;

    public function __construct(ExportLog $exportLog, $exportClass = null)
    {
        $this->exportLog = $exportLog;
        $this->exportClass = $exportClass;
    }

    public function handle(): void
    {
        try {
            $this->exportLog->update(['status' => 'processing', 'started_at' => now()]);

            $filters = $this->exportLog->filters ?? [];
            $selectedColumns = $this->exportLog->selected_columns ?? [];
            $format = $this->exportLog->export_format;

            // Determine export class based on module type
            $exportClass = $this->exportClass ?? $this->getExportClass(
                $this->exportLog->module_type,
                $filters,
                $selectedColumns
            );

            // Get the query to count records
            $recordCount = $exportClass->query()->count();
            $this->exportLog->update(['total_records' => $recordCount]);

            if ($format === 'pdf') {
                $this->storePdfExport($exportClass);
            } else {
                Excel::store(
                    $exportClass,
                    $this->exportLog->file_path,
                    'local',
                    $format === 'xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV
                );
            }

            $this->exportLog->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

        } catch (Throwable $e) {
            $this->exportLog->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function getExportClass($moduleType, $filters, $selectedColumns)
    {
        return match($moduleType) {
            'books' => new BooksExport($filters, $selectedColumns),
            'orders' => new OrdersExport($filters, $selectedColumns),
            'orders_financial' => new OrdersExport($filters, $selectedColumns, true),
            'users' => new UsersExport($filters, $selectedColumns, $filters['redact_pii'] ?? false),
            default => throw new \Exception("Unknown module type: {$moduleType}"),
        };
    }

    protected function storePdfExport(object $exportClass): void
    {
        $rows = [];
        $query = $exportClass->query();

        $query->chunk(1000, function ($records) use (&$rows, $exportClass) {
            foreach ($records as $record) {
                $rows[] = $exportClass->map($record);
            }
        });

        $title = ucfirst(str_replace('_', ' ', $this->exportLog->module_type)) . ' Export';
        Storage::disk('local')->put(
            $this->exportLog->file_path,
            SimplePdfService::table($title, $exportClass->headings(), $rows)
        );
    }

    public function failed(Throwable $exception): void
    {
        $this->exportLog->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $exception->getMessage(),
        ]);
    }
}
