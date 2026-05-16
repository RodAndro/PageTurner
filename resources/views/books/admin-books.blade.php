<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $book->title }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <div>
                            @if($book->cover_image)
                                <img src="{{ asset('storage/' . $book->cover_image) }}" alt="{{ $book->title }}" class="w-full h-auto rounded-lg shadow-lg">
                            @else
                                <div class="w-full aspect-[3/4] bg-gray-200 flex items-center justify-center rounded-lg shadow-lg">
                                    <svg class="w-32 h-32 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800 mb-4">{{ $book->title }}</h1>
                            
                            <div class="space-y-4">
                                <x-book-details-shared :book="$book"/>

                                <div class="pt-4">

                                            <x-delete-and-edit :book="$book"/>
                                            @include('admin.add-book-modal')
                                            
                                </div>
                            </div>

                            <div class="mt-8">
                                    <h3 class="text-xl font-semibold text-gray-800 mb-3">Description</h3>
                                    <p class="text-gray-700 leading-relaxed">{{ $book->description }}</p>
                                </div>

                                <div class="mt-6">
                                    <a href="{{ route('admin.manage_books') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                        ← Back to Books
                                    </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ratings and Reviews Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                              <x-ratings-review-section :book="$book"/>
            </div>
        </div>
    </div>
</x-admin-layout>

