<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="text-2xl font-bold mb-6">Export Books</h2>

                    <form action="{{ route('admin.import-export.books-export-store') }}" method="POST">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div>
                                <label for="format" class="block text-sm font-medium text-gray-700">Export Format</label>
                                <select name="format" id="format" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                                    <option value="xlsx">Excel (XLSX)</option>
                                    <option value="csv">CSV</option>
                                    <option value="pdf">PDF</option>
                                </select>
                            </div>

                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                                <select name="category" id="category" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                                    <option value="">All Categories</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="stock_status" class="block text-sm font-medium text-gray-700">Stock Status</label>
                                <select name="stock_status" id="stock_status" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                                    <option value="">All Stock</option>
                                    @foreach ($stockStatuses as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="price_min" class="block text-sm font-medium text-gray-700">Min Price</label>
                                <input type="number" step="0.01" min="0" name="price_min" id="price_min" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label for="price_max" class="block text-sm font-medium text-gray-700">Max Price</label>
                                <input type="number" step="0.01" min="0" name="price_max" id="price_max" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label for="date_from" class="block text-sm font-medium text-gray-700">From Date</label>
                                <input type="date" name="date_from" id="date_from" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label for="date_to" class="block text-sm font-medium text-gray-700">To Date</label>
                                <input type="date" name="date_to" id="date_to" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            </div>
                        </div>

                        <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <h3 class="text-sm font-bold text-gray-900 mb-3">Select Columns to Export</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                @foreach ([
                                    'isbn' => 'ISBN',
                                    'title' => 'Title',
                                    'author' => 'Author',
                                    'category_name' => 'Category',
                                    'price' => 'Price',
                                    'stock_quantity' => 'Stock',
                                    'description' => 'Description',
                                    'created_at' => 'Created Date',
                                    'updated_at' => 'Updated Date',
                                ] as $value => $label)
                                    <label class="flex items-center">
                                        <input type="checkbox" name="columns[]" value="{{ $value }}" checked class="rounded">
                                        <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Export Books
                        </button>
                    </form>

                    @if ($errors->any())
                        <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <h3 class="text-sm font-bold text-red-900 mb-2">Errors</h3>
                            <ul class="text-xs text-red-700 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
