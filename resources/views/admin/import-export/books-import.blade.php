<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="text-2xl font-bold mb-6">Import Books</h2>

                    <!-- Download Template -->
                    <div class="mb-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-sm text-gray-700 mb-3">
                            Start by downloading the import template to ensure your file has the correct format and headers.
                        </p>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <a href="{{ url('/sample_books_import.csv') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Download Sample CSV
                            </a>
                        </div>
                    </div>

                    <!-- Import Form -->
                    <form id="book-import-form" action="{{ route('admin.import-export.books-import-store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-6">
                            <label for="file" class="block text-sm font-medium text-gray-700">Select File to Import</label>
                            <input type="file" name="file" id="file" accept=".csv,.xlsx" required 
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 @error('file') border-red-500 @enderror">
                            @error('file')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Supported formats: CSV, XLSX</p>
                            <div class="mt-4">
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">
                                    Import Books
                                </button>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label for="duplicate_mode" class="block text-sm font-medium text-gray-700">Duplicate Handling</label>
                            <select name="duplicate_mode" id="duplicate_mode" required 
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="skip">Skip Duplicates</option>
                                <option value="update">Update Existing Books</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Choose how to handle books with duplicate ISBNs</p>
                        </div>

                        <!-- Validation Rules -->
                        <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <h3 class="text-sm font-bold text-gray-900 mb-3">Validation Rules</h3>
                            <ul class="text-xs text-gray-700 space-y-2">
                                <li><strong>ISBN:</strong> Must be unique and valid ISBN-10 or ISBN-13</li>
                                <li><strong>Title:</strong> Required, max 255 characters</li>
                                <li><strong>Author:</strong> Optional (defaults to 'Unknown')</li>
                                <li><strong>Price:</strong> Required, numeric, positive, max 9,999.99</li>
                                <li><strong>Stock:</strong> Required, non-negative integer</li>
                                <li><strong>Category:</strong> Required, must exist in system</li>
                                <li><strong>Description:</strong> Optional</li>
                            </ul>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <a href="{{ route('admin.import-export.status') }}" class="inline-flex items-center justify-center bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                View Import Status
                            </a>
                        </div>
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
