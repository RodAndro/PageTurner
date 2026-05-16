<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ETagMiddleware
{
    /**
     * Add ETag support for conditional requests
     *
     * Allows clients to:
     * - Cache responses based on ETag
     * - Send If-None-Match header to check if resource changed
     * - Receive 304 Not Modified if unchanged
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only process successful GET/HEAD requests
        if (!in_array($request->method(), ['GET', 'HEAD']) || !$response->isSuccessful()) {
            return $response;
        }

        // Only process JSON responses
        if (!$response->headers->has('Content-Type') || 
            !str_contains($response->headers->get('Content-Type'), 'application/json')) {
            return $response;
        }

        // Generate ETag from response content
        $content = $response->getContent();
        $etag = '"' . hash('sha256', $content) . '"';

        // Check If-None-Match header
        if ($request->header('If-None-Match') === $etag) {
            // Resource hasn't changed, return 304
            return response('', Response::HTTP_NOT_MODIFIED, [
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=3600',
            ]);
        }

        // Add ETag and cache headers to response
        $response->header('ETag', $etag);
        $response->header('Cache-Control', 'public, max-age=3600');

        return $response;
    }
}
