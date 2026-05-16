<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Purchased Books') }}
        </h2>
    </x-slot>
    
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-flash-messages/>
            
            @if(isset($purchasedBooks) && $purchasedBooks->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
                    @foreach($purchasedBooks as $book)
                        <x-purchased-book-card :book="$book" />
                    @endforeach
                </div>
            @else
                <div class="text-center py-12 bg-white shadow-md rounded-lg">
                    <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <p class="text-gray-600 text-lg mt-4">You haven't received any delivered books yet.</p>
                    <p class="text-gray-500 text-sm mt-2">Books from delivered orders will appear here.</p>
                    <a href="{{ route('dashboard') }}" class="mt-6 inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors font-semibold">
                        Browse Books
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
