<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Status and Navigation -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h2 class="text-2xl font-bold mb-4">Import/Export Management</h2>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <a href="{{ route('admin.import-export.books-import') }}" class="block p-4 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 text-center">
                            <div class="text-sm font-bold text-blue-900">Import Books</div>
                        </a>
                        <a href="{{ route('admin.import-export.books-export') }}" class="block p-4 bg-cyan-50 border border-cyan-200 rounded-lg hover:bg-cyan-100 text-center">
                            <div class="text-sm font-bold text-cyan-900">Export Books</div>
                        </a>
                        <a href="{{ route('admin.import-export.orders-export') }}" class="block p-4 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 text-center">
                            <div class="text-sm font-bold text-green-900">Export Orders</div>
                        </a>
                        <a href="{{ route('admin.import-export.users-import') }}" class="block p-4 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 text-center">
                            <div class="text-sm font-bold text-purple-900">Import Users</div>
                        </a>
                        <form action="{{ route('admin.import-export.users-export-store') }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="format" value="xlsx">
                            <button type="submit" class="block w-full p-4 bg-orange-50 border border-orange-200 rounded-lg hover:bg-orange-100 text-center">
                                <div class="text-sm font-bold text-orange-900">Export Users</div>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Import Logs -->
            @if ($imports->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm rounded-lg mb-6">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-xl font-bold mb-4">Recent Imports</h3>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-4 py-2 text-left">Module</th>
                                        <th class="px-4 py-2 text-left">File Name</th>
                                        <th class="px-4 py-2 text-left">Status</th>
                                        <th class="px-4 py-2 text-center">Success</th>
                                        <th class="px-4 py-2 text-center">Failed</th>
                                        <th class="px-4 py-2 text-left">Date</th>
                                        <th class="px-4 py-2 text-left">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($imports as $import)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2 font-medium">{{ ucfirst($import->module_type) }}</td>
                                            <td class="px-4 py-2">{{ $import->file_name }}</td>
                                            <td class="px-4 py-2">
                                                <span class="px-2 py-1 rounded text-xs font-bold
                                                    @if ($import->status === 'completed') bg-green-100 text-green-800
                                                    @elseif ($import->status === 'processing') bg-blue-100 text-blue-800
                                                    @elseif ($import->status === 'failed') bg-red-100 text-red-800
                                                    @else bg-yellow-100 text-yellow-800
                                                    @endif
                                                ">
                                                    {{ ucfirst($import->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-center">{{ $import->successful_rows }}</td>
                                            <td class="px-4 py-2 text-center">{{ $import->failed_rows }}</td>
                                            <td class="px-4 py-2">{{ $import->created_at->format('M d, Y H:i') }}</td>
                                            <td class="px-4 py-2">
                                                @if ($import->failure_report && $import->failed_rows > 0)
                                                    <details class="text-xs text-blue-600 cursor-pointer">
                                                        <summary>View Errors</summary>
                                                        <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded max-h-40 overflow-y-auto">
                                                            @if (is_array($import->failure_report))
                                                                @foreach ($import->failure_report as $failure)
                                                                    <div class="text-xs mb-1">
                                                                        <strong>Row {{ $failure['row'] ?? 'N/A' }}:</strong>
                                                                        {{ $failure['error'] ?? 'Unknown error' }}
                                                                    </div>
                                                                @endforeach
                                                            @else
                                                                <pre class="text-xs overflow-x-auto">{{ $import->failure_report }}</pre>
                                                            @endif
                                                        </div>
                                                    </details>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Export Logs -->
            @if ($exports->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-xl font-bold mb-4">Recent Exports</h3>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-4 py-2 text-left">Module</th>
                                        <th class="px-4 py-2 text-left">Format</th>
                                        <th class="px-4 py-2 text-left">Status</th>
                                        <th class="px-4 py-2 text-center">Records</th>
                                        <th class="px-4 py-2 text-left">Date</th>
                                        <th class="px-4 py-2 text-left">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($exports as $export)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2 font-medium">{{ ucfirst($export->module_type) }}</td>
                                            <td class="px-4 py-2">{{ strtoupper($export->export_format) }}</td>
                                            <td class="px-4 py-2">
                                                <span class="px-2 py-1 rounded text-xs font-bold
                                                    @if ($export->status === 'completed') bg-green-100 text-green-800
                                                    @elseif ($export->status === 'processing') bg-blue-100 text-blue-800
                                                    @elseif ($export->status === 'failed') bg-red-100 text-red-800
                                                    @else bg-yellow-100 text-yellow-800
                                                    @endif
                                                ">
                                                    {{ ucfirst($export->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-center">{{ $export->total_records }}</td>
                                            <td class="px-4 py-2">{{ $export->created_at->format('M d, Y H:i') }}</td>
                                            <td class="px-4 py-2">
                                                @if ($export->status === 'completed')
                                                    <a href="{{ route('admin.import-export.download-export', $export->id) }}" class="text-blue-600 hover:underline text-xs">
                                                        Download
                                                    </a>
                                                @elseif ($export->status === 'failed')
                                                    <span class="text-red-600 text-xs">{{ $export->error_message }}</span>
                                                @else
                                                    <span class="text-gray-500 text-xs">Processing...</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
