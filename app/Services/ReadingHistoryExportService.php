<?php

namespace App\Services;

use App\Models\Review;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Reading History Export Service
 * 
 * Handles export of reading history including:
 * - Books purchased
 * - Books reviewed
 * - Reading preferences
 * - Purchase timeline
 * 
 * @package App\Services
 */
class ReadingHistoryExportService
{
    protected User $user;
    protected array $options;

    /**
     * Initialize service
     * 
     * @param User $user
     * @param array $options ['include_reviews', 'include_ratings', 'include_wishlist']
     */
    public function __construct(User $user, array $options = [])
    {
        $this->user = $user;
        $this->options = array_merge([
            'include_reviews' => true,
            'include_ratings' => true,
            'include_wishlist' => false,
        ], $options);
    }

    /**
     * Generate complete reading history
     * 
     * @return array
     */
    public function generate(): array
    {
        return [
            'export_date' => now()->toIso8601String(),
            'user_id' => $this->user->id,
            'reading_summary' => $this->getReadingSummary(),
            'books_purchased' => $this->getPurchasedBooks(),
            'reviews' => $this->options['include_reviews'] ? $this->getReviews() : [],
            'reading_preferences' => $this->getReadingPreferences(),
            'statistics' => $this->generateStatistics(),
        ];
    }

    /**
     * Get reading summary
     * 
     * @return array
     */
    protected function getReadingSummary(): array
    {
        $orders = Order::where('user_id', $this->user->id)->get();
        $books = [];
        
        foreach ($orders as $order) {
            foreach ($order->orderItems as $item) {
                $books[] = $item->book;
            }
        }

        $reviews = Review::where('user_id', $this->user->id)->get();
        
        return [
            'total_books_purchased' => count($books),
            'total_reviews_written' => $reviews->count(),
            'average_rating' => $reviews->count() > 0 ? round($reviews->avg('rating'), 2) : 0,
            'favorite_category' => $this->getFavoriteCategory($books),
            'most_reviewed_author' => $this->getMostReviewedAuthor($books),
        ];
    }

    /**
     * Get purchased books with metadata
     * 
     * @return array
     */
    protected function getPurchasedBooks(): array
    {
        $purchases = [];
        
        $orders = Order::where('user_id', $this->user->id)
            ->with('orderItems.book')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($orders as $order) {
            foreach ($order->orderItems as $item) {
                $book = $item->book;
                $review = Review::where('user_id', $this->user->id)
                    ->where('book_id', $book->id)
                    ->first();

                $purchases[] = [
                    'book_id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'isbn' => $book->isbn,
                    'category' => $book->category?->name,
                    'purchase_date' => $order->created_at->toIso8601String(),
                    'price_paid' => number_format($item->price, 2),
                    'rating' => $review?->rating ?? null,
                    'has_review' => $review !== null,
                ];
            }
        }

        return $purchases;
    }

