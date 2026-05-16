<?php

namespace App\Http\Traits;

use App\Services\CursorPaginationService;
use Illuminate\Database\Eloquent\Builder;

trait ApiPaginationTrait
{
    /**
     * Paginate a query using cursor-based pagination
     *
     * Usage in controller:
     * $books = $this->cursorPaginate(Book::query());
     */
    protected function cursorPaginate(Builder $query, int $perPage = 20): array
    {
        $service = app(CursorPaginationService::class);
        $cursor = request()->input('cursor');
        $orderBy = request()->input('order_by', 'id');
        $direction = request()->input('direction', 'asc');

        // Whitelist allowed fields for ordering
        $allowed_fields = ['id', 'created_at', 'updated_at', 'title', 'price', 'rating'];
        $orderBy = in_array($orderBy, $allowed_fields) ? $orderBy : 'id';

        return $service->paginate($query, $perPage, $cursor, $orderBy, $direction);
    }

    /**
     * Return paginated API response
     *
     * Automatically includes rate limit headers and proper formatting
     */
    protected function apiPaginatedResponse(Builder $query, int $perPage = 20, array $options = [])
    {
        $result = $this->cursorPaginate($query, $perPage);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'pagination' => $result['pagination'],
            'meta' => [
                'api_version' => '1.0',
                'timestamp' => now()->toIso8601String(),
                'rate_limit_tier' => request()->attributes->get('rate_limit_tier', 'public'),
            ],
        ]);
    }

    /**
     * Return single resource API response
     */
    protected function apiResponse($data, int $statusCode = 200, array $headers = [])
    {
        return response()->json([
            'success' => $statusCode >= 200 && $statusCode < 300,
            'data' => $data,
            'meta' => [
                'api_version' => '1.0',
                'timestamp' => now()->toIso8601String(),
                'rate_limit_tier' => request()->attributes->get('rate_limit_tier', 'public'),
            ],
        ], $statusCode, $headers);
    }

    /**
     * Return error API response
     */
    protected function apiErrorResponse(string $message, int $statusCode = 400, array $errors = [])
    {
        return response()->json([
            'success' => false,
            'error' => [
                'message' => $message,
                'errors' => $errors,
            ],
            'meta' => [
                'api_version' => '1.0',
                'timestamp' => now()->toIso8601String(),
            ],
        ], $statusCode);
    }
}
