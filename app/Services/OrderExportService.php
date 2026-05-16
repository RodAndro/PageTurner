<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Order Export Service
 * 
 * Handles order history export in multiple formats (CSV, Excel, PDF)
 * Supports date filtering and customizable fields
 * 
 * @package App\Services
 */
class OrderExportService
{
    protected User $user;
    protected Collection $orders;

    /**
     * Initialize with user and optional filters
     * 
     * @param User $user
     * @param array $filters ['start_date', 'end_date']
     */
    public function __construct(User $user, array $filters = [])
    {
        $this->user = $user;
        $this->orders = $this->getOrdersWithFilters($filters);
    }

    /**
     * Get orders with applied filters
     * 
     * @param array $filters
     * @return Collection
     */
    protected function getOrdersWithFilters(array $filters): Collection
    {
        $query = Order::where('user_id', $this->user->id)
            ->with(['orderItems.book', 'user']);

        if (isset($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        return $query->get();
    }

    /**
     * Generate CSV export
     * 
     * @return string CSV content
     */
    public function generateCsv(): string
    {
        $rows = [];
        $rows[] = ['Order ID', 'Order Date', 'Status', 'Total Amount', 'Item Count', 'Books', 'Delivery Address', 'Tracking Number'];

        foreach ($this->orders as $order) {
            $books = $order->orderItems->pluck('book.title')->join('; ');
            $rows[] = [
                $order->id,
                $order->created_at->format('Y-m-d H:i'),
                ucfirst($order->status),
                number_format($order->total_amount, 2),
                $order->orderItems->count(),
                $books,
                $this->formatAddress($order),
                $order->tracking_number ?? 'N/A',
            ];
        }

        return $this->arrayToCsv($rows);
    }

    /**
     * Generate JSON export
     * 
     * @return array
     */
    public function generateJson(): array
    {
        return [
            'export_date' => now()->toIso8601String(),
            'user_id' => $this->user->id,
            'total_orders' => $this->orders->count(),
            'total_value' => number_format($this->orders->sum('total_amount'), 2),
            'orders' => $this->orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'date' => $order->created_at->toIso8601String(),
                    'status' => $order->status,
                    'total_amount' => number_format($order->total_amount, 2),
                    'items' => $order->orderItems->map(function ($item) {
                        return [
                            'book_id' => $item->book->id,
                            'title' => $item->book->title,
                            'author' => $item->book->author,
                            'isbn' => $item->book->isbn,
                            'quantity' => $item->quantity,
                            'price' => number_format($item->price, 2),
                        ];
                    })->toArray(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Generate PDF export (placeholder)
     * 
     * @return string HTML content (would be converted to PDF)
     */
    public function generatePdf(): string
    {
        $html = '<html><head><meta charset="UTF-8"></head><body>';
        $html .= '<h1>Order History Export</h1>';
        $html .= '<p>User: ' . $this->user->email . '</p>';
        $html .= '<p>Export Date: ' . now()->format('Y-m-d H:i:s') . '</p>';
        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%">';
        $html .= '<tr><th>Order ID</th><th>Date</th><th>Status</th><th>Total</th><th>Items</th></tr>';

        foreach ($this->orders as $order) {
            $books = $order->orderItems->pluck('book.title')->join(', ');
            $html .= '<tr>';
            $html .= '<td>' . $order->id . '</td>';
            $html .= '<td>' . $order->created_at->format('Y-m-d') . '</td>';
            $html .= '<td>' . ucfirst($order->status) . '</td>';
            $html .= '<td>$' . number_format($order->total_amount, 2) . '</td>';
            $html .= '<td>' . $books . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Export to file
     * 
     * @param string $format csv|excel|pdf
     * @return string File path
     */
    public function exportToFile(string $format = 'csv'): string
    {
        $fileName = "order_export_{$this->user->id}_" . now()->format('Y-m-d-His') . ".{$format}";
        $filePath = "exports/" . $fileName;

        switch ($format) {
            case 'csv':
                $content = $this->generateCsv();
                Storage::disk('local')->put($filePath, $content);
                break;
            case 'json':
                $content = json_encode($this->generateJson(), JSON_PRETTY_PRINT);
                Storage::disk('local')->put($filePath, $content);
                break;
            case 'pdf':
                $content = $this->generatePdf();
                // Would use DomPDF or TCPDF here
                Storage::disk('local')->put($filePath, $content);
                break;
        }

        return Storage::path($filePath);
    }

    /**
     * Get order count
     * 
     * @return int
     */
    public function getOrderCount(): int
    {
        return $this->orders->count();
    }

    /**
     * Get total order value
     * 
     * @return float
     */
    public function getTotalValue(): float
    {
        return $this->orders->sum('total_amount');
    }

    // Helper methods

    private function formatAddress(Order $order): string
    {
        $parts = [];
        if ($order->delivery_address) {
            $parts[] = $order->delivery_address;
        }
        if ($order->delivery_city) {
            $parts[] = $order->delivery_city;
        }
        if ($order->delivery_postal_code) {
            $parts[] = $order->delivery_postal_code;
        }
        if ($order->delivery_country) {
            $parts[] = $order->delivery_country;
        }

        return implode(', ', $parts) ?: 'N/A';
    }

    protected function arrayToCsv(array $rows): string
    {
        $csv = '';
        foreach ($rows as $row) {
            $csv .= '"' . implode('","', array_map(function ($cell) {
                return str_replace('"', '""', $cell);
            }, $row)) . "\"\n";
        }
        return $csv;
    }
}
