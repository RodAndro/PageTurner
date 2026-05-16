<x-admin-layout>
    <x-search-hero />
    
    <div class="bg-gray-50 py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success Message -->
            <x-flash-messages/>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Books -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-500">Total Books</h3>
                        <p class="text-2xl font-bold text-gray-900">{{ $totalBooks }}</p>
                    </div>
                </div>

                <!-- Total Categories -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-500">Categories</h3>
                        <p class="text-2xl font-bold text-gray-900">{{ $totalCategories }}</p>
                    </div>
                </div>

                <!-- Total Orders -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-500">Total Orders</h3>
                        <p class="text-2xl font-bold text-gray-900">{{ $totalOrders }}</p>
                    </div>
                </div>

                <!-- Total Customers -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-500">Customers</h3>
                        <p class="text-2xl font-bold text-gray-900">{{ $totalUsers }}</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions and Order Status Summary in one row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Quick Actions -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <button onclick="openAddBookModal()" class="w-full flex items-center p-4 border border-gray-300 hover:border-gray-400 rounded-lg transition-colors text-left">
                                <span class="text-gray-700 font-medium">+ Add New Book</span>
                            </button>

                            <button onclick="openAddCategoryModal()" class="w-full flex items-center p-4 border border-gray-300 hover:border-gray-400 rounded-lg transition-colors text-left">
                                <span class="text-gray-700 font-medium">+ Add New Category</span>
                            </button>

                            <a href="{{ route('admin.manage_books') }}" class="w-full flex items-center p-4 border border-gray-300 hover:border-gray-400 rounded-lg transition-colors">
                                <span class="text-gray-700 font-medium">Manage Books</span>
                            </a>

                            <a href="{{ route('admin.manage_categories') }}" class="w-full flex items-center p-4 border border-gray-300 hover:border-gray-400 rounded-lg transition-colors">
                                <span class="text-gray-700 font-medium">Manage Categories</span>
                            </a>

                            <a href="{{ route('admin.customer_orders') }}" class="w-full flex items-center p-4 border border-gray-300 hover:border-gray-400 rounded-lg transition-colors">
                                <span class="text-gray-700 font-medium">Customer Orders</span>
                            </a>

                            <a href="{{ route('admin.import-export.books-import') }}" class="w-full flex items-center p-4 border border-gray-300 hover:border-gray-400 rounded-lg transition-colors">
                                <span class="text-gray-700 font-medium">📥 Import Books</span>
                            </a>

                            <a href="{{ route('admin.import-export.books-export') }}" class="w-full flex items-center p-4 border border-gray-300 hover:border-gray-400 rounded-lg transition-colors">
                                <span class="text-gray-700 font-medium">📤 Export Books</span>
                            </a>

                            <a href="{{ route('admin.import-export.orders-export') }}" class="w-full flex items-center p-4 border border-gray-300 hover:border-gray-400 rounded-lg transition-colors">
                                <span class="text-gray-700 font-medium">📤 Export Orders</span>
                            </a>

                            <a href="{{ route('admin.import-export.users-import') }}" class="w-full flex items-center p-4 border border-gray-300 hover:border-gray-400 rounded-lg transition-colors">
                                <span class="text-gray-700 font-medium">👥 User Management</span>
                            </a>

                            <a href="{{ route('admin.import-export.status') }}" class="w-full flex items-center p-4 border border-gray-300 hover:border-gray-400 rounded-lg transition-colors">
                                <span class="text-gray-700 font-medium">⚙️ Import/Export Status</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Order Status Summary -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Status Summary</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8 pb-8 border-b border-gray-200">
                            @php
                                $statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
                            @endphp
                            
                            @foreach($statuses as $status)
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <p class="text-xs font-medium text-gray-500 uppercase">{{ $status }}</p>
                                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $orderStatusSummary[$status] ?? 0 }}</p>
                                </div>
                            @endforeach
                        </div>

                        <!-- Data Import/Export Operations -->
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">Data Import/Export Operations</h3>
                        
                        <!-- Book Import/Export Section -->
                        <div class="mb-6">
                            <h4 class="text-md font-semibold text-gray-800 mb-3 flex items-center">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-md bg-blue-100 text-blue-600 mr-3">📚</span>
                                Book Management
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <a href="{{ route('admin.import-export.books-import') }}" class="flex items-center justify-between p-3 border-2 border-blue-200 hover:border-blue-400 hover:bg-blue-50 rounded-lg transition-colors">
                                    <div>
                                        <h5 class="font-semibold text-gray-900 text-sm">Import Books</h5>
                                        <p class="text-xs text-gray-600 mt-1">Upload bulk books from CSV/XLSX</p>
                                    </div>
                                    <span class="text-lg">📥</span>
                                </a>
                                
                                <a href="{{ route('admin.import-export.books-template') }}" download class="flex items-center justify-between p-3 border-2 border-purple-200 hover:border-purple-400 hover:bg-purple-50 rounded-lg transition-colors">
                                    <div>
                                        <h5 class="font-semibold text-gray-900 text-sm">Download Template</h5>
                                        <p class="text-xs text-gray-600 mt-1">Get the import template with required headers</p>
                                    </div>
                                    <span class="text-lg">📋</span>
                                </a>

                                <a href="{{ route('admin.import-export.books-export') }}" class="flex items-center justify-between p-3 border-2 border-cyan-200 hover:border-cyan-400 hover:bg-cyan-50 rounded-lg transition-colors">
                                    <div>
                                        <h5 class="font-semibold text-gray-900 text-sm">Export Books</h5>
                                        <p class="text-xs text-gray-600 mt-1">Filter books and export XLSX, CSV, or PDF</p>
                                    </div>
                                    <span class="text-lg">📤</span>
                                </a>
                            </div>
                        </div>

                        <!-- Order Export Section -->
                        <div class="mb-6">
                            <h4 class="text-md font-semibold text-gray-800 mb-3 flex items-center">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-md bg-green-100 text-green-600 mr-3">📦</span>
                                Order Management
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <a href="{{ route('admin.import-export.orders-export') }}" class="flex items-center justify-between p-3 border-2 border-green-200 hover:border-green-400 hover:bg-green-50 rounded-lg transition-colors">
                                    <div>
                                        <h5 class="font-semibold text-gray-900 text-sm">Export Orders</h5>
                                        <p class="text-xs text-gray-600 mt-1">Export orders with filters (XLSX, CSV, PDF)</p>
                                    </div>
                                    <span class="text-lg">📤</span>
                                </a>
                                
                                <a href="{{ route('admin.customer_orders') }}" class="flex items-center justify-between p-3 border-2 border-teal-200 hover:border-teal-400 hover:bg-teal-50 rounded-lg transition-colors">
                                    <div>
                                        <h5 class="font-semibold text-gray-900 text-sm">Manage Customer Orders</h5>
                                        <p class="text-xs text-gray-600 mt-1">View and update order statuses</p>
                                    </div>
                                    <span class="text-lg">📋</span>
                                </a>
                            </div>
                        </div>

                        <!-- User Management Section -->
                        <div class="mb-6">
                            <h4 class="text-md font-semibold text-gray-800 mb-3 flex items-center">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-md bg-orange-100 text-orange-600 mr-3">👥</span>
                                User Management (Admin Only)
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <a href="{{ route('admin.import-export.users-import') }}" class="flex items-center justify-between p-3 border-2 border-orange-200 hover:border-orange-400 hover:bg-orange-50 rounded-lg transition-colors">
                                    <div>
                                        <h5 class="font-semibold text-gray-900 text-sm">Import Users</h5>
                                        <p class="text-xs text-gray-600 mt-1">Bulk create user accounts with role assignment</p>
                                    </div>
                                    <span class="text-lg">👤➕</span>
                                </a>
                                
                                <a href="{{ route('admin.import-export.users-export') }}" class="flex items-center justify-between p-3 border-2 border-red-200 hover:border-red-400 hover:bg-red-50 rounded-lg transition-colors">
                                    <div>
                                        <h5 class="font-semibold text-gray-900 text-sm">Export Users</h5>
                                        <p class="text-xs text-gray-600 mt-1">Export user data with GDPR PII redaction options</p>
                                    </div>
                                    <span class="text-lg">👥📤</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Operations Status Card -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg mb-8">
                <div class="p-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <span class="inline-flex items-center justify-center h-8 w-8 rounded-md bg-indigo-100 text-indigo-600 mr-3">⚙️</span>
                        Operations Status
                    </h4>
                    <a href="{{ route('admin.import-export.status') }}" class="flex items-center justify-between p-4 border-2 border-indigo-200 hover:border-indigo-400 hover:bg-indigo-50 rounded-lg transition-colors">
                        <div>
                            <h5 class="font-semibold text-gray-900">View Import/Export Status</h5>
                            <p class="text-sm text-gray-600 mt-1">Track queued and completed operations, download results</p>
                        </div>
                        <span class="text-2xl">📊</span>
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- Include Modals -->
    @include('admin.add-book-modal')
    @include('admin.add-category-modal')
</x-admin-layout>
