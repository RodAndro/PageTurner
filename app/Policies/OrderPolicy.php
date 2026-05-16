<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine if the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        return true; // Authenticated users can view their orders
    }

    /**
     * Determine if the user can view the order.
     * Owner or admin can view.
     */
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id || $user->role === 'admin';
    }

    /**
     * Determine if the user can update the order.
     * Only admin can update orders.
     */
    public function update(User $user, Order $order): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine if the user can cancel the order.
     * Owner can cancel if status is Pending, admin can always cancel.
     */
    public function cancel(User $user, Order $order): bool
    {
        if ($user->role === 'admin') {
            return true;
        }
        
        return $user->id === $order->user_id && $order->status === 'Pending';
    }

    /**
     * Determine if the user can delete the order.
     * Only admin can delete.
     */
    public function delete(User $user, Order $order): bool
    {
        return $user->role === 'admin';
    }
}
