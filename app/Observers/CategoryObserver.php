<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\QueryCache;

/**
 * CategoryObserver
 * 
 * Automatically invalidates query caches when categories are created, updated, or deleted.
 * This ensures cache consistency with database changes.
 * 
 * @package App\Observers
 */
class CategoryObserver
{
    /**
     * Handle the Category "created" event.
     * 
     * @param Category $category
     * @return void
     */
    public function created(Category $category)
    {
        $this->invalidateCaches();
    }

    /**
     * Handle the Category "updated" event.
     * 
     * @param Category $category
     * @return void
     */
    public function updated(Category $category)
    {
        $this->invalidateCaches();
    }

    /**
     * Handle the Category "deleted" event.
     * 
     * @param Category $category
     * @return void
     */
    public function deleted(Category $category)
    {
        $this->invalidateCaches();
    }

    /**
     * Handle the Category "restored" event.
     * 
     * @param Category $category
     * @return void
     */
    public function restored(Category $category)
    {
        $this->invalidateCaches();
    }

    /**
     * Handle the Category "force deleted" event.
     * 
     * @param Category $category
     * @return void
     */
    public function forceDeleted(Category $category)
    {
        $this->invalidateCaches();
    }

    /**
     * Invalidate all category-related caches
     * 
     * @return void
     */
    private function invalidateCaches()
    {
        QueryCache::invalidateCategories();
        // Categories affect book queries
        QueryCache::invalidateBooks();
    }
}
