<x-admin-layout>
    <x-search-hero />
    
    <div class="bg-gray-50 py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-flash-messages/>

            @if(session('error'))
                <div id="error-message" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Categories Grid -->
            @if($categories->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6 text-center">
                        <p class="text-gray-500">No categories found. Add your first category from the dashboard.</p>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                    @foreach($categories as $category)
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                            <div class="p-6">
                                <!-- Category Name -->
                                <h3 class="text-xl font-bold text-gray-800 mb-3">{{ $category->name }}</h3>
                                
                                <!-- Description -->
                                @if($category->description)
                                    <p class="text-sm text-gray-600 mb-4 line-clamp-3">{{ $category->description }}</p>
                                @else
                                    <p class="text-sm text-gray-400 italic mb-4">No description</p>
                                @endif

                                <!-- Books Count -->
                                <div class="mb-4 flex items-center text-sm text-gray-600">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    <span class="font-medium">{{ $category->books_count }} {{ Str::plural('book', $category->books_count) }}</span>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex gap-2">
                                    <!-- Edit Button -->
                                    <button onclick="openEditCategoryModal({{ $category->id }}, '{{ addslashes($category->name) }}', '{{ addslashes($category->description ?? '') }}')" 
                                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded transition-colors duration-200">
                                        Edit
                                    </button>
                                    
                                    <!-- Delete Button -->
                                    <form action="{{ route('admin.categories.delete', $category) }}" method="POST" 
                                          onsubmit="return confirm('Are you sure you want to delete this category? This action cannot be undone.');" 
                                          class="flex-1">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded transition-colors duration-200"
                                                {{ $category->books_count > 0 ? 'disabled title="Cannot delete category with books"' : '' }}>
                                            Delete
                                        </button>
                                    </form>
                                </div>

                                @if($category->books_count > 0)
                                    <p class="text-xs text-gray-500 mt-2 text-center">
                                        Cannot delete: has {{ $category->books_count }} {{ Str::plural('book', $category->books_count) }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination Links -->
                <div class="mt-6">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Include Category Modal -->
    @include('admin.add-category-modal')

    <script>
        // Auto-hide error message
        setTimeout(function() {
            let message = document.getElementById('error-message');
            if (message) {
                message.style.display = 'none';
            }
        }, 5000);
    </script>
</x-admin-layout>