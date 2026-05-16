@extends('layouts.main')

@section('content')
<div class="min-h-screen bg-gray-100">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 md:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Backup Details</h1>
                    <p class="text-gray-600 mt-2">#{{ $backup->id }}</p>
                </div>
                <a href="{{ route('admin.backup-maintenance.backups-list') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition">
                    Back to Backups
                </a>
            </div>
        </div>

        <!-- Backup Information -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left Column -->
                <div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Backup Name</label>
                        <p class="text-gray-900">{{ $backup->backup_name ?: 'N/A' }}</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <span class="px-3 py-1 text-sm font-semibold rounded-full
                            @if ($backup->status === 'completed') bg-green-100 text-green-800
                            @elseif ($backup->status === 'processing') bg-yellow-100 text-yellow-800
                            @elseif ($backup->status === 'failed') bg-red-100 text-red-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst($backup->status) }}
                        </span>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <p class="text-gray-900">{{ ucfirst($backup->type) }}</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">File Size</label>
                        <p class="text-gray-900">{{ $backup->formatted_file_size ?? 'Unknown' }}</p>
                    </div>
                </div>

                <!-- Right Column -->
                <div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Duration</label>
                        <p class="text-gray-900">{{ $backup->formatted_duration ?? 'N/A' }}</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Started At</label>
                        <p class="text-gray-900">{{ $backup->started_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Completed At</label>
                        <p class="text-gray-900">{{ $backup->completed_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Total Rows Backed Up</label>
                        <p class="text-gray-900">{{ $backup->total_rows ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Details (if failed) -->
        @if ($backup->status === 'failed' && $backup->error_message)
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-red-900 mb-3">Error Details</h3>
                <p class="text-red-700 font-mono text-sm whitespace-pre-wrap break-words">{{ $backup->error_message }}</p>
            </div>
        @endif

        <!-- Additional Details -->
        @if ($backup->details)
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Additional Details</h3>
                <div class="bg-gray-50 p-4 rounded-md font-mono text-sm overflow-auto max-h-64">
                    <pre>{{ json_encode($backup->details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        @endif

        <!-- Actions -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
            <div class="flex flex-wrap gap-3">
                @if ($backup->status === 'completed' && $backup->backup_name)
                    <a href="{{ route('admin.backup-maintenance.download-backup', $backup->id) }}" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Download Backup
                    </a>
                @endif

                <form action="{{ route('admin.backup-maintenance.delete-backup', $backup->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this backup?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Delete Backup
                    </button>
                </form>

                <a href="{{ route('admin.backup-maintenance.backups-list') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition">
                    Back to List
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
