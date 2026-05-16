<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\QueryCache;

/**
 * OrderObserver
 * 
 * Automatically invalidates query caches when orders are created, updated, or deleted.
 * Handles order status changes and statistics invalidation.
 * 
 * @package App\Observers
 */
class OrderObserver
{
    /**
     * Handle the Order "created" event.
     * 
     * @param Order $order
     * @return void
     */
    public function created(Order $order)
    {
        $this->invalidateCaches();
    }

    /**
     * Handle the Order "updated" event.
     * 
     * @param Order $order
     * @return void
     */
    public function updated(Order $order)
    {
        $this->invalidateCaches();
    }

    /**
     * Handle the Order "deleted" event.
     * 
     * @param Order $order
     * @return void
     */
    public function deleted(Order $order)
    {
        $this->invalidateCaches();
    }

    /**
     * Handle the Order "restored" event.
     * 
     * @param Order $order
     * @return void
     */
    public function restored(Order $order)
    {
        $this->invalidateCaches();
    }

    /**
     * Handle the Order "force deleted" event.
     * 
     * @param Order $order
     * @return void
     */
    public function forceDeleted(Order $order)
    {
        $this->invalidateCaches();
    }

    /**
     * Invalidate all order-related caches
     * 
     * @return void
     */
    private function invalidateCaches()
    {
        // Invalidate order statistics
        QueryCache::invalidateOrders();
        
        // Invalidate affected caches
        QueryCache::invalidate(
            'stats:orders',
            'bestsellers:10',
            'stats:inventory'
        );
    }
}
