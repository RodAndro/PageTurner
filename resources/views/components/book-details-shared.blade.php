
@props(['book'])

<div>
    <span class="text-gray-600 font-semibold">Author:</span>
    <span class="text-gray-800 ml-2">{{ $book->author }}</span>
</div>

<div>
    <span class="text-gray-600 font-semibold">Category:</span>
    <span class="text-gray-800 ml-2">
        {{ $book->category->name }}
    </span>
</div>

<div>
    <span class="text-gray-600 font-semibold">ISBN:</span>
    <span class="text-gray-800 ml-2">{{ $book->isbn }}</span>
</div>

<div>
    <span class="text-gray-600 font-semibold">Price:</span>
    <span class="text-3xl font-bold text-green-600 ml-2">₱{{ number_format($book->price, 2) }}</span>
</div>

<div>
    <span class="text-gray-600 font-semibold">Stock Available:</span>
    @if($book->stock_quantity > 0)
        <span class="ml-2 text-lg font-semibold {{ $book->stock_quantity < 10 ? 'text-orange-600' : 'text-green-600' }}">
            {{ $book->stock_quantity }}
        </span>
    @else
        <span class="ml-2 text-lg font-semibold text-red-600">Out of Stock</span>
    @endif
</div>