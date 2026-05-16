<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function admin_home(){
        $totalBooks = Book::count();
        $totalCategories = Category::count();
        $totalOrders = Order::where('status', '!=', 'Cart')->count();
        $totalUsers = User::where('role', 'customer')->count();
        $categories = Category::all();
        
        // Recent orders (latest 10)
        $recentOrders = Order::with(['user', 'orderItems'])
            ->where('status', '!=', 'Cart')
            ->latest()
            ->take(10)
            ->get();
        
        // Order status summary
        $orderStatusSummary = Order::where('status', '!=', 'Cart')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');
        
        // Recent customer reviews (latest 10)
        $recentReviews = Review::with(['user', 'book'])
            ->latest()
            ->take(10)
            ->get();
        
        return view('admin.admin-home', compact(
            'totalBooks', 
            'totalCategories', 
            'totalOrders', 
            'totalUsers', 
            'categories',
            'recentOrders',
            'orderStatusSummary',
            'recentReviews'
        ));
    }

    public function storeBook(Request $request)
    {
        $this->authorize('create', Book::class);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'isbn' => 'required|string|max:20|unique:books,isbn',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('cover_image')) {
            $imagePath = Storage::disk('public')->putFile('book_covers', $request->file('cover_image'));
            $validated['cover_image'] = $imagePath;
        }

        Book::create($validated);

        return redirect()->route('admin_home')->with('success', 'Book added successfully!');
    }

    public function storeCategory(Request $request)
    {
        $this->authorize('create', Category::class);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
        ]);

        Category::create($validated);

        return redirect()->route('admin_home')->with('success', 'Category added successfully!');
    }

    public function manage_books(){
        $books = Book::with('category')->latest('updated_at')->paginate(12);
        $categories = Category::all();
        return view('admin.manage-books', compact('books', 'categories'));
    }

    public function updateBook(Request $request, Book $book)
    {
        $this->authorize('update', $book);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'isbn' => 'required|string|max:20|unique:books,isbn,' . $book->id,
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('cover_image')) {
            // delete old cover image if exists
            if ($book->cover_image) {
                Storage::disk('public')->delete($book->cover_image);
            }
            $imagePath = Storage::disk('public')->putFile('book_covers', $request->file('cover_image'));
            $validated['cover_image'] = $imagePath;
        }

        $book->update($validated);

        return redirect()->route('admin.manage_books')->with('success', 'Book updated successfully!');
    }

    public function deleteBook(Book $book)
    {
        $this->authorize('delete', $book);
        
        if ($book->cover_image) {
            Storage::disk('public')->delete($book->cover_image);
        }

        $book->delete();

        return redirect()->route('admin.manage_books')->with('success', 'Book deleted successfully!');
    }

    public function manage_categories(){
        $categories = Category::withCount('books')->latest()->paginate(12);
        return view('admin.manage-categories', compact('categories'));
    }

    public function updateCategory(Request $request, Category $category)
    {
        $this->authorize('update', $category);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
        ]);

        $category->update($validated);

        return redirect()->route('admin.manage_categories')->with('success', 'Category updated successfully!');
    }

    public function deleteCategory(Category $category)
    {
        $this->authorize('delete', $category);
        
        if ($category->books()->count() > 0) {
            return redirect()->route('admin.manage_categories')->with('error', 'Cannot delete category with existing books!');
        }

        $category->delete();

        return redirect()->route('admin.manage_categories')->with('success', 'Category deleted successfully!');
    }

    public function customer_orders(){
        $orders = Order::with(['user', 'orderItems.book'])
            ->latest()
            ->paginate(15);
            
        return view('admin.customer-order', compact('orders'));
    }

    public function updateOrderStatus(Request $request, Order $order)
    {
        $this->authorize('update', $order);
        
        $validated = $request->validate([
            'status' => 'required|in:Pending,Processing,Delivered,Cancelled,Failed,Shipped,Cart'
        ]);

        $oldStatus = $order->status;
        $order->update($validated);

        // Send notification to customer if status changed
        if ($oldStatus !== $validated['status']) {
            try {
                $order->user->notify(new \App\Notifications\OrderStatusChangedNotification($order, $oldStatus));
            } catch (\Exception $e) {
                \Log::error('Failed to send order status notification: ' . $e->getMessage());
            }
        }

        return redirect()->route('admin.customer_orders')->with('success', 'Order status updated successfully!');
    }
}
