<?php

namespace App\Imports;

use App\Models\Book;
use App\Models\Category;
use App\Models\ImportLog;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Illuminate\Support\Collection;

class BooksImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading, SkipsOnFailure
{
    protected $importLog;
    protected $successCount = 0;
    protected $failureDetails = [];
    protected $duplicateMode = 'skip'; // 'skip' or 'update'

    public function __construct(ImportLog $importLog, $duplicateMode = 'skip')
    {
        $this->importLog = $importLog;
        $this->duplicateMode = $duplicateMode;
    }

    /**
     * @param array $row
     * @return Book|null
     */
    public function model(array $row)
    {
        // Validate required fields
        if (
            empty($row['isbn'])
            || empty($row['title'])
            || !array_key_exists('price', $row)
            || $row['price'] === null
            || $row['price'] === ''
            || !array_key_exists('stock', $row)
            || $row['stock'] === null
            || $row['stock'] === ''
            || empty($row['category'])
            || empty($row['description'])
        ) {
            $this->failureDetails[] = [
                'row' => $this->importLog->successful_rows + $this->importLog->failed_rows + 1,
                'error' => 'Missing required fields (ISBN, Title, Price, Stock, Category, or Description)',
                'data' => $row,
            ];
            $this->importLog->increment('failed_rows');
            return null;
        }

        // Validate ISBN format (ISBN-10 or ISBN-13)
        if (!$this->isValidISBN($row['isbn'])) {
            $this->failureDetails[] = [
                'row' => $this->importLog->successful_rows + $this->importLog->failed_rows + 1,
                'error' => "Invalid ISBN format: {$row['isbn']}",
                'data' => $row,
            ];
            $this->importLog->increment('failed_rows');
            return null;
        }

        // Validate title length
        if (strlen($row['title']) > 255) {
            $this->failureDetails[] = [
                'row' => $this->importLog->successful_rows + $this->importLog->failed_rows + 1,
                'error' => "Title exceeds 255 characters",
                'data' => $row,
            ];
            $this->importLog->increment('failed_rows');
            return null;
        }

        // Validate price
        if (!is_numeric($row['price']) || $row['price'] < 0 || $row['price'] > 9999.99) {
            $this->failureDetails[] = [
                'row' => $this->importLog->successful_rows + $this->importLog->failed_rows + 1,
                'error' => "Invalid price: must be numeric, positive, and not exceed 9,999.99",
                'data' => $row,
            ];
            $this->importLog->increment('failed_rows');
            return null;
        }

        // Validate stock
        if (!is_numeric($row['stock']) || $row['stock'] < 0 || intval($row['stock']) != $row['stock']) {
            $this->failureDetails[] = [
                'row' => $this->importLog->successful_rows + $this->importLog->failed_rows + 1,
                'error' => "Invalid stock: must be a non-negative integer",
                'data' => $row,
            ];
            $this->importLog->increment('failed_rows');
            return null;
        }

        // Validate and get category
        $category = Category::where('name', $row['category'])->first();
        if (!$category) {
            $this->failureDetails[] = [
                'row' => $this->importLog->successful_rows + $this->importLog->failed_rows + 1,
                'error' => "Category '{$row['category']}' does not exist",
                'data' => $row,
            ];
            $this->importLog->increment('failed_rows');
            return null;
        }

        // Check for duplicates
        $existingBook = Book::where('isbn', $row['isbn'])->first();
        if ($existingBook) {
            if ($this->duplicateMode === 'skip') {
                $this->failureDetails[] = [
                    'row' => $this->importLog->successful_rows + $this->importLog->failed_rows + 1,
                    'error' => "Duplicate ISBN found: {$row['isbn']}. Skipping.",
                    'data' => $row,
                ];
                $this->importLog->increment('failed_rows');
                return null;
            } elseif ($this->duplicateMode === 'update') {
                // Update existing record
                $existingBook->update([
                    'category_id' => $category->id,
                    'title' => $row['title'],
                    'author' => $row['author'] ?? $existingBook->author,
                    'price' => $row['price'],
                    'stock_quantity' => $row['stock'],
                    'description' => $row['description'] ?? $existingBook->description,
                ]);
                $this->importLog->increment('successful_rows');
                return null; // Return null to prevent duplicate insertion
            }
        }

        $this->importLog->increment('successful_rows');

        return new Book([
            'category_id' => $category->id,
            'title' => $row['title'],
            'author' => $row['author'] ?? 'Unknown',
            'isbn' => $row['isbn'],
            'price' => $row['price'],
            'stock_quantity' => $row['stock'],
            'description' => $row['description'] ?? '',
        ]);
    }

    /**
     * Validate ISBN-10 or ISBN-13 format
     */
    private function isValidISBN($isbn)
    {
        $isbn = preg_replace('/[^0-9X]/', '', strtoupper($isbn));
        
        if (strlen($isbn) == 10) {
            return $this->isValidISBN10($isbn);
        } elseif (strlen($isbn) == 13) {
            return $this->isValidISBN13($isbn);
        }
        
        return false;
    }

    private function isValidISBN10($isbn)
    {
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int)$isbn[$i] * (10 - $i);
        }
        $checkDigit = (11 - ($sum % 11)) % 11;
        $checkChar = ($checkDigit == 10) ? 'X' : (string)$checkDigit;
        return $isbn[9] === $checkChar;
    }

    private function isValidISBN13($isbn)
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int)$isbn[$i] * ($i % 2 == 0 ? 1 : 3);
        }
        $checkDigit = (10 - ($sum % 10)) % 10;
        return (int)$isbn[12] === $checkDigit;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function onFailure(\Maatwebsite\Excel\Validators\Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->failureDetails[] = [
                'row' => $failure->row(),
                'error' => implode(', ', $failure->errors()),
                'attribute' => $failure->attribute(),
            ];
        }
    }

    public function getFailureDetails()
    {
        return $this->failureDetails;
    }
}
