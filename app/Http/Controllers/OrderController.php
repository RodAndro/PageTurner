<?php

namespace App\Http\Controllers;

use App\Models\Order;

class OrderController extends Controller
{
    public function show_orders()
    {
        $orders = auth()->user()->orders()
            ->whereNot('status', 'Cart')
            ->with(['orderItems.book'])
            ->latest()
            ->paginate(10);

        return view('orders.orders', compact('orders'));
    }

    public function cancel($orderId)
    {
        $order = Order::findOrFail($orderId);

        $this->authorize('cancel', $order);

        if (!in_array($order->status, ['Pending', 'Processing'])) {
            return redirect()->route('orders.show')
                ->withErrors(['error' => 'This order cannot be cancelled. Current status: ' . $order->status]);
        }

        $orderItems = $order->orderItems()->with('book')->get();
        
        foreach ($orderItems as $item) {
            $book = $item->book;
            $book->stock_quantity += $item->quantity;
            $book->save();
        }

        $order->update([
            'status' => 'Cancelled'
        ]);

        return redirect()->route('orders.show')
            ->with('success', 'Order #' . $order->id . ' has been cancelled successfully. Stock has been restored.');
    }
}
