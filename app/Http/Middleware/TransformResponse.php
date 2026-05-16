<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TransformResponse
{
    /**
     * Transform response data from snake_case to camelCase
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only transform JSON responses
        if (!$response->headers->has('Content-Type') || 
            !str_contains($response->headers->get('Content-Type'), 'application/json')) {
            return $response;
        }

        // Don't transform if transformation is disabled
        if ($request->input('_no_transform') === 'true') {
            return $response;
        }

        $data = json_decode($response->getContent(), true);

        if (is_array($data)) {
            $data = $this->transformToCamelCase($data);
            $response->setContent(json_encode($data));
        }

        return $response;
    }

    /**
     * Recursively transform array keys from snake_case to camelCase
     */
    protected function transformToCamelCase(array $data): array
    {
        $transformed = [];

        foreach ($data as $key => $value) {
            $newKey = $this->snakeToCamel($key);

            if (is_array($value) && !$this->isIndexedArray($value)) {
                $value = $this->transformToCamelCase($value);
            } elseif (is_array($value)) {
                $value = array_map(function ($item) {
                    if (is_array($item) && !$this->isIndexedArray($item)) {
                        return $this->transformToCamelCase($item);
                    }
                    return $item;
                }, $value);
            }

            $transformed[$newKey] = $value;
        }

        return $transformed;
    }

    /**
     * Convert snake_case to camelCase
     */
    protected function snakeToCamel(string $string): string
    {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }

    /**
     * Check if array is indexed (list) rather than associative
     */
    protected function isIndexedArray(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }
}
