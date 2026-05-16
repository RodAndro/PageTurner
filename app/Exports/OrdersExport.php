<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class OrdersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithChunkReading
{
    protected $filters = [];
    protected $selectedColumns = [];
    protected $isFinancialReport = false;

    public function __construct($filters = [], $selectedColumns = [], $isFinancialReport = false)
    {
        $this->filters = is_string($filters) ? json_decode($filters, true) : $filters;
        $this->selectedColumns = is_string($selectedColumns) ? json_decode($selectedColumns, true) : ($selectedColumns ?: $this->defaultColumns());
        $this->isFinancialReport = $isFinancialReport;
    }

    public function query()
    {
        $query = Order::with('user', 'orderItems.book');

        // Apply order status filter
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        // Apply date range filter
        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        // Apply customer filter
        if (!empty($this->filters['user_id'])) {
            $query->where('user_id', $this->filters['user_id']);
        }
        if (!empty($this->filters['customer_email'])) {
            $query->whereHas('user', function($q) {
                $q->where('email', 'like', '%' . $this->filters['customer_email'] . '%');
            });
        }

        return $query;
    }

    public function headings(): array
    {
        if ($this->isFinancialReport) {
            return ['Order ID', 'Order Date', 'Customer Name', 'Total Amount', 'Tax', 'Subtotal', 'Status'];
        }

        $headingMap = [
            'id' => 'Order ID',
            'order_number' => 'Order Number',
            'customer_name' => 'Customer Name',
            'customer_email' => 'Customer Email',
            'total_price' => 'Total Amount',
            'total_amount' => 'Total Amount',
            'status' => 'Status',
            'item_count' => 'Items Count',
            'created_at' => 'Order Date',
        ];

        return array_map(fn($col) => $headingMap[$col] ?? ucfirst($col), $this->selectedColumns);
    }

    public function map($order): array
    {
        if ($this->isFinancialReport) {
            $subtotal = $order->total_amount / 1.10; // Assuming 10% tax
            $tax = $order->total_amount - $subtotal;
            
            return [
                $order->id,
                $order->created_at->format('Y-m-d'),
                $this->customerName($order),
                number_format($order->total_amount, 2),
                number_format($tax, 2),
                number_format($subtotal, 2),
                $order->status,
            ];
        }

        $data = [];
        foreach ($this->selectedColumns as $column) {
            $data[] = match($column) {
                'id' => $order->id,
                'order_number' => $order->id, // Using ID as order number
                'customer_name' => $this->customerName($order),
                'customer_email' => $order->user?->email ?? 'N/A',
                'total_price', 'total_amount' => number_format($order->total_amount, 2),
                'status' => ucfirst($order->status),
                'item_count' => $order->orderItems->count(),
                'created_at' => $order->created_at->format('Y-m-d H:i'),
                default => '',
            };
        }
        return $data;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    private function defaultColumns(): array
    {
        return ['id', 'order_number', 'customer_name', 'customer_email', 'total_amount', 'status', 'created_at'];
    }

    private function customerName(Order $order): string
    {
        return $order->user?->name ?? 'N/A';
    }
}
