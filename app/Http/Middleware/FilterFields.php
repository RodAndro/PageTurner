<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FilterFields
{
    /**
     * Filter response fields based on ?fields query parameter
     *
     * Usage: GET /api/books?fields=id,title,price
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only process JSON responses
        if (!$response->headers->has('Content-Type') || 
            !str_contains($response->headers->get('Content-Type'), 'application/json')) {
            return $response;
        }

        // Check if fields parameter is provided
        if (!$request->has('fields')) {
            return $response;
        }

        $fields = array_map('trim', explode(',', $request->input('fields')));
        $data = json_decode($response->getContent(), true);

        if (is_array($data)) {
            // Handle paginated responses
            if (isset($data['data']) && is_array($data['data'])) {
                $data['data'] = array_map(
                    fn($item) => $this->filterFields($item, $fields),
                    $data['data']
                );
            } else {
                // Handle single resource or list responses
                $data = $this->filterFields($data, $fields);
            }

            $response->setContent(json_encode($data));
        }

        return $response;
    }

    /**
     * Filter an item to only include specified fields
     */
    protected function filterFields(array|object $item, array $fields): array
    {
        if (is_object($item)) {
            $item = (array) $item;
        }

        $filtered = [];

        foreach ($fields as $field) {
            // Support nested fields: user.email
            if (str_contains($field, '.')) {
                $value = $this->getNestedValue($item, explode('.', $field));
                if ($value !== null) {
                    $filtered[$field] = $value;
                }
            } else {
                if (array_key_exists($field, $item)) {
                    $filtered[$field] = $item[$field];
                }
            }
        }

        return $filtered;
    }

    /**
     * Get nested array value
     */
    protected function getNestedValue(array $data, array $keys): mixed
    {
        $value = $data;

        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }
}
