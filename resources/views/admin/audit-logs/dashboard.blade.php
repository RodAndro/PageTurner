@extends('layouts.admin')

@section('title', 'Audit Log Dashboard')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Audit Log Dashboard</h1>
        <div class="btn-group">
            <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-list"></i> View All Logs
            </a>
            <form action="{{ route('admin.audit-logs.verify-integrity') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="bi bi-shield-check"></i> Verify Integrity
                </button>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Total Logs</h5>
                    <p class="card-text display-4">{{ $stats['total_logs'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <h5 class="card-title">Critical Events</h5>
                    <p class="card-text display-4">{{ $stats['critical_events'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Sensitive Operations</h5>
                    <p class="card-text display-4">{{ $stats['sensitive_operations'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">Pending Alerts</h5>
                    <p class="card-text display-4">{{ $stats['pending_alerts'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Event Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="eventChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">User Activity</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @foreach($userActivity as $activity)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span>{{ $activity['user'] }}</span>
                                <span class="badge badge-primary badge-pill">{{ $activity['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Logs -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recent Audit Logs</h5>
            <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Event</th>
                        <th>Level</th>
                        <th>IP Address</th>
                        <th>Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentLogs as $log)
                        <tr>
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
                            <td><code>{{ $log->metadata['ip_address'] ?? '-' }}</code></td>
                            <td><small>{{ $log->created_at->diffForHumans() }}</small></td>
                            <td>
                                <a href="{{ route('admin.audit-logs.show', $log->id) }}" class="btn btn-sm btn-info">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No recent logs</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Event Distribution Chart
    const ctx = document.getElementById('eventChart').getContext('2d');
    const eventData = @json($eventCounts);
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(eventData),
            datasets: [{
                data: Object.values(eventData),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 159, 64, 0.8)',
                    'rgba(199, 199, 199, 0.8)',
                    'rgba(83, 102, 255, 0.8)',
                    'rgba(255, 99, 255, 0.8)',
                    'rgba(99, 255, 132, 0.8)',
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
</script>
@endpush
@endsection
