<?php

namespace App\Exports;

use App\Models\Book;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class BooksExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithChunkReading
{
    protected $filters = [];
    protected $selectedColumns = [];

    public function __construct($filters = [], $selectedColumns = [])
    {
        $this->filters = is_string($filters) ? json_decode($filters, true) : $filters;
        $this->selectedColumns = is_string($selectedColumns) ? json_decode($selectedColumns, true) : ($selectedColumns ?: $this->defaultColumns());
    }

    public function query()
    {
        $query = Book::with('category');

        // Apply category filter
        if (!empty($this->filters['category'])) {
            $query->where('category_id', $this->filters['category']);
        }

        // Apply price range filter
        if (!empty($this->filters['price_min'])) {
            $query->where('price', '>=', $this->filters['price_min']);
        }
        if (!empty($this->filters['price_max'])) {
            $query->where('price', '<=', $this->filters['price_max']);
        }

        // Apply stock status filter
        if (!empty($this->filters['stock_status'])) {
            if ($this->filters['stock_status'] === 'in_stock') {
                $query->where('stock_quantity', '>', 0);
            } elseif ($this->filters['stock_status'] === 'out_of_stock') {
                $query->where('stock_quantity', 0);
            } elseif ($this->filters['stock_status'] === 'low_stock') {
                $query->where('stock_quantity', '>', 0)->where('stock_quantity', '<', 10);
            }
        }

        // Apply date range filter
        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        return $query;
    }

    public function headings(): array
    {
        $headingMap = [
            'id' => 'ID',
            'isbn' => 'ISBN',
            'title' => 'Title',
            'author' => 'Author',
            'category_name' => 'Category',
            'price' => 'Price',
            'stock_quantity' => 'Stock',
            'description' => 'Description',
            'created_at' => 'Created Date',
            'updated_at' => 'Updated Date',
        ];

        return array_map(fn($col) => $headingMap[$col] ?? ucfirst($col), $this->selectedColumns);
    }

    public function map($book): array
    {
        $data = [];
        foreach ($this->selectedColumns as $column) {
            $data[] = match($column) {
                'id' => $book->id,
                'isbn' => $book->isbn,
                'title' => $book->title,
                'author' => $book->author,
                'category_name' => $book->category->name ?? 'N/A',
                'price' => number_format($book->price, 2),
                'stock_quantity' => $book->stock_quantity,
                'description' => $book->description,
                'created_at' => $book->created_at->format('Y-m-d H:i'),
                'updated_at' => $book->updated_at->format('Y-m-d H:i'),
                default => '',
            };
        }
        return $data;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    private function defaultColumns(): array
    {
        return ['id', 'isbn', 'title', 'author', 'category_name', 'price', 'stock_quantity', 'description'];
    }
}
