@props(['book'])

   <div class="p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Ratings & Reviews</h2>
        
        <!-- Overall Rating Summary -->
        <div class="mb-8 pb-6 border-b border-gray-200">
            @if($book->reviewCount > 0)
                <div class="flex items-center space-x-6">
                    <div class="text-center">
                        <div class="text-5xl font-bold text-gray-800">{{ number_format($book->averageRating, 1) }}</div>
                        <div class="flex items-center justify-center mt-2">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= round($book->averageRating))
                                    <svg class="w-6 h-6 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                        <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                    </svg>
                                @else
                                    <svg class="w-6 h-6 text-gray-300 fill-current" viewBox="0 0 20 20">
                                        <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                    </svg>
                                @endif
                            @endfor
                        </div>
                        <div class="text-sm text-gray-600 mt-1">{{ $book->reviewCount }} {{ $book->reviewCount == 1 ? 'review' : 'reviews' }}</div>
                    </div>
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                    <p class="text-gray-500 mt-2">No reviews yet</p>
                    <p class="text-sm text-gray-400">Be the first to review this book!</p>
                </div>
            @endif
        </div>
        
        <!-- Individual Reviews -->
        @if($book->reviewCount > 0)
            <x-review-card :book="$book"/>
        @endif
    </div>