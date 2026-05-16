<!-- Book Modal (Add/Edit) -->
<div id="bookModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 id="bookModalTitle" class="text-lg font-semibold text-gray-900">Add New Book</h3>
            <button onclick="closeBookModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="bookForm" action="{{ route('admin.books.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="bookMethodField" name="_method" value="POST">
            <input type="hidden" id="book_id" name="book_id">
            
            <div class="space-y-4">
                <!-- Title -->
                <div>
                    <label for="book_title" class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="book_title" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black"
                           value="{{ old('title') }}">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Author -->
                <div>
                    <label for="book_author" class="block text-sm font-medium text-gray-700">Author <span class="text-red-500">*</span></label>
                    <input type="text" name="author" id="book_author" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black"
                           value="{{ old('author') }}">
                    @error('author')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Category -->
                <div>
                    <label for="book_category_id" class="block text-sm font-medium text-gray-700">Category <span class="text-red-500">*</span></label>
                    <select name="category_id" id="book_category_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">
                        <option value="">Select a category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- ISBN -->
                <div>
                    <label for="book_isbn" class="block text-sm font-medium text-gray-700">ISBN <span class="text-red-500">*</span></label>
                    <input type="text" name="isbn" id="book_isbn" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black"
                           value="{{ old('isbn') }}">
                    @error('isbn')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Price -->
                <div>
                    <label for="book_price" class="block text-sm font-medium text-gray-700">Price <span class="text-red-500">*</span></label>
                    <input type="number" name="price" id="book_price" step="0.01" min="0" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black"
                           value="{{ old('price') }}">
                    @error('price')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Stock Quantity -->
                <div>
                    <label for="book_stock_quantity" class="block text-sm font-medium text-gray-700">Stock Quantity <span class="text-red-500">*</span></label>
                    <input type="number" name="stock_quantity" id="book_stock_quantity" min="0" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black"
                           value="{{ old('stock_quantity') }}">
                    @error('stock_quantity')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="book_description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="book_description" rows="3"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Cover Image -->
                <div>
                    <label for="book_cover_image" class="block text-sm font-medium text-gray-700">Cover Image</label>
                    <input type="file" name="cover_image" id="book_cover_image" accept="image/*"
                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-black hover:file:bg-blue-100">
                    <p id="currentImageName" class="mt-1 text-xs text-gray-600 hidden"></p>
                    <p id="imageHelpText" class="mt-1 text-xs text-gray-500 hidden">Leave empty to keep current image</p>
                    @error('cover_image')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                <button type="button" onclick="closeBookModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                    Cancel
                </button>
                <button type="submit" id="bookSubmitBtn"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                    Add Book
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddBookModal() {
        // Reset form
        document.getElementById('bookForm').reset();
        document.getElementById('bookForm').action = "{{ route('admin.books.store') }}";
        document.getElementById('bookMethodField').value = 'POST';
        document.getElementById('book_id').value = '';
        
        // Update UI
        document.getElementById('bookModalTitle').textContent = 'Add New Book';
        document.getElementById('bookSubmitBtn').textContent = 'Add Book';
        document.getElementById('imageHelpText').classList.add('hidden');
        document.getElementById('currentImageName').classList.add('hidden');
        
        // Show modal
        document.getElementById('bookModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function openEditBookModal(bookId, title, author, categoryId, isbn, price, stockQuantity, description, coverImage) {
        // Populate form fields
        document.getElementById('book_id').value = bookId;
        document.getElementById('book_title').value = title;
        document.getElementById('book_author').value = author;
        document.getElementById('book_category_id').value = categoryId;
        document.getElementById('book_isbn').value = isbn;
        document.getElementById('book_price').value = price;
        document.getElementById('book_stock_quantity').value = stockQuantity;
        document.getElementById('book_description').value = description || '';
        
        // Update form action and method
        document.getElementById('bookForm').action = `/admin/books/${bookId}`;
        document.getElementById('bookMethodField').value = 'PUT';
        
        // Update UI
        document.getElementById('bookModalTitle').textContent = 'Edit Book';
        document.getElementById('bookSubmitBtn').textContent = 'Update Book';
        document.getElementById('imageHelpText').classList.remove('hidden');
        
        // Display current image filename
        const currentImageName = document.getElementById('currentImageName');
        if (coverImage) {
            currentImageName.textContent = 'Current image: ' + coverImage;
            currentImageName.classList.remove('hidden');
        } else {
            currentImageName.classList.add('hidden');
        }
        
        // Show modal
        document.getElementById('bookModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeBookModal() {
        document.getElementById('bookModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Close modal when clicking outside
    document.getElementById('bookModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeBookModal();
        }
    });
</script>
