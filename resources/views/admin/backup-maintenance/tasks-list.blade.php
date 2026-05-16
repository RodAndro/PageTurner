@extends('layouts.main')

@section('content')
<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Maintenance Tasks</h1>
                    <p class="text-gray-600 mt-2">View and manage scheduled maintenance tasks</p>
                </div>
                <a href="{{ route('admin.backup-maintenance.dashboard') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition">
                    Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <form method="GET" class="flex flex-wrap gap-4">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" class="px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="">All Statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="task" class="block text-sm font-medium text-gray-700 mb-1">Task</label>
                    <select name="task" id="task" class="px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="">All Tasks</option>
                        @foreach ($taskNames as $taskName)
                            <option value="{{ $taskName }}" @selected(request('task') === $taskName)>{{ $taskName }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition text-sm">
                        Filter
                    </button>
                    <a href="{{ route('admin.backup-maintenance.tasks-list') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition text-sm">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Messages -->
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

        <!-- Tasks Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Task Name</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Duration</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Type</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Last Run</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($tasks as $task)
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
                                    <span class="text-xs px-2 py-1 rounded-full
                                        @if ($task->was_manual) bg-blue-100 text-blue-800
                                        @else bg-purple-100 text-purple-800
                                        @endif">
                                        {{ $task->was_manual ? 'Manual' : 'Scheduled' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-600 text-xs">{{ $task->completed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <a href="{{ route('admin.backup-maintenance.task-detail', $task->id) }}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                            View
                                        </a>
                                        @if ($task->status !== 'running')
                                            <form action="{{ route('admin.backup-maintenance.run-task', str_replace(':', '-', $task->task_name)) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-800 text-xs font-medium" onclick="return confirm('Run this task now?');">
                                                    Run Now
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-gray-500 text-center">No maintenance tasks found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($tasks->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $tasks->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
