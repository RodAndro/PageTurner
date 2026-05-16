<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="text-2xl font-bold mb-6">Export Orders</h2>

                    <form action="{{ route('admin.import-export.orders-export-store') }}" method="POST">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Format Selection -->
                            <div>
                                <label for="format" class="block text-sm font-medium text-gray-700">Export Format</label>
                                <select name="format" id="format" required 
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="xlsx">Excel (XLSX)</option>
                                    <option value="csv">CSV</option>
                                    <option value="pdf">PDF</option>
                                </select>
                            </div>

                            <!-- Status Filter -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700">Order Status</label>
                                <select name="status" id="status" 
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">All Statuses</option>
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status }}">{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Date From -->
                            <div>
                                <label for="date_from" class="block text-sm font-medium text-gray-700">From Date</label>
                                <input type="date" name="date_from" id="date_from" 
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <!-- Date To -->
                            <div>
                                <label for="date_to" class="block text-sm font-medium text-gray-700">To Date</label>
                                <input type="date" name="date_to" id="date_to" 
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div>
                                <label for="customer_email" class="block text-sm font-medium text-gray-700">Customer Email</label>
                                <input type="text" name="customer_email" id="customer_email"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="flex items-center">
                                <input type="checkbox" name="financial_report" value="1" class="rounded">
                                <span class="ml-2 text-sm text-gray-700">Generate financial report with revenue, tax, and subtotal columns</span>
                            </label>
                        </div>

                        <!-- Column Selection -->
                        <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <h3 class="text-sm font-bold text-gray-900 mb-3">Select Columns to Export</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <label class="flex items-center">
                                    <input type="checkbox" name="columns[]" value="id" checked class="rounded">
                                    <span class="ml-2 text-sm text-gray-700">Order ID</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="columns[]" value="order_number" checked class="rounded">
                                    <span class="ml-2 text-sm text-gray-700">Order Number</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="columns[]" value="customer_name" checked class="rounded">
                                    <span class="ml-2 text-sm text-gray-700">Customer Name</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="columns[]" value="customer_email" checked class="rounded">
                                    <span class="ml-2 text-sm text-gray-700">Customer Email</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="columns[]" value="total_amount" checked class="rounded">
                                    <span class="ml-2 text-sm text-gray-700">Total Amount</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="columns[]" value="status" checked class="rounded">
                                    <span class="ml-2 text-sm text-gray-700">Status</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="columns[]" value="item_count" class="rounded">
                                    <span class="ml-2 text-sm text-gray-700">Items Count</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="columns[]" value="created_at" class="rounded">
                                    <span class="ml-2 text-sm text-gray-700">Order Date</span>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Export Orders
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
