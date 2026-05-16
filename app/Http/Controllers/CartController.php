<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
 


# find order using user_id and status is cart
# in orderitems, match the order_id
public function cart_view() {

    $order = Auth::user()->orders()
        ->where('status', 'Cart')
        ->first();

    if (!$order) {
        $order_items = collect();
    } else {
        $order_items = $order->orderItems()
            ->with('book')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    return view('cart.cart', compact('order', 'order_items'));
}




public function add_to_cart(Request $request){
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $book = Book::findOrFail($request->book_id);
        $requestedQuantity = (int) $request->quantity;

        if ($book->stock_quantity < $requestedQuantity) {
            return redirect()->back()->withErrors(['quantity' => 'Not enough stock available.']);
        }

        $cartOrder = Order::firstOrCreate([
            'user_id' => Auth::id(),
            'status' => 'Cart'
        ], [
            'total_amount' => 0.00
        ]);

        # checks if the book is already in the cart.

        $existingCartItem = OrderItem::where([
            'order_id' => $cartOrder->id,
            'book_id' => $book->id
        ])->first();

        if ($existingCartItem) {
            # if it is, add the existing quantity to new quantity

            $newQuantity = $existingCartItem->quantity + $requestedQuantity;
            
            if ($newQuantity > $book->stock_quantity) {
                return redirect()->back()->withErrors(['quantity' => 'Adding this quantity would exceed available stock.']);
            }
            
            #update
            $existingCartItem->update([
                'quantity' => $newQuantity
            ]);
        } else {
            #if not, create new order item
            OrderItem::create([
                'order_id' => $cartOrder->id,
                'book_id' => $book->id,
                'quantity' => $requestedQuantity,
                'unit_price' => $book->price 
            ]);
        }

        $cartTotal = OrderItem::where('order_id', $cartOrder->id)
            ->join('books', 'order_items.book_id', '=', 'books.id')
            ->sum(DB::raw('order_items.quantity * books.price'));
        
        $cartOrder->update([
            'total_amount' => $cartTotal
        ]);

        return redirect()->route('dashboard')->with('success', 'Item added to cart successfully!');
    }

    public function update_quantity(Request $request, $orderItemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $orderItem = OrderItem::with('book')->findOrFail($orderItemId);
        

        #checks if the user own the order it tries to edit
        $cartOrder = Auth::user()->orders()
            ->where('status', 'Cart')
            ->where('id', $orderItem->order_id)
            ->first();

        if (!$cartOrder) {
            return redirect()->route('cart')->withErrors(['error' => 'Unauthorized access.']);
        }

        $requestedQuantity = (int) $request->quantity;
        $book = $orderItem->book;

        if ($requestedQuantity > $book->stock_quantity) {
            return redirect()->route('cart')
                ->withErrors(['quantity' => 'Not enough stock available. Only ' . $book->stock_quantity . ' items left.']);
        }

        $orderItem->update(['quantity' => $requestedQuantity]);

        $cartTotal = OrderItem::where('order_id', $cartOrder->id)
            ->join('books', 'order_items.book_id', '=', 'books.id')
            ->sum(DB::raw('order_items.quantity * books.price'));
        
        $cartOrder->update(['total_amount' => $cartTotal]);

        return redirect()->route('cart')->with('success', 'Cart updated successfully!');
    }

    public function remove_from_cart($orderItemId)
    {
        $orderItem = OrderItem::findOrFail($orderItemId);
        
        #same checker. 
        $cartOrder = Auth::user()->orders()
            ->where('status', 'Cart')
            ->where('id', $orderItem->order_id)
            ->first();

        if (!$cartOrder) {
            return redirect()->route('cart')->withErrors(['error' => 'Unauthorized access.']);
        }

        $orderItem->delete();

        $cartTotal = OrderItem::where('order_id', $cartOrder->id)
            ->join('books', 'order_items.book_id', '=', 'books.id')
            ->sum(DB::raw('order_items.quantity * books.price'));
        
        $cartOrder->update(['total_amount' => $cartTotal]);

        return redirect()->route('cart')->with('success', 'Item removed from cart.');
    }

    #checkouts the order. change the status to pending.
    public function checkout()
    
    {
        $cartOrder = Auth::user()->orders()
            ->where('status', 'Cart')
            ->first();

        if (!$cartOrder) {
            return redirect()->route('cart')->withErrors(['error' => 'Your cart is empty.']);
        }

        $cartItems = $cartOrder->orderItems()->with('book')->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart')->withErrors(['error' => 'Your cart is empty.']);
        }

        foreach ($cartItems as $item) {
            if ($item->quantity > $item->book->stock_quantity) {
                return redirect()->route('cart')
                    ->withErrors(['stock' => 'Not enough stock for ' . $item->book->title . '. Only ' . $item->book->stock_quantity . ' left.']);
            }
        }

        foreach ($cartItems as $item) {
            $book = $item->book;
            $book->stock_quantity -= $item->quantity;
            $book->save();
        }

        $cartOrder->update([
            'status' => 'Pending',
            'updated_at' => now()
        ]);

        // Send notification to customer
        try {
            $cartOrder->user->notify(new \App\Notifications\OrderPlacedNotification($cartOrder));
        } catch (\Exception $e) {
            Log::error('Failed to send order placed notification: ' . $e->getMessage());
        }

        // Send notification to all admins
        try {
            $admins = \App\Models\User::where('role', 'admin')->get();
            if ($admins->isNotEmpty()) {
                \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\NewOrderAdminNotification($cartOrder));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send admin order notification: ' . $e->getMessage());
        }

        return redirect()->route('dashboard')
            ->with('success', 'Order placed successfully! Your order total is $' . number_format($cartOrder->total_amount, 2));
    }
}
