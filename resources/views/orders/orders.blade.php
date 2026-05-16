<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Orders</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-flash-messages/>

            @if($orders->count() > 0)
                <div class="space-y-6">
                    @foreach($orders as $order)
                        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                            <div class="bg-gradient-to-r from-indigo-50 to-primary-50 px-6 py-4 border-b">
                                <div class="flex justify-between items-start">
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
                                        <div>
                                            <p class="text-xs font-medium text-gray-600 uppercase">Order ID</p>
                                            <p class="text-lg font-bold text-gray-900">#{{ $order->id }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-600 uppercase">Date</p>
                                            <p class="font-semibold text-gray-900">{{ $order->created_at->format('M d, Y') }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-600 uppercase">Total</p>
                                            <p class="text-lg font-bold text-indigo-600">₱{{ number_format($order->total_amount, 2) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-600 uppercase">Status</p>
                                            <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold
                                                @if($order->status === 'Pending') bg-yellow-100 text-yellow-800
                                                @elseif($order->status === 'Processing') bg-blue-100 text-blue-800
                                                @elseif($order->status === 'Shipped') bg-purple-100 text-purple-800
                                                @elseif($order->status === 'Delivered') bg-green-100 text-green-800
                                                @elseif($order->status === 'Cancelled') bg-red-100 text-red-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ $order->status }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    @if($order->status === 'Pending')
                                        <form action="{{ route('orders.cancel', $order->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this order? Stock will be restored.')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 font-semibold text-sm">
                                                Cancel Order
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <div class="p-6">
                                <h4 class="font-semibold text-gray-900 mb-4">Order Items ({{ $order->orderItems->count() }})</h4>
                                <div class="space-y-4">
                                    @foreach($order->orderItems as $item)
                                        <div class="flex items-center justify-between border-b pb-4">
                                            <div class="flex items-center space-x-4">
                                                <img src="{{ $item->book->cover_image ? asset('storage/' . $item->book->cover_image) : 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 150%22%3E%3Crect fill=%22%23e5e7eb%22 width=%22100%22 height=%22150%22/%3E%3Ctext x=%2250%22 y=%2275%22 font-size=%2212%22 fill=%22%239ca3af%22 text-anchor=%22middle%22 dy=%22.3em%22%3ENo Cover%3C/text%3E%3C/svg%3E' }}" alt="{{ $item->book->title }}" class="w-16 h-24 object-cover rounded">
                                                <div>
                                                    <h5 class="font-semibold text-gray-900">{{ $item->book->title }}</h5>
                                                    <p class="text-sm text-gray-600">by {{ $item->book->author }}</p>
                                                    <p class="text-sm text-gray-500 mt-1">Quantity: <span class="font-medium">{{ $item->quantity }}</span></p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-bold text-gray-900">₱{{ number_format($item->unit_price * $item->quantity, 2) }}</p>
                                                <p class="text-xs text-gray-500">₱{{ number_format($item->unit_price, 2) }} each</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $orders->links() }}
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">No orders yet</h3>
                        <p class="text-gray-500 mb-6">You haven't placed any orders yet. Start browsing our collection!</p>
                        <a href="{{ route('dashboard') }}" class="inline-block px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">
                            Start Shopping
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
