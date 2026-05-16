<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Order summary
        $totalOrders = $user->orders()->where('status', '!=', 'Cart')->count();
        $recentOrders = $user->orders()
            ->where('status', '!=', 'Cart')
            ->with('orderItems.book')
            ->latest()
            ->take(5)
            ->get();
        
        // Order status counts
        $orderStatusCounts = $user->orders()
            ->where('status', '!=', 'Cart')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');
        
        // Recently purchased books (from delivered orders)
        $purchasedBooks = $user->orders()
            ->whereIn('status', ['Delivered', 'Processing', 'Shipped'])
            ->with('orderItems.book')
            ->latest()
            ->take(3)
            ->get()
            ->pluck('orderItems')
            ->flatten()
            ->pluck('book')
            ->unique('id')
            ->take(6);
        
        // Review activity
        $reviewCount = Review::where('user_id', $user->id)->count();
        $recentReviews = Review::where('user_id', $user->id)
            ->with('book')
            ->latest()
            ->take(5)
            ->get();
        
        // Account status indicators
        $emailVerified = $user->hasVerifiedEmail();
        $twoFactorEnabled = !is_null($user->two_factor_secret);
        
        return view('customer.dashboard', compact(
            'user',
            'totalOrders',
            'recentOrders',
            'orderStatusCounts',
            'purchasedBooks',
            'reviewCount',
            'recentReviews',
            'emailVerified',
            'twoFactorEnabled'
        ));
    }

    /**
     * Export user's personal data (GDPR compliant)
     */
    public function exportPersonalData(): StreamedResponse
    {
        $user = Auth::user();
        
        $personalData = [
            'user_information' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'middle_name' => $user->middle_name,
                'last_name' => $user->last_name,
                'suffix' => $user->suffix,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
            'orders' => $user->orders()->with('orderItems.book')->get()->map(function ($order) {
                return [
                    'id' => $order->id,
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                    'items' => $order->orderItems->map(function ($item) {
                        return [
                            'book_title' => $item->book->title,
                            'book_isbn' => $item->book->isbn,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                        ];
                    }),
                ];
            }),
            'reviews' => $user->reviews()->with('book')->get()->map(function ($review) {
                return [
                    'book_title' => $review->book->title,
                    'book_isbn' => $review->book->isbn,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                    'updated_at' => $review->updated_at,
                ];
            }),
            'export_metadata' => [
                'export_date' => now()->toISOString(),
                'export_type' => 'personal_data',
                'format' => 'json',
                'compliance' => 'GDPR',
            ],
        ];

        $filename = 'personal_data_' . $user->id . '_' . now()->format('Y-m-d_H-i-s') . '.json';
        
        return response()->streamDownload(function () use ($personalData) {
            echo json_encode($personalData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    /**
     * Export order history as PDF/Excel
     */
    public function exportOrderHistory(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $format = $request->get('format', 'excel'); // 'excel' or 'pdf'
        
        $orders = $user->orders()
            ->with('orderItems.book')
            ->where('status', '!=', 'Cart')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($format === 'excel') {
            return $this->exportOrderHistoryExcel($orders, $user);
        } else {
            return $this->exportOrderHistoryPDF($orders, $user);
        }
    }

    /**
     * Export reading history (book browsing/purchase history)
     */
    public function exportReadingHistory(): StreamedResponse
    {
        $user = Auth::user();
        
        $readingHistory = [
            'user_id' => $user->id,
            'user_name' => $user->first_name . ' ' . $user->last_name,
            'export_date' => now()->toISOString(),
            'purchased_books' => $user->orders()
                ->whereIn('status', ['Delivered', 'Processing', 'Shipped'])
                ->with('orderItems.book.category')
                ->get()
                ->map(function ($order) {
                    return $order->orderItems->map(function ($item) use ($order) {
                        return [
                            'book_title' => $item->book->title,
                            'book_author' => $item->book->author,
                            'book_isbn' => $item->book->isbn,
                            'book_category' => $item->book->category->name,
                            'purchase_date' => $order->created_at->toISOString(),
                            'order_id' => $order->id,
                            'price' => $item->price,
                            'quantity' => $item->quantity,
                        ];
                    });
                })
                ->flatten()
                ->values(),
            'reviewed_books' => $user->reviews()
                ->with('book.category')
                ->get()
                ->map(function ($review) {
                    return [
                        'book_title' => $review->book->title,
                        'book_author' => $review->book->author,
                        'book_isbn' => $review->book->isbn,
                        'book_category' => $review->book->category->name,
                        'review_date' => $review->created_at->toISOString(),
                        'rating' => $review->rating,
                        'comment' => $review->comment,
                    ];
                }),
            'reading_statistics' => [
                'total_books_purchased' => $user->orders()
                    ->whereIn('status', ['Delivered', 'Processing', 'Shipped'])
                    ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                    ->sum('order_items.quantity'),
                'total_unique_books' => $user->orders()
                    ->whereIn('status', ['Delivered', 'Processing', 'Shipped'])
                    ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                    ->distinct('book_id')
                    ->count('book_id'),
                'total_reviews_written' => $user->reviews()->count(),
                'average_rating_given' => $user->reviews()->avg('rating'),
                'total_spent' => $user->orders()
                    ->whereIn('status', ['Delivered', 'Processing', 'Shipped'])
                    ->sum('total_amount'),
            ],
        ];

        $filename = 'reading_history_' . $user->id . '_' . now()->format('Y-m-d_H-i-s') . '.json';
        
        return response()->streamDownload(function () use ($readingHistory) {
            echo json_encode($readingHistory, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    /**
     * Helper method to export order history as Excel
     */
    private function exportOrderHistoryExcel($orders, $user): StreamedResponse
    {
        $filename = 'order_history_' . $user->id . '_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');
            
            // CSV Header
            fputcsv($file, [
                'Order ID',
                'Order Date',
                'Status',
                'Total Amount',
                'Book Title',
                'Book Author',
                'ISBN',
                'Quantity',
                'Unit Price',
                'Item Total'
            ]);
            
            // CSV Data
            foreach ($orders as $order) {
                foreach ($order->orderItems as $item) {
                    fputcsv($file, [
                        $order->id,
                        $order->created_at->format('Y-m-d H:i:s'),
                        $order->status,
                        $order->total_amount,
                        $item->book->title,
                        $item->book->author,
                        $item->book->isbn,
                        $item->quantity,
                        $item->price,
                        $item->quantity * $item->price
                    ]);
                }
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Helper method to export order history as PDF (simplified version)
     */
    private function exportOrderHistoryPDF($orders, $user): StreamedResponse
    {
        // For PDF export, we'll create a simple text-based format
        // In a real implementation, you would use a PDF library like DomPDF or TCPDF
        $filename = 'order_history_' . $user->id . '_' . now()->format('Y-m-d_H-i-s') . '.txt';
        
        $content = "Order History for {$user->first_name} {$user->last_name}\n";
        $content .= "Generated on: " . now()->format('Y-m-d H:i:s') . "\n";
        $content .= str_repeat("=", 80) . "\n\n";
        
        foreach ($orders as $order) {
            $content .= "Order #{$order->id}\n";
            $content .= "Date: {$order->created_at->format('Y-m-d H:i:s')}\n";
            $content .= "Status: {$order->status}\n";
            $content .= "Total: ₱" . number_format($order->total_amount, 2) . "\n";
            $content .= str_repeat("-", 40) . "\n";
            
            foreach ($order->orderItems as $item) {
                $content .= "  {$item->book->title}\n";
                $content .= "  Author: {$item->book->author}\n";
                $content .= "  ISBN: {$item->book->isbn}\n";
                $content .= "  Quantity: {$item->quantity} × ₱" . number_format($item->price, 2) . "\n";
                $content .= "  Item Total: ₱" . number_format($item->quantity * $item->price, 2) . "\n\n";
            }
            
            $content .= str_repeat("=", 80) . "\n\n";
        }

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => 'text/plain',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}
