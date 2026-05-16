@extends('layouts.main')

@section('content')
<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Backup & Maintenance</h1>
            <p class="text-gray-600 mt-2">Manage automated backups and system maintenance tasks</p>
        </div>

        <!-- Success/Error Messages -->
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                <div class="text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        @if (session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
                <div class="text-sm text-green-700">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                <div class="text-sm text-red-700">{{ session('error') }}</div>
            </div>
        @endif

        <!-- Statistics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Total Backups</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['total_backups'] }}</p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-3">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Successful</p>
                        <p class="text-2xl font-bold text-green-600">{{ $stats['successful_backups'] }}</p>
                    </div>
                    <div class="bg-green-100 rounded-full p-3">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Failed</p>
                        <p class="text-2xl font-bold text-red-600">{{ $stats['failed_backups'] }}</p>
                    </div>
                    <div class="bg-red-100 rounded-full p-3">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Unhealthy Backups</p>
                        <p class="text-2xl font-bold text-orange-600">{{ $stats['unhealthy_backups'] }}</p>
                    </div>
                    <div class="bg-orange-100 rounded-full p-3">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 0v2m0 0v2"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h2>
            <div class="flex flex-wrap gap-3">
                <form action="{{ route('admin.backup-maintenance.trigger-backup') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        Trigger Backup Now
                    </button>
                </form>

                <a href="{{ route('admin.backup-maintenance.backups-list') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    View All Backups
                </a>

                <a href="{{ route('admin.backup-maintenance.tasks-list') }}" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    View Tasks
                </a>
            </div>
        </div>

        <!-- Recent Backups -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Recent Backups</h2>
                </div>
                <div class="divide-y">
                    @forelse ($recentBackups as $backup)
                        <div class="px-6 py-4 hover:bg-gray-50">
                            <div class="flex items-center justify-between mb-2">
                                <a href="{{ route('admin.backup-maintenance.backup-detail', $backup->id) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                    {{ $backup->backup_name ?: 'Backup #' . $backup->id }}
                                </a>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    @if ($backup->status === 'completed') bg-green-100 text-green-800
                                    @elseif ($backup->status === 'processing') bg-yellow-100 text-yellow-800
                                    @elseif ($backup->status === 'failed') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($backup->status) }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500">{{ $backup->created_at->diffForHumans() }}</p>
                            @if ($backup->file_size_mb)
                                <p class="text-xs text-gray-600 mt-1">Size: {{ $backup->formatted_file_size }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="px-6 py-4 text-gray-500 text-sm">No backups yet</div>
                    @endforelse
                </div>
            </div>

            <!-- Failed Backups -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Failed Backups</h2>
                </div>
                <div class="divide-y">
                    @forelse ($failedBackups as $backup)
                        <div class="px-6 py-4 hover:bg-gray-50 border-l-4 border-red-400">
                            <div class="flex items-center justify-between mb-2">
                                <a href="{{ route('admin.backup-maintenance.backup-detail', $backup->id) }}" class="text-sm font-medium text-red-600 hover:text-red-800">
                                    Backup #{{ $backup->id }}
                                </a>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                            </div>
                            <p class="text-xs text-gray-600">{{ $backup->error_message ?: 'No error message' }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $backup->created_at->diffForHumans() }}</p>
                        </div>
                    @empty
                        <div class="px-6 py-4 text-gray-500 text-sm">No failed backups</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Health Checks -->
        @if ($healthChecks->count() > 0)
            <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Backup Health Checks</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Backup Name</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Status</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Age (Days)</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Storage</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Last Checked</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($healthChecks as $check)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-gray-900">{{ $check->backup_name }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                                            @if ($check->isHealthy()) bg-green-100 text-green-800
                                            @else bg-red-100 text-red-800
                                            @endif">
                                            {{ ucfirst($check->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">{{ $check->age_days ?? '-' }} days</td>
                                    <td class="px-6 py-4 text-gray-600">{{ $check->formatted_storage ?? '-' }}</td>
                                    <td class="px-6 py-4 text-gray-600 text-xs">{{ $check->last_checked_at?->diffForHumans() ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Recent Maintenance Tasks -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Recent Maintenance Tasks</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Task</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Duration</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Type</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Executed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($maintenanceTasks as $task)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <a href="{{ route('admin.backup-maintenance.task-detail', $task->id) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                        {{ $task->task_name }}
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                        @if ($task->status === 'completed') bg-green-100 text-green-800
                                        @elseif ($task->status === 'running') bg-yellow-100 text-yellow-800
                                        @elseif ($task->status === 'failed') bg-red-100 text-red-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst($task->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ $task->formatted_duration ?? '-' }}</td>
                                <td class="px-6 py-4 text-gray-600">
                                    <span class="text-xs">{{ $task->was_manual ? 'Manual' : 'Scheduled' }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-600 text-xs">{{ $task->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-gray-500 text-center">No maintenance tasks yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
