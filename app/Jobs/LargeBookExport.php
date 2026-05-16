<?php

namespace App\Jobs;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class LargeBookExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $options = []
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $limit = $this->options['limit'] ?? 10000;
        if ($limit < 1) {
            throw new InvalidArgumentException('Export limit must be greater than zero.');
        }

        $this->validateFormat();
        $this->validateFilters();

        $format = $this->options['format'] ?? 'csv';
        $path = 'exports/books_export_' . now()->format('Ymd_His_u') . ".{$format}";
        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['id', 'isbn', 'title', 'author', 'price']);
        
        Book::query()
            ->select(['id', 'isbn', 'title', 'author', 'price'])
            ->limit($limit)
            ->chunkById(1000, function ($books) use ($handle) {
                foreach ($books as $book) {
                    fputcsv($handle, [
                        $book->id,
                        $book->isbn,
                        $book->title,
                        $book->author,
                        $book->price,
                    ]);
                }
            });

        rewind($handle);
        Storage::disk('local')->put($path, stream_get_contents($handle));
        fclose($handle);
    }

    public function validateFormat(): bool
    {
        $format = $this->options['format'] ?? 'csv';
        if (!in_array($format, ['csv', 'xlsx', 'json'], true)) {
            throw new InvalidArgumentException("Unsupported export format: {$format}");
        }

        return true;
    }

    public function validateFilters(): bool
    {
        foreach (['category_id', 'min_price', 'max_price'] as $numericFilter) {
            if (isset($this->options[$numericFilter]) && $this->options[$numericFilter] < 0) {
                throw new InvalidArgumentException("Invalid {$numericFilter} filter.");
            }
        }

        foreach (['date_from', 'date_to'] as $dateFilter) {
            if (isset($this->options[$dateFilter]) && strtotime((string) $this->options[$dateFilter]) === false) {
                throw new InvalidArgumentException("Invalid {$dateFilter} filter.");
            }
        }

        return true;
    }
}
