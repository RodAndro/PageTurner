<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Your Shopping Cart</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-flash-messages/>

            @if($order_items->count() > 0)
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Cart Items -->
                    <div class="lg:col-span-2">
                        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-6">Items in your cart ({{ $order_items->count() }})</h3>
                                <div class="space-y-6">
                                    @foreach($order_items as $item)
                                        <div class="flex items-center border-b pb-6">
                                            <img src="{{ $item->book->cover_image ? asset('storage/' . $item->book->cover_image) : 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 150%22%3E%3Crect fill=%22%23e5e7eb%22 width=%22100%22 height=%22150%22/%3E%3Ctext x=%2250%22 y=%2275%22 font-size=%2212%22 fill=%22%239ca3af%22 text-anchor=%22middle%22 dy=%22.3em%22%3ENo Cover%3C/text%3E%3C/svg%3E' }}" alt="{{ $item->book->title }}" class="w-20 h-32 object-cover rounded mr-6">
                                            <div class="flex-1">
                                                <h4 class="text-lg font-semibold text-gray-900">{{ $item->book->title }}</h4>
                                                <p class="text-gray-600">by {{ $item->book->author }}</p>
                                                <p class="text-gray-500 text-sm mt-2">Stock available: {{ $item->book->stock_quantity }}</p>
                                                <p class="text-indigo-600 font-bold mt-2">₱{{ number_format($item->book->price, 2) }}</p>
                                            </div>
                                            <div class="flex items-center space-x-4">
                                                <form action="{{ route('cart.update', $item->id) }}" method="POST" class="flex items-center space-x-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <label for="quantity-{{ $item->id }}" class="text-sm font-medium">Qty:</label>
                                                    <input 
                                                        type="number" 
                                                        id="quantity-{{ $item->id }}" 
                                                        name="quantity"
                                                        value="{{ $item->quantity }}" 
                                                        min="1" 
                                                        max="{{ $item->book->stock_quantity }}"
                                                        class="w-20 px-2 py-1 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500"
                                                        onchange="this.form.submit()"
                                                    >
                                                </form>
                                                <form action="{{ route('cart.remove', $item->id) }}" method="POST" onsubmit="return confirm('Remove this item from cart?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Remove</button>
                                                </form>
                                            </div>
                                            <div class="ml-6 text-right">
                                                <p class="text-lg font-bold text-gray-900">₱{{ number_format($item->book->price * $item->quantity, 2) }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="lg:col-span-1">
                        <div class="bg-white overflow-hidden shadow-sm rounded-lg sticky top-8">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-6">Order Summary</h3>
                                <div class="space-y-4 mb-6">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Subtotal</span>
                                        <span class="font-medium">₱{{ number_format($order->total_amount, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Shipping</span>
                                        <span class="font-medium">Free</span>
                                    </div>
                                    <div class="border-t pt-4">
                                        <div class="flex justify-between">
                                            <span class="text-lg font-semibold">Total</span>
                                            <span class="text-lg font-bold text-indigo-600">₱{{ number_format($order->total_amount, 2) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <button onclick="openCheckoutModal()" class="w-full bg-indigo-600 text-white py-3 rounded-lg hover:bg-indigo-700 font-semibold mb-3">
                                    Proceed to Checkout
                                </button>
                                <a href="{{ route('dashboard') }}" class="block w-full text-center border border-gray-300 text-gray-700 py-3 rounded-lg hover:bg-gray-50 font-semibold">
                                    Continue Shopping
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Include Checkout Modal -->
                @include('cart.checkout-modal')
            @else
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Your cart is empty</h3>
                        <p class="text-gray-500 mb-6">Start adding some books to your cart!</p>
                        <a href="{{ route('dashboard') }}" class="inline-block px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">
                            Start Shopping
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
