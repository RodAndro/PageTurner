<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\Request;

class PurchasedBooksController extends Controller
{
    public function index()
    {
        // Get all books from orders with 'Delivered' status using Eloquent relationships
        $user = auth()->user();
        
        $purchasedBooks = collect();
        
        // Get all delivered orders for the user
        $deliveredOrders = $user->orders()
            ->where('status', 'Delivered')
            ->with(['orderItems.book.category', 'orderItems.book.reviews'])
            ->get();
        
        // Extract unique books from order items
        foreach ($deliveredOrders as $order) {
            foreach ($order->orderItems as $orderItem) {
                $book = $orderItem->book;
                // Check if book is not already in the collection
                if (!$purchasedBooks->contains('id', $book->id)) {
                    $purchasedBooks->push($book);
                }
            }
        }
        
        return view('purchased_books.purchased_books', compact('purchasedBooks'));
    }

    public function storeReview(Request $request, $bookId)
    {
        $user = auth()->user();
        $book = Book::findOrFail($bookId);
        
        // Use policy to check if user can create review
        $this->authorize('create', [Review::class, $book]);
        
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);
        
        Review::create([
            'user_id' => $user->id,
            'book_id' => $bookId,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        // Get the created review with relationships
        $review = Review::with(['book', 'user'])
            ->where('user_id', $user->id)
            ->where('book_id', $bookId)
            ->latest()
            ->first();

        // Send notification to all admins
        try {
            $admins = \App\Models\User::where('role', 'admin')->get();
            if ($admins->isNotEmpty()) {
                \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\NewReviewAdminNotification($review));
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send review notification: ' . $e->getMessage());
        }
        
        return redirect()->route('purchased-books.show')
            ->with('success', 'Your review has been submitted successfully!');
    }
}