    /**
     * Get user reviews
     * 
     * @return array
     */
    protected function getReviews(): array
    {
        return Review::where('user_id', $this->user->id)
            ->with('book')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($review) {
                return [
                    'book_id' => $review->book->id,
                    'book_title' => $review->book->title,
                    'book_author' => $review->book->author,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'review_date' => $review->created_at->toIso8601String(),
                    'helpful_count' => $review->helpful_count ?? 0,
                ];
            })
            ->toArray();
    }

    /**
     * Get reading preferences inferred from purchase history
     * 
     * @return array
     */
    protected function getReadingPreferences(): array
    {
        $orders = Order::where('user_id', $this->user->id)->get();
        $books = [];
        $categories = [];

        foreach ($orders as $order) {
            foreach ($order->orderItems as $item) {
                $books[] = $item->book;
                $categories[] = $item->book->category?->name;
            }
        }

        $categoryCount = array_count_values($categories);
        arsort($categoryCount);

        return [
            'top_categories' => array_slice($categoryCount, 0, 5),
            'favorite_authors' => $this->getTopAuthors($books),
            'reading_frequency' => $this->calculateReadingFrequency(),
            'estimated_reading_level' => $this->estimateReadingLevel($books),
        ];
    }

    /**
     * Generate reading statistics
     * 
     * @return array
     */
    protected function generateStatistics(): array
    {
        $orders = Order::where('user_id', $this->user->id)->get();
        $reviews = Review::where('user_id', $this->user->id)->get();
        
        $totalSpent = $orders->sum('total_amount');
        $bookCount = 0;
        foreach ($orders as $order) {
            $bookCount += $order->orderItems->sum('quantity');
        }

        return [
            'total_books_purchased' => $bookCount,
            'total_amount_spent' => number_format($totalSpent, 2),
            'average_price_per_book' => $bookCount > 0 ? number_format($totalSpent / $bookCount, 2) : 0,
            'reviews_written' => $reviews->count(),
            'average_rating_given' => $reviews->count() > 0 ? round($reviews->avg('rating'), 2) : 0,
            'most_active_month' => $this->getMostActiveMonth(),
        ];
    }

    /**
     * Export to file
     * 
     * @param string $format json|csv|excel
     * @return string File path
     */
    public function exportToFile(string $format = 'json'): string
    {
        $fileName = "reading_history_{$this->user->id}_" . now()->format('Y-m-d-His') . ".{$format}";
        $filePath = "exports/" . $fileName;

        switch ($format) {
            case 'json':
                $content = json_encode($this->generate(), JSON_PRETTY_PRINT);
                Storage::disk('local')->put($filePath, $content);
                break;
            case 'csv':
                $content = $this->generateCsv();
                Storage::disk('local')->put($filePath, $content);
                break;
            case 'excel':
                // Would use Laravel Excel here
                $content = $this->generateCsv();
                Storage::disk('local')->put($filePath, $content);
                break;
        }

        return Storage::path($filePath);
    }

    /**
     * Generate CSV format
     * 
     * @return string
     */
    protected function generateCsv(): string
    {
        $data = $this->generate();
        $rows = [];
        
        // Header
        $rows[] = ['Title', 'Author', 'ISBN', 'Category', 'Purchase Date', 'Price', 'Your Rating', 'Review'];

        // Books
        foreach ($data['books_purchased'] as $book) {
            $rows[] = [
                $book['title'],
                $book['author'],
                $book['isbn'],
                $book['category'] ?? '',
                $book['purchase_date'],
                $book['price_paid'],
                $book['rating'] ?? '',
                '',
            ];
        }

        return $this->arrayToCsv($rows);
    }

    // Helper methods

    private function getFavoriteCategory($books): string
    {
        $categories = collect($books)
            ->pluck('category')
            ->filter()
            ->groupBy('id')
            ->map->count()
            ->sort(SORT_NUMERIC)
            ->reverse()
            ->keys()
            ->first();

        return $categories ?? 'N/A';
    }

    private function getMostReviewedAuthor($books): string
    {
        $authors = collect($books)
            ->pluck('author')
            ->filter()
            ->countBy(fn($a) => $a)
            ->sort(SORT_NUMERIC)
            ->reverse()
            ->keys()
            ->first();

        return $authors ?? 'N/A';
    }

    private function getTopAuthors($books): array
    {
        return collect($books)
            ->pluck('author')
            ->filter()
            ->countBy(fn($a) => $a)
            ->sort(SORT_NUMERIC)
            ->reverse()
            ->slice(0, 5)
            ->toArray();
    }

    private function calculateReadingFrequency(): string
    {
        $orders = Order::where('user_id', $this->user->id)->count();
        
        if ($orders === 0) {
            return 'No purchases';
        } elseif ($orders < 5) {
            return 'Occasional';
        } elseif ($orders < 15) {
            return 'Regular';
        } else {
            return 'Avid reader';
        }
    }

    private function estimateReadingLevel($books): string
    {
        if (empty($books)) {
            return 'Unknown';
        }

        // Simple heuristic based on book types
        return 'General';
    }

    private function getMostActiveMonth(): string
    {
        $orders = Order::where('user_id', $this->user->id)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('count', 'desc')
            ->first();

        if (!$orders) {
            return 'N/A';
        }

        $months = ['', 'January', 'February', 'March', 'April', 'May', 'June', 
                   'July', 'August', 'September', 'October', 'November', 'December'];
        
        return $months[$orders->month] ?? 'N/A';
    }

    protected function arrayToCsv(array $rows): string
    {
        $csv = '';
        foreach ($rows as $row) {
            $csv .= '"' . implode('","', array_map(function ($cell) {
                return str_replace('"', '""', $cell ?? '');
            }, $row)) . "\"\n";
        }
        return $csv;
    }
}
