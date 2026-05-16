<!-- Category Modal (Add/Edit) -->
<div id="categoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 id="categoryModalTitle" class="text-lg font-semibold text-gray-900">Add New Category</h3>
            <button onclick="closeCategoryModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="categoryForm" action="{{ route('admin.categories.store') }}" method="POST">
            @csrf
            <input type="hidden" id="categoryMethodField" name="_method" value="POST">
            <input type="hidden" id="category_id" name="category_id">
            
            <div class="space-y-4">
                <!-- Name -->
                <div>
                    <label for="category_name" class="block text-sm font-medium text-gray-700">Category Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="category_name" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black"
                           value="{{ old('name') }}">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="category_description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="category_description" rows="4"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                <button type="button" onclick="closeCategoryModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                    Cancel
                </button>
                <button type="submit" id="categorySubmitBtn"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                    Add Category
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddCategoryModal() {
        // Reset form
        document.getElementById('categoryForm').reset();
        document.getElementById('categoryForm').action = "{{ route('admin.categories.store') }}";
        document.getElementById('categoryMethodField').value = 'POST';
        document.getElementById('category_id').value = '';
        
        // Update UI
        document.getElementById('categoryModalTitle').textContent = 'Add New Category';
        document.getElementById('categorySubmitBtn').textContent = 'Add Category';
        
        // Show modal
        document.getElementById('categoryModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function openEditCategoryModal(categoryId, name, description) {
        // Populate form
        document.getElementById('category_id').value = categoryId;
        document.getElementById('category_name').value = name;
        document.getElementById('category_description').value = description || '';
        
        // Update form action and method
        document.getElementById('categoryForm').action = `/admin/categories/${categoryId}`;
        document.getElementById('categoryMethodField').value = 'PUT';
        
        // Update UI
        document.getElementById('categoryModalTitle').textContent = 'Edit Category';
        document.getElementById('categorySubmitBtn').textContent = 'Update Category';
        
        // Show modal
        document.getElementById('categoryModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeCategoryModal() {
        document.getElementById('categoryModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Close modal when clicking outside
    document.getElementById('categoryModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeCategoryModal();
        }
    });
</script>
