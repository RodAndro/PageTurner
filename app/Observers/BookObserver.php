<?php

namespace App\Observers;

use App\Models\Book;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class BookObserver
{
    /**
     * Handle the book "created" event.
     */
    public function created(Book $book): void
    {
        // Invalidate caches when new book is created
        $this->invalidateCatalogCache($book);
        
        // Invalidate specific category cache
        $this->invalidateCategoryCache($book);
        
        // Invalidate specific book cache
        $this->invalidateBookCache($book);
        
        Log::info('Book created, caches invalidated', [
            'book_id' => $book->id,
            'category_id' => $book->category_id,
            'isbn' => $book->isbn,
        ]);
    }

    /**
     * Handle the book "updated" event.
     */
    public function updated(Book $book): void
    {
        // Invalidate caches when book is updated
        $this->invalidateCatalogCache($book);
        $this->invalidateCategoryCache($book);
        $this->invalidateBookCache($book);
        
        Log::info('Book updated, caches invalidated', [
            'book_id' => $book->id,
            'category_id' => $book->category_id,
            'isbn' => $book->isbn,
            'changes' => $book->getDirty(),
        ]);
    }

    /**
     * Handle the book "deleted" event.
     */
    public function deleted(Book $book): void
    {
        // Invalidate caches when book is deleted
        $this->invalidateCatalogCache($book);
        $this->invalidateCategoryCache($book);
        $this->invalidateBookCache($book);
        
        // Also invalidate search cache and related caches
        Cache::tags(['search', 'related'])->flush();
        
        Log::info('Book deleted, caches invalidated', [
            'book_id' => $book->id,
            'category_id' => $book->category_id,
            'isbn' => $book->isbn,
        ]);
    }

    /**
     * Handle the book "saved" event.
     */
    public function saved(Book $book): void
    {
        // Invalidate caches when book is saved (covers both create and update)
        $this->invalidateCatalogCache($book);
        $this->invalidateCategoryCache($book);
        $this->invalidateBookCache($book);
        
        Log::info('Book saved, caches invalidated', [
            'book_id' => $book->id,
            'category_id' => $book->category_id,
            'isbn' => $book->isbn,
        ]);
    }

    /**
     * Invalidate catalog cache
     */
    protected function invalidateCatalogCache(Book $book): void
    {
        // Invalidate general catalog cache
        Cache::tags(['books', 'catalog'])->flush();
    }

    /**
     * Invalidate category-specific cache
     */
    protected function invalidateCategoryCache(Book $book): void
    {
        if ($book->category_id) {
            Cache::tags(["category:{$book->category_id}"])->flush();
        }
    }

    /**
     * Invalidate specific book cache
     */
    protected function invalidateBookCache(Book $book): void
    {
        // Invalidate specific book caches
        Cache::forget("book:isbn:{$book->isbn}");
        Cache::forget("book:{$book->id}");
        
        // Also invalidate related book caches
        Cache::tags(["related:{$book->id}"])->flush();
    }

    /**
     * Invalidate search cache
     */
    protected function invalidateSearchCache(Book $book): void
    {
        // Invalidate search caches that might contain this book
        Cache::tags(['search', 'search_suggestions'])->flush();
    }

    /**
     * Invalidate bestseller cache
     */
    protected function invalidateBestsellerCache(Book $book): void
    {
        // Invalidate bestseller caches when book data changes
        Cache::tags(['bestsellers', 'statistics'])->flush();
    }

    /**
     * Invalidate export cache
     */
    protected function invalidateExportCache(Book $book): void
    {
        // Invalidate export caches when book data changes
        Cache::tags(['exports', 'catalog_export'])->flush();
    }

    /**
     * Log cache invalidation for debugging
     */
    protected function logCacheInvalidation(string $operation, Book $book, array $tags): void
    {
        Log::debug('Cache invalidation', [
            'operation' => $operation,
            'book_id' => $book->id,
            'category_id' => $book->category_id,
            'isbn' => $book->isbn,
            'tags' => $tags,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
