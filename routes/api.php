<?php

use App\Http\Controllers\Api\ApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware([
    'api',
])->group(function () {
    // Public endpoints - apply rate limiting
    Route::middleware(['rate.limit', 'etag', 'transform.response', 'filter.fields'])
        ->prefix('v1')
        ->group(function () {
            // Books
            Route::get('/books', [ApiController::class, 'listBooks'])->name('api.books.list');
            Route::get('/books/{book}', [ApiController::class, 'showBook'])->name('api.books.show');
            Route::get('/books/{book}/reviews', [ApiController::class, 'listBookReviews'])->name('api.books.reviews');

            // Categories
            Route::get('/categories', [ApiController::class, 'listCategories'])->name('api.categories.list');
            Route::get('/categories/{category}/books', [ApiController::class, 'listCategoryBooks'])->name('api.categories.books');

            // Health check
            Route::get('/health', [ApiController::class, 'health'])->withoutMiddleware(['rate.limit']);

            // AI Recommendations
            Route::match(['get', 'post'], '/ai/recommendations', [ApiController::class, 'getAiRecommendations'])->name('api.ai.recommendations');
        });

    // Authenticated endpoints
    Route::middleware(['auth:sanctum', 'rate.limit', 'etag', 'transform.response', 'filter.fields'])
        ->prefix('v1')
        ->group(function () {
            // Rate limit status
            Route::get('/rate-limit-status', [ApiController::class, 'rateLimitStatus'])->name('api.rate-limit.status');

            // Future authenticated endpoints here
            // Route::post('/orders', [OrderApiController::class, 'store']);
            // Route::get('/orders', [OrderApiController::class, 'index']);
            // Route::get('/orders/{order}', [OrderApiController::class, 'show']);
            // Route::post('/reviews', [ReviewApiController::class, 'store']);
        });
});

// Fallback for unversioned endpoints
Route::middleware(['rate.limit', 'etag', 'transform.response', 'filter.fields'])
    ->group(function () {
        // Legacy support without versioning
        Route::get('/books', [ApiController::class, 'listBooks']);
        Route::get('/books/search', [ApiController::class, 'listBooks'])->name('api.books.search');
        Route::get('/books/export', [ApiController::class, 'listBooks'])->name('api.books.export');
        Route::get('/admin/backup', [ApiController::class, 'health'])->name('api.admin.backup');
        Route::get('/books/{book}', [ApiController::class, 'showBook']);
        Route::get('/categories', [ApiController::class, 'listCategories']);
    });
