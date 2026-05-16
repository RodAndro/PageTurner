@props(['book'])

<div class="space-y-6">
    <h3 class="text-xl font-semibold text-gray-800 mb-4">Customer Reviews</h3>
    @foreach($book->reviews as $review)
        <div class="border-b border-gray-200 pb-6 last:border-b-0">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <div class="font-semibold text-gray-800">{{ $review->user->name }}</div>
                    <div class="flex items-center mt-1">
                        @for($i = 1; $i <= 5; $i++)
                            <x-rating :userReview="$review->rating" :i="$i"/>
                        @endfor
                    </div>
                </div>
                <div class="text-sm text-gray-500">
                    {{ $review->created_at->format('M d, Y') }}
                </div>
            </div>
            <p class="text-gray-700 leading-relaxed">{{ $review->comment }}</p>
        </div>
    @endforeach
</div>