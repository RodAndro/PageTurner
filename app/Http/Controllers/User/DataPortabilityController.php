<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use App\Models\Order;
use App\Models\Review;
use App\Models\ExportLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * User Data Portability Controller
 * 
 * Handles GDPR-compliant data export and portability features including:
 * - Personal data export (JSON)
 * - Order history export (PDF/Excel/CSV)
 * - Reading history export
 * - Export logs and status tracking
 * 
 * @package App\Http\Controllers\User
 */
class DataPortabilityController extends Controller
{
    /**
     * Show data portability dashboard
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        
        $exportHistory = ExportLog::where('user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        $dataSize = [
            'personal_data_kb' => $this->estimatePersonalDataSize($user),
            'order_history_kb' => $this->estimateOrderDataSize($user),
            'reading_history_kb' => $this->estimateReadingDataSize($user),
        ];

        return view('user.data-portability.index', [
            'exportHistory' => $exportHistory,
            'dataSize' => $dataSize,
        ]);
    }

    /**
     * Export personal data (GDPR compliance)
     * 
     * Returns all personal data in JSON format
     * 
     * @return \Illuminate\Http\Response
     */
    public function exportPersonalData()
    {
        $user = Auth::user();
        
        // Create export log entry
        $exportLog = ExportLog::create([
            'user_id' => $user->id,
            'export_type' => 'personal_data',
            'format' => 'json',
            'status' => 'processing',
            'filters' => json_encode(['gdpr_compliant' => true]),
            'started_at' => now(),
        ]);

        try {
            // Gather all personal data
            $personalData = [
                'export_date' => now()->toIso8601String(),
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'middle_name' => $user->middle_name,
                    'suffix' => $user->suffix,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'profile' => [
                    'date_of_birth' => $user->date_of_birth ?? null,
                    'gender' => $user->gender ?? null,
                    'country' => $user->country ?? null,
                    'state' => $user->state ?? null,
                    'city' => $user->city ?? null,
                    'postal_code' => $user->postal_code ?? null,
                    'address' => $user->address ?? null,
                ],
            ];

            // Total records
            $personalData['total_records'] = 1;
            
            $fileName = "personal_data_" . $user->id . "_" . now()->format('Y-m-d') . ".json";
            $filePath = "exports/" . $fileName;

            // Store file
            Storage::disk('local')->put($filePath, json_encode($personalData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // Update export log
            $exportLog->update([
                'status' => 'completed',
                'file_path' => Storage::path($filePath),
                'file_name' => $fileName,
                'file_size_mb' => Storage::size($filePath) / 1024 / 1024,
                'completed_at' => now(),
                'total_records' => 1,
            ]);

            // Return download
            return Storage::download($filePath, $fileName);

        } catch (\Exception $e) {
            $exportLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return redirect()->back()
                ->withErrors('Failed to export personal data: ' . $e->getMessage());
        }
    }

    /**
     * Export order history
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportOrderHistory(Request $request)
    {
        $request->validate([
            'format' => 'required|in:csv,excel,pdf',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $user = Auth::user();
        $format = $request->input('format');

        // Create export log
        $exportLog = ExportLog::create([
            'user_id' => $user->id,
            'export_type' => 'orders',
            'format' => $format,
            'status' => 'processing',
            'filters' => json_encode($request->only(['start_date', 'end_date'])),
            'started_at' => now(),
        ]);

        try {
            // Get orders with filters
            $query = Order::where('user_id', $user->id)
                ->with(['orderItems.book']);

            if ($request->has('start_date')) {
                $query->whereDate('created_at', '>=', $request->input('start_date'));
            }
            if ($request->has('end_date')) {
                $query->whereDate('created_at', '<=', $request->input('end_date'));
            }

            $orders = $query->get();

            $totalRecords = $orders->count();
            $fileName = "order_history_" . $user->id . "_" . now()->format('Y-m-d') . "." . $format;
            $filePath = "exports/" . $fileName;

            // Format data based on requested format
            switch ($format) {
                case 'csv':
                    $csvContent = $this->generateOrderCsv($orders);
                    Storage::disk('local')->put($filePath, $csvContent);
                    break;
                case 'excel':
                    // Would use Laravel Excel or similar
                    $this->generateOrderExcel($orders, $filePath);
                    break;
                case 'pdf':
                    // Would use Laravel PDF or similar
                    $this->generateOrderPdf($orders, $filePath);
                    break;
            }

            // Update export log
            $exportLog->update([
                'status' => 'completed',
                'file_path' => Storage::path($filePath),
                'file_name' => $fileName,
                'file_size_mb' => Storage::size($filePath) / 1024 / 1024,
                'completed_at' => now(),
                'total_records' => $totalRecords,
            ]);

            return Storage::download($filePath, $fileName);

        } catch (\Exception $e) {
            $exportLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return redirect()->back()
                ->withErrors('Failed to export order history: ' . $e->getMessage());
        }
    }

    /**
     * Export reading history
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportReadingHistory(Request $request)
    {
        $request->validate([
            'format' => 'required|in:csv,excel,json',
            'include_reviews' => 'boolean',
            'include_wishlist' => 'boolean',
        ]);

        $user = Auth::user();
        $format = $request->input('format');

        // Create export log
        $exportLog = ExportLog::create([
            'user_id' => $user->id,
            'export_type' => 'reading_history',
            'format' => $format,
            'status' => 'processing',
            'filters' => json_encode($request->only(['include_reviews', 'include_wishlist'])),
            'started_at' => now(),
        ]);

        try {
            // Get reading history from orders
            $orders = Order::where('user_id', $user->id)
                ->with(['orderItems.book'])
                ->get();

            $readingHistory = [];
            foreach ($orders as $order) {
                foreach ($order->orderItems as $item) {
                    $readingHistory[] = [
                        'book_id' => $item->book->id,
                        'title' => $item->book->title,
                        'author' => $item->book->author,
                        'isbn' => $item->book->isbn,
                        'purchase_date' => $order->created_at->toDateString(),
                        'price_paid' => $item->price,
                    ];
                }
            }

            // Include reviews if requested
            if ($request->boolean('include_reviews')) {
                $reviews = Review::where('user_id', $user->id)
                    ->with('book')
                    ->get()
                    ->map(function ($review) {
                        return [
                            'book_id' => $review->book->id,
                            'title' => $review->book->title,
                            'rating' => $review->rating,
                            'comment' => $review->comment,
                            'review_date' => $review->created_at->toDateString(),
                        ];
                    });

                $readingHistory = array_merge($readingHistory, $reviews->toArray());
            }

            $totalRecords = count($readingHistory);
            $fileName = "reading_history_" . $user->id . "_" . now()->format('Y-m-d') . "." . $format;
            $filePath = "exports/" . $fileName;

            // Generate file based on format
            switch ($format) {
                case 'json':
                    Storage::disk('local')->put($filePath, json_encode($readingHistory, JSON_PRETTY_PRINT));
                    break;
                case 'csv':
                    $csvContent = $this->generateReadingHistoryCsv($readingHistory);
                    Storage::disk('local')->put($filePath, $csvContent);
                    break;
                case 'excel':
                    $this->generateReadingHistoryExcel($readingHistory, $filePath);
                    break;
            }

            // Update export log
            $exportLog->update([
                'status' => 'completed',
                'file_path' => Storage::path($filePath),
                'file_name' => $fileName,
                'file_size_mb' => Storage::size($filePath) / 1024 / 1024,
                'completed_at' => now(),
                'total_records' => $totalRecords,
            ]);

            return Storage::download($filePath, $fileName);

        } catch (\Exception $e) {
            $exportLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return redirect()->back()
                ->withErrors('Failed to export reading history: ' . $e->getMessage());
        }
    }

    /**
     * Download previous export
     * 
     * @param ExportLog $exportLog
     * @return \Illuminate\Http\Response
     */
    public function downloadExport(ExportLog $exportLog)
    {
        // Verify ownership
        if ($exportLog->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        if ($exportLog->status !== 'completed' || !$exportLog->file_path) {
            abort(404, 'File not found or export incomplete');
        }

        // Update download timestamp
        $exportLog->update(['downloaded_at' => now()]);

        return Storage::download($exportLog->file_path, $exportLog->file_name);
    }

    /**
     * Delete old exports (cleanup)
     * 
     * @param ExportLog $exportLog
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteExport(ExportLog $exportLog)
    {
        // Verify ownership
        if ($exportLog->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        if ($exportLog->file_path && Storage::exists($exportLog->file_path)) {
            Storage::delete($exportLog->file_path);
        }

        $exportLog->delete();

        return redirect()->back()->with('success', 'Export deleted successfully');
    }

    // Helper methods

    private function generateOrderCsv($orders)
    {
        $csvData = [];
        $csvData[] = ['Order ID', 'Order Date', 'Status', 'Total Amount', 'Item Count', 'Books'];

        foreach ($orders as $order) {
            $books = $order->orderItems->pluck('book.title')->join(', ');
            $csvData[] = [
                $order->id,
                $order->created_at->toDateString(),
                $order->status,
                $order->total_amount,
                $order->orderItems->count(),
                $books,
            ];
        }

        return $this->arrayToCsv($csvData);
    }

    private function generateOrderExcel($orders, $filePath)
    {
        // Placeholder - would use Laravel Excel
        // Implementation would use Maatwebsite/Laravel-Excel or similar
    }

    private function generateOrderPdf($orders, $filePath)
    {
        // Placeholder - would use Laravel PDF
        // Implementation would use Barryvdh/Laravel-DomPDF or similar
    }

    private function generateReadingHistoryCsv($readingHistory)
    {
        $csvData = [];
        $csvData[] = ['Book Title', 'Author', 'ISBN', 'Date', 'Price/Rating'];

        foreach ($readingHistory as $item) {
            $csvData[] = [
                $item['title'],
                $item['author'] ?? $item['rating'] ?? '',
                $item['isbn'] ?? '',
                $item['purchase_date'] ?? $item['review_date'] ?? '',
                $item['price_paid'] ?? $item['rating'] ?? '',
            ];
        }

        return $this->arrayToCsv($csvData);
    }

    private function generateReadingHistoryExcel($readingHistory, $filePath)
    {
        // Placeholder - would use Laravel Excel
    }

    private function arrayToCsv(array $data)
    {
        $csv = '';
        foreach ($data as $row) {
            $csv .= '"' . implode('","', array_map(function ($cell) {
                return str_replace('"', '""', $cell);
            }, $row)) . "\"\n";
        }
        return $csv;
    }

    private function estimatePersonalDataSize($user)
    {
        // Estimate in KB
        return 10;
    }

    private function estimateOrderDataSize($user)
    {
        $orderCount = $user->orders()->count();
        return max(5, $orderCount * 2);
    }

    private function estimateReadingDataSize($user)
    {
        $reviewCount = Review::where('user_id', $user->id)->count();
        return max(5, $reviewCount * 1);
    }
}
