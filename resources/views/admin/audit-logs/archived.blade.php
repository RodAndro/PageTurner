@extends('layouts.admin')

@section('title', 'Archived Audit Logs')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Archived Audit Logs</h1>
        <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Active Logs
        </a>
    </div>

    <!-- Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.audit-logs.archived') }}" class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Search archived logs..." value="{{ request('search') }}">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-search"></i> Search
                </button>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Event</th>
                        <th>Level</th>
                        <th>Original Timestamp</th>
                        <th>Archived On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td><small>#{{ $log->id }}</small></td>
                            <td>
                                @if($log->user)
                                    <span class="badge badge-info">{{ $log->user->email }}</span>
                                @else
                                    <span class="badge badge-secondary">System</span>
                                @endif
                            </td>
                            <td><code>{{ $log->event }}</code></td>
                            <td>
                                @if($log->level === 'critical')
                                    <span class="badge badge-danger">{{ ucfirst($log->level) }}</span>
                                @elseif($log->level === 'warning')
                                    <span class="badge badge-warning">{{ ucfirst($log->level) }}</span>
                                @else
                                    <span class="badge badge-info">{{ ucfirst($log->level) }}</span>
                                @endif
                            </td>
                            <td><small>{{ $log->created_at->format('M d, Y H:i') }}</small></td>
                            <td><small>{{ $log->archived_at->format('M d, Y H:i') }}</small></td>
                            <td>
                                <a href="{{ route('admin.audit-logs.show', $log->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No archived logs found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-4">
        {{ $logs->links() }}
    </div>
</div>
@endsection
