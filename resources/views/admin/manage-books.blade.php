<x-admin-layout>
    <x-search-hero />
    
    <div class="bg-gray-50 py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success Message -->
            <x-flash-messages/>

            @if(isset($query) && $query)
                <div class="mb-6 flex items-center gap-3">
                    <a href="{{ route('admin.manage_books') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        ← Back to All Books
                    </a>
                    <p class="text-gray-600">Found {{ $books->total() }} result(s)</p>
                </div>
            @endif

            <!-- Books Grid -->
            @if($books->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6 text-center">
                        @if(isset($query) && $query)
                            <p class="text-gray-500">No books found matching "{{ $query }}".</p>
                        @else
                            <p class="text-gray-500">No books found. Add your first book from the dashboard.</p>
                        @endif
                    </div>
                </div>
            @else

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($books as $book)
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                            <!-- Book Cover -->
                            <x-shared-book-card :book="$book"/>
                                
                                <p class="text-sm text-gray-600 mb-4">
                                    <span class="font-medium">Stock:</span> 
                                    <span class="{{ $book->stock_quantity > 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $book->stock_quantity }} units
                                    </span>
                                </p>
                                    <x-delete-and-edit :book="$book"/>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination Links -->
                <div class="mt-6">
                    {{ $books->links() }}
                </div>

            @endif
        </div>
    </div>

    <!-- Include Book Modal (Add/Edit) -->
    @include('admin.add-book-modal')


</x-admin-layout>