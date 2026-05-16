<div class="bg-gradient-to-r from-primary-50 to-primary-100 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header Text -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-primary-900 mb-3">Discover Your Next Great Read</h1>
            <p class="text-lg text-primary-700">Browse our collection of amazing books</p>
        </div>

        <!-- Search Bar -->
        <div class="flex justify-center">
            <form action="{{ route('books.search') }}" method="GET" class="w-full max-w-md">
                <div class="relative">
                    <!-- Search Input -->
                    <input 
                        type="text" 
                        name="query" 
                        id="hero-search-input"
                        placeholder="Search by title, author, or category..." 
                        value="{{ request('query') }}"
                        class="w-full py-3 px-4 text-gray-700 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-primary-600 focus:ring-2 focus:ring-primary-200 shadow-md">
                    
                    <!-- Clear Button -->
                    @if(request('query'))
                        <button 
                            type="button" 
                            onclick="document.getElementById('hero-search-input').value = ''; document.querySelector('form').submit();" 
                            class="absolute top-0 bottom-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
