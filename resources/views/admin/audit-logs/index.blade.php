@extends('layouts.admin')

@section('title', 'Audit Logs')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Audit Logs</h1>
        <div class="btn-group">
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
                <i class="bi bi-download"></i> Export
            </button>
            <a href="{{ route('admin.audit-logs.archived') }}" class="btn btn-outline-secondary">
                <i class="bi bi-archive"></i> Archived Logs
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="row g-3">
                <div class="col-md-2">
                    <label for="userFilter" class="form-label">User</label>
                    <select class="form-select" id="userFilter" name="user_id">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->email }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="eventFilter" class="form-label">Event</label>
                    <select class="form-select" id="eventFilter" name="event">
                        <option value="">All Events</option>
                        @foreach($events as $event)
                            <option value="{{ $event }}" {{ request('event') == $event ? 'selected' : '' }}>
                                {{ $event }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="levelFilter" class="form-label">Level</label>
                    <select class="form-select" id="levelFilter" name="level">
                        <option value="">All Levels</option>
                        @foreach($levels as $level)
                            <option value="{{ $level }}" {{ request('level') == $level ? 'selected' : '' }}>
                                {{ ucfirst($level) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="startDate" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="startDate" name="start_date" value="{{ request('start_date') }}">
                </div>

                <div class="col-md-2">
                    <label for="endDate" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="endDate" name="end_date" value="{{ request('end_date') }}">
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100" style="margin-top: 32px;">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </form>

            <div class="mt-3">
                <label for="search" class="form-label">Search Description</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="search" name="search" placeholder="Search descriptions..." value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="button" id="searchBtn">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>

            @if($savedSearches->count() > 0)
                <div class="mt-3">
                    <label class="form-label">Saved Searches</label>
                    <div class="btn-group" role="group">
                        @foreach($savedSearches as $search)
                            <form action="{{ route('admin.audit-logs.load-search', $search->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-info">
                                    {{ $search->name }}
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            @endif
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
                        <th>Model</th>
                        <th>Level</th>
                        <th>IP Address</th>
                        <th>Time</th>
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
                                @if($log->auditable_type)
                                    <small>{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</small>
                                @else
                                    <small class="text-muted">-</small>
                                @endif
                            </td>
                            <td>
                                @if($log->level === 'critical')
                                    <span class="badge badge-danger">{{ ucfirst($log->level) }}</span>
                                @elseif($log->level === 'warning')
                                    <span class="badge badge-warning">{{ ucfirst($log->level) }}</span>
                                @else
                                    <span class="badge badge-info">{{ ucfirst($log->level) }}</span>
                                @endif
                            </td>
                            <td><code>{{ $log->metadata['ip_address'] ?? '-' }}</code></td>
                            <td><small>{{ $log->created_at->format('M d, Y H:i') }}</small></td>
                            <td>
                                <a href="{{ route('admin.audit-logs.show', $log->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No audit logs found</td>
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

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Audit Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.audit-logs.export') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="format" class="form-label">Format</label>
                        <select class="form-select" id="format" name="format" required>
                            <option value="csv">CSV</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                    <p class="text-muted"><small>Current filters will be applied to the export.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Export</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('searchBtn').addEventListener('click', function() {
        const form = document.querySelector('form');
        form.submit();
    });
</script>
@endpush
@endsection
