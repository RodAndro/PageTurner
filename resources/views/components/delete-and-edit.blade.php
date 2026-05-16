  @props(['book'])
  
    <div class="flex gap-2">
        <button onclick="openEditBookModal({{ $book->id }}, '{{ addslashes($book->title) }}', '{{ addslashes($book->author) }}', {{ $book->category_id }}, '{{ $book->isbn }}', {{ $book->price }}, {{ $book->stock_quantity }}, '{{ addslashes($book->description ?? '') }}', '{{ $book->cover_image ?? '' }}')" 
                class="flex-1 bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-4 rounded transition-colors duration-200">
            Edit
        </button>
        
        <form action="{{ route('admin.books.delete', $book) }}" method="POST" 
                onsubmit="return confirm('Are you sure you want to delete this book? This action cannot be undone.');" 
                class="flex-1">
            @csrf
            @method('DELETE')
            <button type="submit" 
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded transition-colors duration-200">
                Delete
            </button>
        </form>
    </div>