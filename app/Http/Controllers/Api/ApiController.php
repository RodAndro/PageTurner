<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiPaginationTrait;
use App\Models\Book;
use App\Models\Category;
use App\Models\Review;
use App\Services\BookRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    use ApiPaginationTrait;

    /**
     * GET /api/books
     * List books with cursor-based pagination
     *
     * Query parameters:
     * - cursor: pagination cursor
     * - per_page: items per page (default: 20)
     * - order_by: field to order by (default: id)
     * - direction: asc or desc (default: asc)
     * - fields: comma-separated fields to return (e.g., id,title,price)
     *
     * Response headers:
     * - X-RateLimit-Limit: maximum requests per minute
     * - X-RateLimit-Remaining: requests remaining in current window
     * - X-RateLimit-Reset: unix timestamp when limit resets
     * - ETag: resource fingerprint for caching
     */
    public function listBooks(Request $request)
    {
        $query = Book::query();

        // Filter by category if provided
        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Search if provided
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Price range filtering
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->input('min_price'));
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->input('max_price'));
        }

        $perPage = min($request->input('per_page', 20), 100); // Max 100 per page

        return $this->apiPaginatedResponse($query, $perPage);
    }

    /**
     * GET /api/books/{id}
     * Get a single book with full details
     */
    public function showBook(Book $book)
    {
        $data = [
            'id' => $book->id,
            'title' => $book->title,
            'description' => $book->description,
            'price' => $book->price,
            'isbn' => $book->isbn,
            'category_id' => $book->category_id,
            'category' => [
                'id' => $book->category?->id,
                'name' => $book->category?->name,
            ],
            'rating' => $book->reviews()->avg('rating'),
            'review_count' => $book->reviews()->count(),
            'in_stock' => $book->quantity > 0,
            'quantity' => $book->quantity,
            'created_at' => $book->created_at,
            'updated_at' => $book->updated_at,
        ];

        return $this->apiResponse($data);
    }

    /**
     * GET /api/books/{id}/reviews
     * Get reviews for a book with pagination
     */
    public function listBookReviews(Request $request, Book $book)
    {
        $query = $book->reviews()
            ->with('user')
            ->where('approved', true)
            ->orderBy('created_at', 'desc');

        $perPage = min($request->input('per_page', 20), 100);

        return $this->apiPaginatedResponse($query, $perPage);
    }

    /**
     * GET /api/categories
     * List all categories
     */
    public function listCategories(Request $request)
    {
        $query = Category::query()
            ->with(['books' => fn($q) => $q->count()]);

        $perPage = min($request->input('per_page', 50), 100);

        return $this->apiPaginatedResponse($query, $perPage);
    }

    /**
     * GET /api/categories/{id}/books
     * Get books in a category with pagination
     */
    public function listCategoryBooks(Request $request, Category $category)
    {
        $query = $category->books()->orderBy('title');
        $perPage = min($request->input('per_page', 20), 100);

        return $this->apiPaginatedResponse($query, $perPage);
    }

    /**
     * GET /api/rate-limit-status
     * Get current rate limit status for authenticated user
     */
    public function rateLimitStatus(Request $request)
    {
        $identifier = $request->user()?->id ?? $request->ip();
        $tier = $request->attributes->get('rate_limit_tier', 'public');

        $rateLimiter = app(\App\Services\RateLimiterService::class);
        $status = $rateLimiter->getStatus($identifier, $tier);

        return $this->apiResponse($status);
    }

    /**
     * GET /api/health
     * Health check endpoint (no rate limiting)
     */
    public function health()
    {
        return $this->apiResponse([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
            'database' => $this->checkDatabase() ? 'connected' : 'disconnected',
        ]);
    }

    /**
     * GET|POST /api/ai/recommendations
     * Get AI-powered book recommendations
     */
    public function getAiRecommendations(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:500',
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);

        $query = $request->input('query');
        $categoryId = $request->input('category_id');

        $recommendations = app(BookRecommendationService::class)->recommend(
            $query,
            $categoryId ? (int) $categoryId : null,
            $request->user()?->id
        );

        return response()->json($recommendations);
    }

    /**
     * Check database connection
     */
    protected function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
