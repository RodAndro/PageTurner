@props(['book'])        


@if (auth()->check())
    <button class="add-to-cart-btn w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-4 rounded transition-colors duration-200
    {{ $book->stock_quantity == 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
    data-modal-target="modal-container"
    data-book-id="{{ $book->id }}"
    data-book-title="{{ $book->title }}"
    data-book-author="{{ $book->author }}"
    data-book-description="{{ $book->description }}"
    data-book-price="{{ $book->price }}"
    data-book-image="{{ $book->cover_image ? asset('storage/' . $book->cover_image) : '/images/no-cover.jpg' }}"
    data-book-stock="{{ $book->stock_quantity }}"
    type="button"
    {{ $book->stock_quantity == 0 ? 'disabled' : '' }}
>
    {{ $book->stock_quantity == 0 ? 'Out of Stock' : 'Add to Cart' }}
</button>

@else
    <form action="{{ route('login') }}">
        <input type="submit" 
        class="add-to-cart-btn w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-4 rounded transition-colors duration-200 cursor-pointer"
        value="{{$book->stock_quantity == 0 ? 'Out of Stock' : 'Add to Cart'}}">
    </form>

@endif


