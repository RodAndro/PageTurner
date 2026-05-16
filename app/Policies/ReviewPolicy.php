<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Determine if the user can view any reviews.
     */
    public function viewAny(User $user): bool
    {
        return true; // Everyone can view reviews
    }

    /**
     * Determine if the user can view the review.
     */
    public function view(User $user, Review $review): bool
    {
        return true; // Everyone can view a review
    }

    /**
     * Determine if the user can create a review for a book.
     * User must be verified and have purchased the book.
     */
    public function create(User $user, Book $book): bool
    {
        // Must have verified email
        if (!$user->hasVerifiedEmail()) {
            return false;
        }

        // Check if user has purchased this book (has a delivered order with this book)
        $hasPurchased = $user->orders()
            ->whereIn('status', ['Delivered', 'Shipped', 'Processing'])
            ->whereHas('orderItems', function ($query) use ($book) {
                $query->where('book_id', $book->id);
            })
            ->exists();

        if (!$hasPurchased) {
            return false;
        }

        // Check if user already reviewed this book
        $hasReviewed = Review::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->exists();

        return !$hasReviewed; // Can create if not already reviewed
    }

    /**
     * Determine if the user can update the review.
     */
    public function update(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }

    /**
     * Determine if the user can delete the review.
     */
    public function delete(User $user, Review $review): bool
    {
        return $user->id === $review->user_id || $user->role === 'admin';
    }
}
