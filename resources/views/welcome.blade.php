@extends('layouts.guest-home')

@section('content')
        <x-search-hero />

        <div class="bg-gray-50 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <x-flash-messages/>

                @if(isset($query) && $query)
                    <div class="mb-8 flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-primary-900 mb-2">Search Results</h2>
                            <p class="text-primary-600">Found {{ $books->total() }} result(s) for "{{ $query }}"</p>
                        </div>
                        <a href="{{ route('guest_books') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            ← Back to All Books
                        </a>
                    </div>
                @else
                    <h2 class="text-2xl font-bold text-primary-900 mb-8">Featured Books</h2>
                @endif
                
                @if(isset($books) && $books->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
                        @foreach($books as $book)
                        <form action="{{ route('login')  }}">
                            <x-book-card :book="$book" />
                        </form>
                        @endforeach
                    </div>

                    <!-- Pagination Links -->
                    <div class="mt-8 flex justify-center">
                        {{ $books->links() }}
                    </div>
                @else
                    <div class="text-center py-16">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        @if(isset($query) && $query)
                            <p class="text-gray-600 text-lg">No books found matching "{{ $query }}".</p>
                            <p class="text-gray-500 text-sm mt-2">Try adjusting your search terms</p>
                        @else
                            <p class="text-gray-600 text-lg">No books available at the moment.</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
@endsection