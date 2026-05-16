<div class="relative flex-1 max-w-md mx-auto">
    <form action="{{ route('books.search') }}" method="GET" class="w-full" id="search-form">
        <div class="relative w-full">
            <!-- Search Icon -->
            <div class="absolute top-0 bottom-0 left-0 flex items-center pl-3 pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            
            <!-- Search Input -->
            <input 
                type="text" 
                name="query" 
                id="search-input"
                placeholder="Search books by title, author, or category..." 
                value="{{ request('query') }}"
                class="w-full py-2.5 pl-11 pr-10 text-gray-700 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-primary-600 focus:ring-1 focus:ring-primary-600">
            
            <!-- Clear Button -->
            @if(request('query'))
                <button type="button" onclick="clearSearch()" class="absolute top-0 bottom-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            @endif
        </div>
    </form>
</div>

<script>
    function clearSearch() {
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.value = '';
        }
        
        @auth
            @if(auth()->user()->role === 'admin')
                window.location.href = '{{ route('admin_home') }}';
            @else
                window.location.href = '{{ route('dashboard') }}';
            @endif
        @else
            window.location.href = '{{ route('guest_books') }}';
        @endauth
    }
</script>
