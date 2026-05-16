@props(['book'])

<div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
   <x-shared-book-card :book="$book"/>
   
        <div class="mb-4">
            <span class="text-2xl font-bold text-green-600">₱{{ number_format($book->price, 2) }}</span>
        </div>
    
        @if($book->userReview)
            <div class="bg-blue-50 border border-blue-600 rounded-lg p-3">
                <div class="flex items-center mb-2">
                    <span class="text-sm font-semibold">Your Review</span>
                    <div class="ml-auto flex items-center">
                        @for($i = 1; $i <= 5; $i++)
                       <x-rating :userReview="$book->userReview->rating" :i="$i"/>
                        @endfor
                    </div>
                </div>
                <p class="text-sm text-gray-700">{{ $book->userReview->comment }}</p>
            </div>
        @else
            <button 
                onclick="openReviewModal{{ $book->id }}()" 
                class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors font-semibold"
            >
                Write a Review
            </button>
        @endif
    </div>
</div>

@if(!$book->userReview)
<div id="reviewModal{{ $book->id }}" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">Review: {{ $book->title }}</h3>
            <button onclick="closeReviewModal{{ $book->id }}()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form action="{{ route('purchased-books.review', $book->id) }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                <div class="flex space-x-2">
                    @for($i = 1; $i <= 5; $i++)
                        <label class="cursor-pointer">
                            <input type="radio" name="rating" value="{{ $i }}" class="hidden peer" required>
                            <svg class="w-8 h-8 text-gray-300 peer-checked:text-yellow-400 hover:text-yellow-300 transition-colors fill-current" viewBox="0 0 20 20">
                                <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                            </svg>
                        </label>
                    @endfor
                </div>
                @error('rating')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-4">
                <label for="comment{{ $book->id }}" class="block text-sm font-medium text-gray-700 mb-2">Your Review</label>
                <textarea 
                    id="comment{{ $book->id }}" 
                    name="comment" 
                    rows="4" 
                    required
                    maxlength="1000"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Share your thoughts about this book..."
                ></textarea>
                @error('comment')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="flex space-x-3">
                <button 
                    type="button" 
                    onclick="closeReviewModal{{ $book->id }}()"
                    class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors font-semibold"
                >
                    Cancel
                </button>
                <button 
                    type="submit"
                    class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors font-semibold"
                >
                    Submit Review
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openReviewModal{{ $book->id }}() {
        document.getElementById('reviewModal{{ $book->id }}').classList.remove('hidden');
    }
    
    function closeReviewModal{{ $book->id }}() {
        document.getElementById('reviewModal{{ $book->id }}').classList.add('hidden');
    }
</script>
@endif
