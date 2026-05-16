@extends('layouts.app')

@section('content')
<div class="container-fluid py-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="display-5 font-weight-bold">Advanced Admin Dashboard</h1>
            <p class="text-muted">System Health, Data Management & Operations Overview</p>
        </div>
    </div>

    <!-- System Health Status -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">System Health Status</h5>
                    @php
                        $healthColors = [
                            'healthy' => 'success',
                            'warning' => 'warning',
                            'critical' => 'danger',
                        ];
                        $color = $healthColors[$overall_health] ?? 'secondary';
                    @endphp
                    <div class="alert alert-{{ $color }} d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Overall Status: {{ ucfirst($overall_health) }}</strong>
                            <p class="mb-0 small">Last updated: {{ $timestamp->format('H:i:s') }}</p>
                        </div>
                        <div class="badge badge-{{ $color }} p-3">{{ strtoupper($overall_health[0]) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Import/Export Widget -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-exchange-alt"></i> Import/Export Status</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Imports Today</strong>
                            <p class="h4 text-primary">{{ $import_export['import_stats']['total_operations'] }}</p>
                            <small class="text-muted">Success Rate: {{ $import_export['import_stats']['success_rate'] }}%</small>
                        </div>
                        <div class="col-md-6">
                            <strong>Exports Today</strong>
                            <p class="h4 text-info">{{ $import_export['export_stats']['total_operations'] }}</p>
                            <small class="text-muted">Completed: {{ $import_export['export_stats']['completed_today'] }}</small>
                        </div>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <strong>Recent Operations</strong>
                        <div class="list-group list-group-sm mt-2">
                            @forelse($import_export['recent_imports']->take(3) as $import)
                                <a href="#" class="list-group-item list-group-item-action small">
                                    <div class="d-flex justify-content-between">
                                        <strong>{{ $import->filename }}</strong>
                                        <span class="badge badge-{{ $import->status === 'completed' ? 'success' : 'warning' }}">
                                            {{ ucfirst($import->status) }}
                                        </span>
                                    </div>
                                    <small class="text-muted">{{ $import->successful_rows }}/{{ $import->total_rows }} rows • {{ $import->created_at->diffForHumans() }}</small>
                                </a>
                            @empty
                                <p class="text-muted small">No recent imports</p>
                            @endforelse
                        </div>
                    </div>
                    <div class="alert alert-warning small mb-0" role="alert">
                        <strong>Alert:</strong>
                        @if($import_export['import_stats']['failed_today'] > 0)
                            {{ $import_export['import_stats']['failed_today'] }} import(s) failed today
                        @elseif($import_export['import_stats']['in_progress'] > 0)
                            {{ $import_export['import_stats']['in_progress'] }} import(s) currently processing
                        @else
                            All imports completed successfully
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Backup Status Widget -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-database"></i> Backup Status</h5>
                </div>
                <div class="card-body">
                    @if($backup['latest_backup'])
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Latest Backup</strong>
                                <p class="h6">{{ $backup['latest_backup']->backup_name }}</p>
                                <small class="text-muted">{{ $backup['latest_backup']->backup_completed_at?->diffForHumans() }}</small>
                            </div>
                            <div class="col-md-6">
                                <strong>Health Status</strong>
                                <p class="h6">{{ ucfirst($backup['health_status']) }}</p>
                                <small class="text-muted">{{ $backup['backup_stats']['verified_backups'] }} of {{ $backup['backup_stats']['total_backups'] }} verified</small>
                            </div>
                        </div>
                    @endif
                    <hr>
                    <div class="row text-center mb-3">
                        <div class="col-md-6">
                            <strong>{{ $backup['backup_stats']['total_backups'] }}</strong>
                            <p class="small text-muted mb-0">Total Backups</p>
                        </div>
                        <div class="col-md-6">
                            <strong>{{ number_format($backup['storage_usage_mb'], 2) }} MB</strong>
                            <p class="small text-muted mb-0">Storage Used</p>
                        </div>
                    </div>
                    <div class="progress mb-3" style="height: 20px;">
                        @php
                            $verifiedPercent = $backup['backup_stats']['total_backups'] > 0 
                                ? ($backup['backup_stats']['verified_backups'] / $backup['backup_stats']['total_backups']) * 100 
                                : 0;
                        @endphp
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $verifiedPercent }}%" aria-valuenow="{{ $verifiedPercent }}" aria-valuemin="0" aria-valuemax="100">
                            {{ round($verifiedPercent) }}% Verified
                        </div>
                    </div>
                    @if($backup['backup_stats']['failed_backups'] > 0 || $backup['backup_stats']['corrupted_backups'] > 0)
                        <div class="alert alert-danger small mb-0" role="alert">
                            <strong>⚠️ Issues Detected:</strong>
                            @if($backup['backup_stats']['failed_backups'] > 0)
                                {{ $backup['backup_stats']['failed_backups'] }} failed backup(s)
                            @endif
                            @if($backup['backup_stats']['corrupted_backups'] > 0)
                                {{ $backup['backup_stats']['corrupted_backups'] }} corrupted backup(s)
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Audit Log Summary Widget -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-shield-alt"></i> Security Alerts</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>{{ $audit_logs['audit_stats']['critical_events_today'] }}</strong>
                        <p class="small text-muted mb-0">Critical Events Today</p>
                    </div>
                    <div class="mb-3">
                        <strong>{{ $audit_logs['audit_stats']['security_alerts_today'] }}</strong>
                        <p class="small text-muted mb-0">Security Alerts</p>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <strong>Recent Alerts</strong>
                        <div class="list-group list-group-sm mt-2">
                            @forelse($audit_logs['security_alerts']->take(5) as $alert)
                                <div class="list-group-item list-group-item-action small p-2">
                                    <div class="d-flex justify-content-between">
                                        <strong>{{ ucfirst(str_replace('_', ' ', $alert->action)) }}</strong>
                                        <span class="badge badge-danger">Alert</span>
                                    </div>
                                    <small class="text-muted">User: {{ $alert->user?->email ?? 'Unknown' }} • {{ $alert->created_at->diffForHumans() }}</small>
                                </div>
                            @empty
                                <p class="text-muted small">No security alerts</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Usage Widget -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> API Usage</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Requests Today</strong>
                        <p class="h5">{{ number_format($api_usage['api_stats']['total_requests_today']) }}</p>
                    </div>
                    <div class="mb-3">
                        <strong>Error Rate</strong>
                        <p class="h5">{{ $api_usage['api_stats']['error_rate'] }}%</p>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <strong>Throttled Users</strong>
                        <p class="h6">{{ $api_usage['api_stats']['throttled_today'] }}</p>
                    </div>
                    <div class="mb-3">
                        <strong>Blocked IPs</strong>
                        <p class="h6">{{ $api_usage['api_stats']['blocked_ips'] }}</p>
                    </div>
                    @if($api_usage['api_stats']['throttled_today'] > 0)
                        <div class="alert alert-warning small" role="alert">
                            <strong>Alert:</strong> {{ $api_usage['api_stats']['throttled_today'] }} user(s) throttled today
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Task Health Widget -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-tasks"></i> Scheduled Tasks</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>{{ $system_health['task_health']['enabled'] }}</strong>
                            <p class="small text-muted mb-0">Enabled Tasks</p>
                        </div>
                        <div class="col-md-6">
                            <strong>{{ $system_health['task_health']['disabled'] }}</strong>
                            <p class="small text-muted mb-0">Disabled Tasks</p>
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>{{ $system_health['task_health']['failures_24h'] }}</strong>
                            <p class="small text-muted mb-0">Failures (24h)</p>
                        </div>
                        <div class="col-md-6">
                            <strong class="text-danger">{{ $system_health['task_health']['critical_tasks_failed'] }}</strong>
                            <p class="small text-muted mb-0">Critical Failed</p>
                        </div>
                    </div>
                    @if($system_health['task_health']['critical_tasks_failed'] > 0)
                        <div class="alert alert-danger small" role="alert">
                            <strong>⚠️ Critical:</strong> {{ $system_health['task_health']['critical_tasks_failed'] }} critical task(s) have consecutive failures
                        </div>
                    @elseif($system_health['task_health']['failures_24h'] > 0)
                        <div class="alert alert-warning small" role="alert">
                            <strong>Alert:</strong> {{ $system_health['task_health']['failures_24h'] }} task(s) failed in last 24 hours
                        </div>
                    @else
                        <div class="alert alert-success small" role="alert">
                            <strong>✓</strong> All scheduled tasks operating normally
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Database Health -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-database"></i> Database Health</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Tables</strong>
                            <p class="h5">{{ $system_health['db_health']['table_count'] }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Estimated Size</strong>
                            <p class="h5">{{ number_format($system_health['db_health']['estimated_size_mb'], 2) }} MB</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <strong>Latest Backup</strong>
                        <p class="small">Status: <span class="badge badge-{{ $system_health['db_health']['backup_status'] === 'verified' ? 'success' : 'warning' }}">{{ ucfirst($system_health['db_health']['backup_status']) }}</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Queue Health -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-hourglass"></i> Queue Health</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Pending Jobs</strong>
                            <p class="h5">{{ $system_health['queue_health']['pending_jobs'] }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Failed Jobs</strong>
                            <p class="h5 text-danger">{{ $system_health['queue_health']['failed_jobs'] }}</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <strong>Latency</strong>
                        <p class="small">{{ $system_health['queue_health']['queue_latency_ms'] }}ms</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    .badge {
        font-size: 0.75rem;
    }
</style>
@endsection
