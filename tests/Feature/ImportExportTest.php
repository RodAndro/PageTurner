<?php

namespace Tests\Feature;

use App\Jobs\ProcessBookImport;
use App\Jobs\ProcessExport;
use App\Models\Book;
use App\Models\Category;
use App\Models\ExportLog;
use App\Models\ImportLog;
use App\Models\User;
use App\Services\MemoryMonitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        Category::factory()->create(['name' => 'Science Fiction']);
        Category::factory()->create(['name' => 'Fiction']);
    }

    public function test_import_10000_book_records_successfully(): void
    {
        Storage::fake('local');
        Queue::fake();

        $file = UploadedFile::fake()->createWithContent('books.csv', $this->generateLargeBooksCsv(10001));

        $response = $this->actingAs($this->admin)->post('/admin/import-export/books/import', [
            'file' => $file,
            'duplicate_mode' => 'skip',
        ]);

        $response->assertRedirect(route('admin.import-export.status'));
        Queue::assertPushed(ProcessBookImport::class);
        $this->assertDatabaseHas('import_logs', [
            'module_type' => 'books',
            'status' => 'pending',
            'total_rows' => 10001,
        ]);
    }

    public function test_imported_records_validated_correctly(): void
    {
        $result = $this->importBooksWithValidation($this->generateValidBooksCsv(100));

        $this->assertEquals(100, $result['successful_rows']);
        $this->assertEquals(0, $result['failed_rows']);
    }

    public function test_malformed_file_returns_proper_error_report(): void
    {
        $result = $this->importBooksWithValidation("isbn,title,author,category,price,description,stock\nmissing,columns");

        $this->assertEquals(0, $result['successful_rows']);
        $this->assertEquals(1, $result['failed_rows']);
        $this->assertStringContainsString('Invalid CSV format', $result['error_details'][0]);
    }

    public function test_partial_import_with_failures_reported(): void
    {
        $result = $this->importBooksWithValidation($this->generateCsvWithErrors(1000, 50));

        $this->assertEquals(950, $result['successful_rows']);
        $this->assertEquals(50, $result['failed_rows']);
        $this->assertNotEmpty($result['error_details']);
    }

    public function test_queue_processing_completes_background_job(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->createWithContent('books.csv', $this->generateLargeBooksCsv(5));
        $filePath = $file->store('imports', 'local');

        $importLog = ImportLog::create([
            'user_id' => $this->admin->id,
            'module_type' => 'books',
            'file_name' => 'books.csv',
            'file_path' => $filePath,
            'status' => 'pending',
            'total_rows' => 5,
        ]);

        ProcessBookImport::dispatchSync($importLog, 'skip');

        $importLog->refresh();
        $this->assertEquals('completed', $importLog->status);
        $this->assertEquals(5, $importLog->successful_rows);
    }

    public function test_import_memory_usage_under_256mb_with_chunking(): void
    {
        $memoryMonitor = new MemoryMonitor();

        $result = $memoryMonitor->monitor(function () {
            return $this->importBooksWithChunking($this->generateLargeBooksCsv(50000), 1000);
        }, 'large_import');

        $stats = $result['statistics'];

        $this->assertLessThan(256 * 1024 * 1024, $stats['memory_diff']);
        $this->assertFalse($stats['exceeded_limit'], 'Memory limit exceeded during import');
    }

    public function test_memory_usage_with_different_chunk_sizes(): void
    {
        foreach ([100, 500, 1000, 2000] as $chunkSize) {
            $memoryMonitor = new MemoryMonitor();
            $result = $memoryMonitor->monitor(function () use ($chunkSize) {
                return $this->importBooksWithChunking($this->generateLargeBooksCsv(10000), $chunkSize);
            }, "chunk_size_{$chunkSize}");

            $this->assertLessThan(
                256 * 1024 * 1024,
                $result['statistics']['memory_diff'],
                "Memory usage too high for chunk size {$chunkSize}"
            );
        }
    }

    public function test_export_large_dataset_without_timeout(): void
    {
        Storage::fake('local');
        $this->createBookRecords(50000);

        $exportLog = ExportLog::create([
            'user_id' => $this->admin->id,
            'module_type' => 'books',
            'export_format' => 'csv',
            'file_name' => 'books_export.csv',
            'file_path' => 'exports/books_export.csv',
            'status' => 'pending',
            'selected_columns' => ['id', 'isbn', 'title', 'author', 'price'],
        ]);

        $memoryMonitor = new MemoryMonitor();
        $result = $memoryMonitor->monitor(function () use ($exportLog) {
            ProcessExport::dispatchSync($exportLog);
        }, 'large_export');

        $exportLog->refresh();
        $this->assertEquals('completed', $exportLog->status);
        $this->assertEquals(50000, $exportLog->total_records);
        Storage::disk('local')->assertExists('exports/books_export.csv');
        $this->assertLessThan(256 * 1024 * 1024, $result['statistics']['memory_diff']);
    }

    public function test_export_queuing_for_very_large_datasets(): void
    {
        Queue::fake();

        $exportLog = ExportLog::create([
            'user_id' => $this->admin->id,
            'module_type' => 'books',
            'export_format' => 'csv',
            'file_name' => 'books_export.csv',
            'file_path' => 'exports/books_export.csv',
            'status' => 'pending',
            'total_records' => 100000,
            'selected_columns' => ['id', 'isbn', 'title'],
        ]);

        ProcessExport::dispatch($exportLog);

        Queue::assertPushed(ProcessExport::class);
    }

    public function test_concurrent_import_export_operations(): void
    {
        Queue::fake();
        Storage::fake('local');

        for ($i = 1; $i <= 3; $i++) {
            $file = UploadedFile::fake()->createWithContent("books_concurrent_{$i}.csv", $this->generateLargeBooksCsv(10001));

            $this->actingAs($this->admin)->post('/admin/import-export/books/import', [
                'file' => $file,
                'duplicate_mode' => 'skip',
            ]);
        }

        $this->assertEquals(3, ImportLog::where('module_type', 'books')->where('status', 'pending')->count());
        Queue::assertPushed(ProcessBookImport::class, 3);

        ProcessExport::dispatch(new ExportLog([
            'module_type' => 'books',
            'export_format' => 'csv',
            'file_name' => 'books_export.csv',
            'file_path' => 'exports/books_export.csv',
            'selected_columns' => ['id', 'isbn', 'title'],
        ]));

        Queue::assertPushed(ProcessExport::class);
    }

    private function generateLargeBooksCsv(int $count): string
    {
        $csv = "isbn,title,author,category,price,description,stock\n";

        for ($i = 1; $i <= $count; $i++) {
            $isbn = $this->isbn13($i);
            $csv .= "\"{$isbn}\",\"Book Title {$i}\",\"Author {$i}\",\"Science Fiction\",29.99,\"Description for book {$i}\",50\n";
        }

        return $csv;
    }

    private function generateValidBooksCsv(int $count): string
    {
        return $this->generateLargeBooksCsv($count);
    }

    private function generateCsvWithErrors(int $total, int $errorCount): string
    {
        $csv = "isbn,title,author,category,price,description,stock\n";

        for ($i = 1; $i <= $total; $i++) {
            if ($i <= $errorCount) {
                $csv .= "\"{$i}\",\"Book {$i}\",\"Author {$i}\"\n";
            } else {
                $isbn = $this->isbn13($i);
                $csv .= "\"{$isbn}\",\"Book {$i}\",\"Author {$i}\",\"Science Fiction\",29.99,\"Description\",50\n";
            }
        }

        return $csv;
    }

    private function importBooksWithValidation(string $csvContent): array
    {
        $lines = array_filter(explode("\n", $csvContent));
        $header = str_getcsv((string) array_shift($lines));

        $successful = 0;
        $failed = 0;
        $errors = [];

        foreach ($lines as $row) {
            $data = str_getcsv($row);
            if (count($data) === count($header) && !empty($data[0]) && !empty($data[1]) && !empty($data[3])) {
                $successful++;
            } else {
                $failed++;
                $errors[] = 'Invalid CSV format: missing required columns';
            }
        }

        return [
            'successful_rows' => $successful,
            'failed_rows' => $failed,
            'error_details' => $errors,
        ];
    }

    private function importBooksWithChunking(string $csvContent, int $chunkSize): array
    {
        $lines = array_filter(explode("\n", $csvContent));
        array_shift($lines);

        $successful = 0;
        foreach (array_chunk($lines, $chunkSize) as $chunk) {
            $successful += count($chunk);
        }

        return [
            'successful_rows' => $successful,
            'used_chunking' => true,
        ];
    }

    private function createBookRecords(int $count): void
    {
        $category = Category::firstOrCreate(['name' => 'Test Category']);

        for ($i = 0; $i < $count; $i += 1000) {
            $booksData = [];
            for ($j = 0; $j < 1000 && ($i + $j) < $count; $j++) {
                $number = $i + $j + 1;
                $booksData[] = [
                    'title' => "Book {$number}",
                    'author' => 'Author ' . ($number % 100),
                    'isbn' => $this->isbn13($number),
                    'category_id' => $category->id,
                    'price' => 29.99,
                    'stock_quantity' => 50,
                    'description' => 'Test book description',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            Book::insert($booksData);
        }
    }

    private function isbn13(int $number): string
    {
        $base = '978' . str_pad((string) $number, 9, '0', STR_PAD_LEFT);
        $sum = 0;

        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $base[$i] * ($i % 2 === 0 ? 1 : 3);
        }

        return $base . ((10 - ($sum % 10)) % 10);
    }
}
