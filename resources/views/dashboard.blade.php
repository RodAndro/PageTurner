<x-app-layout>
    <x-search-hero />
    
    <div class="bg-gray-50 py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-flash-messages/>

            @if(isset($query) && $query)
                <div class="mb-6 flex items-center gap-3">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        ← Back to All Books
                    </a>
                    <p class="text-gray-600">Found {{ $books->total() }} result(s)</p>
                </div>
            @endif
            
            @if(isset($books) && $books->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

                    @foreach($books as $book)
                        <x-book-card :book="$book" />
                    @endforeach
                </div>

                <!-- Pagination Links -->
                <div class="mt-6">
                    {{ $books->links() }}
                </div>

                @include('cart.add-to-cart-modal')

            @else
                <div class="text-center py-12">
                    @if(isset($query) && $query)
                        <p class="text-gray-600 text-lg">No books found matching "{{ $query }}".</p>
                    @else
                        <p class="text-gray-600 text-lg">No books available at the moment.</p>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

<!-- Backup Chatbot -->
<x-api-chatbot />
