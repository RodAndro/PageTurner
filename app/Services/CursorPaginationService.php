<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CursorPaginationService
{
    /**
     * Paginate query using cursor-based pagination
     *
     * Better than offset-based pagination for large datasets
     * Usage: GET /api/books?cursor=...&per_page=20
     */
    public function paginate(Builder $query, int $perPage = 20, ?string $cursor = null, string $orderBy = 'id', string $direction = 'asc'): array
    {
        $validated_direction = in_array($direction, ['asc', 'desc']) ? $direction : 'asc';

        // Clone query to avoid modifying original
        $countQuery = clone $query;

        // Get total count
        $total = $countQuery->count();

        // Apply cursor filtering if provided
        if ($cursor) {
            try {
                $cursor = $this->decodeCursor($cursor);
                $operator = $validated_direction === 'asc' ? '>' : '<';
                $query->where($orderBy, $operator, $cursor);
            } catch (\Exception $e) {
                // Invalid cursor, ignore
            }
        }

        // Order and fetch one extra to detect if there's next page
        $items = $query
            ->orderBy($orderBy, $validated_direction)
            ->limit($perPage + 1)
            ->get();

        $hasMore = $items->count() > $perPage;
        $items = $items->take($perPage);

        // Generate cursors
        $nextCursor = null;
        if ($hasMore && $items->isNotEmpty()) {
            $lastItem = $items->last();
            $nextCursor = $this->encodeCursor($lastItem->{$orderBy});
        }

        $prevCursor = null;
        if ($cursor && $items->isNotEmpty()) {
            $firstItem = $items->first();
            $prevCursor = $this->encodeCursor($firstItem->{$orderBy});
        }

        return [
            'data' => $items,
            'pagination' => [
                'per_page' => $perPage,
                'total' => $total,
                'has_more' => $hasMore,
                'cursor' => $cursor,
                'next_cursor' => $nextCursor,
                'prev_cursor' => $prevCursor,
            ],
        ];
    }

    /**
     * Encode cursor (typically the ID of last item)
     */
    public function encodeCursor(mixed $value): string
    {
        return base64_encode(json_encode(['value' => $value]));
    }

    /**
     * Decode cursor
     */
    public function decodeCursor(string $cursor): mixed
    {
        $decoded = base64_decode($cursor, true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid cursor format');
        }

        $data = json_decode($decoded, true);
        if (!is_array($data) || !isset($data['value'])) {
            throw new \InvalidArgumentException('Invalid cursor data');
        }

        return $data['value'];
    }
}
