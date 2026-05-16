@extends('layouts.main')

@section('content')
<div class="min-h-screen bg-gray-100">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 md:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Task Details</h1>
                    <p class="text-gray-600 mt-2">{{ $task->task_name }}</p>
                </div>
                <a href="{{ route('admin.backup-maintenance.tasks-list') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition">
                    Back to Tasks
                </a>
            </div>
        </div>

        <!-- Task Information -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left Column -->
                <div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Task Name</label>
                        <p class="text-gray-900 font-mono">{{ $task->task_name }}</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <span class="px-3 py-1 text-sm font-semibold rounded-full
                            @if ($task->status === 'completed') bg-green-100 text-green-800
                            @elseif ($task->status === 'running') bg-yellow-100 text-yellow-800
                            @elseif ($task->status === 'failed') bg-red-100 text-red-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst($task->status) }}
                        </span>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <span class="px-3 py-1 text-sm font-semibold rounded-full
                            @if ($task->was_manual) bg-blue-100 text-blue-800
                            @else bg-purple-100 text-purple-800
                            @endif">
                            {{ $task->was_manual ? 'Manual' : 'Scheduled' }}
                        </span>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Duration</label>
                        <p class="text-gray-900">{{ $task->formatted_duration ?? 'N/A' }}</p>
                    </div>
                </div>

                <!-- Right Column -->
                <div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Command</label>
                        <p class="text-gray-900 font-mono text-sm">{{ $task->command ?? 'N/A' }}</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Started At</label>
                        <p class="text-gray-900">{{ $task->started_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Completed At</label>
                        <p class="text-gray-900">{{ $task->completed_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Executed</label>
                        <p class="text-gray-900">{{ $task->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Details (if failed) -->
        @if ($task->status === 'failed' && $task->error_message)
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-red-900 mb-3">Error Message</h3>
                <p class="text-red-700 font-mono text-sm whitespace-pre-wrap break-words">{{ $task->error_message }}</p>
            </div>
        @endif

        <!-- Task Output -->
        @if ($task->output)
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Output</h3>
                <div class="bg-gray-900 text-gray-100 p-4 rounded-md font-mono text-sm overflow-auto max-h-96">
                    <pre>{{ $task->output }}</pre>
                </div>
            </div>
        @endif

        <!-- Actions -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
            <div class="flex flex-wrap gap-3">
                @if ($task->status !== 'running')
                    <form action="{{ route('admin.backup-maintenance.run-task', str_replace(':', '-', $task->task_name)) }}" method="POST" style="display: inline;" onsubmit="return confirm('Run this task now?');">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Run Now
                        </button>
                    </form>
                @else
                    <button disabled class="px-4 py-2 bg-gray-400 text-white rounded-md cursor-not-allowed">
                        <svg class="w-5 h-5 inline mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Running...
                    </button>
                @endif

                <a href="{{ route('admin.backup-maintenance.tasks-list') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition">
                    Back to Tasks
                </a>
            </div>
        </div>

        <!-- Task Information Card -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h4 class="font-semibold text-blue-900 mb-2">About This Task</h4>
            <p class="text-sm text-blue-800 mb-2">
                Task: <span class="font-mono">{{ $task->task_name }}</span>
            </p>
            <div class="text-sm text-blue-700">
                @switch ($task->task_name)
                    @case ('backup:run')
                        <p>Creates a full backup of database and application files, encrypted and stored locally and in S3.</p>
                    @break
                    @case ('backup:clean')
                        <p>Removes old backup files according to retention policy (7/14/4/12/2 days/weeks/months).</p>
                    @break
                    @case ('order:cleanup-pending')
                        <p>Cancels pending orders that haven't been completed within 24 hours.</p>
                    @break
                    @case ('session:cleanup')
                        <p>Removes expired session records from the database based on configured session lifetime.</p>
                    @break
                    @case ('log:rotate')
                        <p>Archives and compresses log files older than 30 days to reduce storage usage.</p>
                    @break
                    @case ('report:generate-daily')
                        <p>Generates daily sales report with order count, revenue, and average order value.</p>
                    @break
                    @case ('notification:prune')
                        <p>Deletes notification records older than 90 days from the database.</p>
                    @break
                    @case ('audit:archive')
                        <p>Archives audit logs older than 12 months to separate archive storage.</p>
                    @break
                    @default
                        <p>Scheduled maintenance task for system management.</p>
                @endswitch
            </div>
        </div>
    </div>
</div>
@endsection
