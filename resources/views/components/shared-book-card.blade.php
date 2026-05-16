    
@props(['book'])

    <a href="{{ route('get_books_details', parameters: $book->id) }}">
        @if($book->cover_image && filter_var($book->cover_image, FILTER_VALIDATE_URL))
            <img src="{{ $book->cover_image }}" alt="{{ $book->title }}" class="w-full h-80 object-cover">
        @elseif($book->cover_image && file_exists(storage_path('app/public/' . $book->cover_image)))
            <img src="{{ asset('storage/' . $book->cover_image) }}" alt="{{ $book->title }}" class="w-full h-80 object-cover">
        @else
            <div class="w-full h-80 bg-gradient-to-br from-primary-200 to-primary-400 flex items-center justify-center">
                <div class="text-center">
                    <svg class="w-20 h-20 text-primary-100 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <p class="text-primary-50 text-sm font-semibold">{{ $book->title }}</p>
                </div>
            </div>
        @endif
    </a>
    
    <div class="p-4">
        <a href="{{ route('get_books_details', parameters: $book->id) }}" class="block hover:text-primary-600 transition-colors">
            <h3 class="text-lg font-bold text-gray-800 mb-2 line-clamp-2">{{ $book->title }}</h3>
        </a>
        
        <p class="text-sm text-gray-600 mb-3">
            <span class="font-medium">By:</span> {{ $book->author }}
        </p>
        
        <p class="text-sm text-gray-600 mb-3">
            <span class="font-medium">Category:</span> {{ $book->category->name }}
        </p>
        
        <!-- Book Rating -->
        @if($book->reviewCount > 0)
        <div class="mb-3 flex items-center">
            <div class="flex items-center">
                @for($i = 1; $i <= 5; $i++)
                        <x-rating :userReview="$book->averageRating" :i="$i"/>
                    @endfor
            </div>
            <span class="ml-2 text-xs text-gray-600">
                {{ number_format($book->averageRating, 1) }} ({{ $book->reviewCount }})
            </span>
        </div>
        @else
        <div class="mb-3">
            <span class="text-xs text-gray-500 italic">No reviews yet</span>
        </div>
        @endif
        