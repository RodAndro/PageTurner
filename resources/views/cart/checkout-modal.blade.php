<div id="checkoutModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-2xl font-bold text-gray-900">Order Summary</h3>
                <button onclick="closeCheckoutModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="mt-4 max-h-96 overflow-y-auto">
                <p class="text-sm text-gray-600 mb-4">Please review your order before proceeding to checkout.</p>
                
                <div class="space-y-3">
                    @foreach($order_items as $item)
                        <div class="flex items-center justify-between border-b pb-3">
                            <div class="flex items-center space-x-3">
                                <img src="{{ $item->book->cover_image ? asset('storage/' . $item->book->cover_image) : '/images/no-cover.jpg' }}" alt="{{ $item->book->title }}" class="w-12 h-16 object-cover rounded">
                                <div>
                                    <h4 class="font-semibold text-gray-900">{{ $item->book->title }}</h4>
                                    <p class="text-sm text-gray-600">by {{ $item->book->author }}</p>
                                    <p class="text-sm text-gray-500">Quantity: {{ $item->quantity }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-900">₱{{ number_format($item->book->price * $item->quantity, 2) }}</p>
                                <p class="text-xs text-gray-500">₱{{ number_format($item->book->price, 2) }} each</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 pt-4 border-t-2">
                    <div class="flex justify-between items-center">
                        <span class="text-xl font-bold text-gray-900">Total Amount:</span>
                        <span class="text-2xl font-bold text-primary-600">₱{{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-between space-x-4">
                <button onclick="closeCheckoutModal()" class="flex-1 bg-gray-500 text-white px-4 py-3 rounded hover:bg-gray-600 font-semibold">
                    Back to Cart
                </button>
                <form action="{{ route('cart.checkout') }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full bg-primary-600 text-white px-4 py-3 rounded hover:bg-primary-700 font-semibold transition-colors duration-200">
                        Confirm Checkout
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openCheckoutModal() {
        document.getElementById('checkoutModal').classList.remove('hidden');
    }

    function closeCheckoutModal() {
        document.getElementById('checkoutModal').classList.add('hidden');
    }

    document.getElementById('checkoutModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeCheckoutModal();
        }
    });
</script>
