<?php

namespace App\Jobs;

use App\Imports\BooksImport;
use App\Models\ImportLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProcessBookImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $importLog;
    protected $duplicateMode;

    public function __construct(ImportLog $importLog, $duplicateMode = 'skip')
    {
        $this->importLog = $importLog;
        $this->duplicateMode = $duplicateMode;
    }

    public function handle(): void
    {
        try {
            $this->importLog->update(['status' => 'processing', 'started_at' => now()]);

            $import = new BooksImport($this->importLog, $this->duplicateMode);
            Excel::import($import, $this->importLog->file_path, 'local');

            $failureDetails = $import->getFailureDetails();
            
            $this->importLog->update([
                'status' => 'completed',
                'completed_at' => now(),
                'failure_report' => !empty($failureDetails) ? $failureDetails : null,
            ]);

        } catch (Throwable $e) {
            $this->importLog->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_details' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->importLog->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_details' => [
                'message' => $exception->getMessage(),
                'exception' => class_basename($exception),
            ],
        ]);
    }
}
