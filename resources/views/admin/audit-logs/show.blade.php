@extends('layouts.admin')

@section('title', 'Audit Log Details')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Audit Log #{{ $log->id }}</h1>
        <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="row">
        <!-- Main Details -->
        <div class="col-md-8">
            <!-- Basic Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">UUID</label>
                            <p><code>{{ $log->uuid }}</code></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Event</label>
                            <p><code>{{ $log->event_label }}</code></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">User</label>
                            <p>
                                @if($log->user)
                                    <a href="{{ route('admin.users.show', $log->user->id) }}">
                                        {{ $log->user->email }}
                                    </a>
                                @else
                                    <span class="badge badge-secondary">System</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Level</label>
                            <p>
                                @if($log->level === 'critical')
                                    <span class="badge badge-danger">{{ ucfirst($log->level) }}</span>
                                @elseif($log->level === 'warning')
                                    <span class="badge badge-warning">{{ ucfirst($log->level) }}</span>
                                @else
                                    <span class="badge badge-info">{{ ucfirst($log->level) }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Timestamp</label>
                            <p>{{ $log->created_at->format('Y-m-d H:i:s') }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Sensitive</label>
                            <p>
                                @if($log->is_sensitive)
                                    <span class="badge badge-danger">Yes</span>
                                @else
                                    <span class="badge badge-success">No</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            @if($log->description)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Description</h5>
                    </div>
                    <div class="card-body">
                        <p>{{ $log->description }}</p>
                    </div>
                </div>
            @endif

            <!-- Model Information -->
            @if($log->auditable_type && $log->auditable_id)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Model Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted">Type</label>
                                <p><code>{{ class_basename($log->auditable_type) }}</code></p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">ID</label>
                                <p>#{{ $log->auditable_id }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Changes -->
            @if($log->old_values || $log->new_values)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Changes</h5>
                    </div>
                    <div class="card-body">
                        @if($log->old_values)
                            <div class="mb-3">
                                <label class="form-label text-muted">Old Values</label>
                                <div class="bg-light p-3 rounded">
                                    <pre><code>{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                </div>
                            </div>
                        @endif

                        @if($log->new_values)
                            <div class="mb-3">
                                <label class="form-label text-muted">New Values</label>
                                <div class="bg-light p-3 rounded">
                                    <pre><code>{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Metadata -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Request Metadata</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td class="text-muted">IP Address:</td>
                                <td><code>{{ $log->metadata['ip_address'] ?? '-' }}</code></td>
                            </tr>
                            <tr>
                                <td class="text-muted">URL:</td>
                                <td><code>{{ $log->metadata['url'] ?? '-' }}</code></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Method:</td>
                                <td><code>{{ $log->metadata['method'] ?? '-' }}</code></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Referer:</td>
                                <td><code>{{ $log->metadata['referer'] ?? '-' }}</code></td>
                            </tr>
                            <tr>
                                <td class="text-muted">User Agent:</td>
                                <td><small>{{ $log->metadata['user_agent'] ?? '-' }}</small></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Integrity Check -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Integrity Check</h5>
                </div>
                <div class="card-body">
                    @if($log->verifyChecksum())
                        <div class="alert alert-success mb-0">
                            <i class="bi bi-check-circle"></i> <strong>Valid:</strong> Checksum verified. This log has not been tampered with.
                        </div>
                    @else
                        <div class="alert alert-danger mb-0">
                            <i class="bi bi-exclamation-triangle"></i> <strong>Invalid:</strong> Checksum mismatch. This log may have been tampered with!
                        </div>
                    @endif
                    <div class="mt-3">
                        <label class="form-label text-muted">Checksum</label>
                        <div class="bg-light p-2 rounded">
                            <code style="word-break: break-all; font-size: 0.8rem;">{{ $log->checksum }}</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Status Card -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Status</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">Archived</small>
                        <p>{{ $log->archived_at ? $log->archived_at->format('M d, Y') : 'Not archived' }}</p>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            @if($log->alerts->count() > 0)
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Alerts</h5>
                    </div>
                    <div class="card-body">
                        @foreach($log->alerts as $alert)
                            <div class="mb-2 p-2 border-left border-warning bg-light">
                                <small class="d-block"><strong>{{ ucfirst($alert->alert_type) }}</strong></small>
                                <small class="text-muted">{{ $alert->recipients ? implode(', ', $alert->recipients) : '-' }}</small>
                                <small class="d-block mt-1">
                                    Status: <span class="badge {{ $alert->status === 'sent' ? 'bg-success' : 'bg-warning' }}">{{ ucfirst($alert->status) }}</span>
                                </small>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Related Logs -->
            @if($log->auditable_type && $log->auditable_id)
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Related Logs</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        @php
                            $relatedLogs = \App\Models\AuditLog::where('auditable_type', $log->auditable_type)
                                ->where('auditable_id', $log->auditable_id)
                                ->where('id', '!=', $log->id)
                                ->latest()
                                ->limit(5)
                                ->get();
                        @endphp

                        @forelse($relatedLogs as $relatedLog)
                            <a href="{{ route('admin.audit-logs.show', $relatedLog->id) }}" class="list-group-item list-group-item-action">
                                <small class="d-block"><strong>{{ $relatedLog->event_label }}</strong></small>
                                <small class="text-muted">{{ $relatedLog->created_at->diffForHumans() }}</small>
                            </a>
                        @empty
                            <div class="list-group-item">
                                <small class="text-muted">No related logs</small>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
