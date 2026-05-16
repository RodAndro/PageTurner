@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="display-5 font-weight-bold">Personal Data Portability</h1>
            <p class="text-muted">Download your personal data, order history, and reading information in compliance with GDPR regulations.</p>
        </div>
    </div>

    <!-- GDPR Notice -->
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <h4 class="alert-heading"><i class="fas fa-info-circle"></i> Your Data Rights</h4>
        <p class Under the General Data Protection Regulation (GDPR), you have the right to:</p>
        <ul class="mb-0">
            <li>Access all personal data we hold about you</li>
            <li>Export your data in machine-readable formats</li>
            <li>Request data deletion (separate process)</li>
            <li>Data portability to other services</li>
        </ul>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <!-- Data Summary Cards -->
    <div class="row mb-5">
        <div class="col-md-4 mb-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <i class="fas fa-user fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Personal Data</h5>
                    <p class="text-muted">{{ $dataSize['personal_data_kb'] }} KB</p>
                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#exportPersonalModal">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <i class="fas fa-shopping-cart fa-3x text-success mb-3"></i>
                    <h5 class="card-title">Order History</h5>
                    <p class="text-muted">{{ $dataSize['order_history_kb'] }} KB</p>
                    <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#exportOrdersModal">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <i class="fas fa-book fa-3x text-info mb-3"></i>
                    <h5 class="card-title">Reading History</h5>
                    <p class="text-muted">{{ $dataSize['reading_history_kb'] }} KB</p>
                    <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#exportReadingModal">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Export History -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-history"></i> Recent Exports</h5>
        </div>
        <div class="card-body">
            @if($exportHistory->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Format</th>
                                <th>Status</th>
                                <th>Records</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($exportHistory as $export)
                                <tr>
                                    <td>
                                        <span class="badge badge-secondary">{{ ucfirst(str_replace('_', ' ', $export->export_type)) }}</span>
                                    </td>
                                    <td>
                                        <code>{{ strtoupper($export->format) }}</code>
                                    </td>
                                    <td>
                                        @if($export->status === 'completed')
                                            <span class="badge badge-success">Completed</span>
                                        @elseif($export->status === 'processing')
                                            <span class="badge badge-warning">Processing</span>
                                        @elseif($export->status === 'failed')
                                            <span class="badge badge-danger">Failed</span>
                                        @else
                                            <span class="badge badge-secondary">{{ ucfirst($export->status) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($export->total_records ?? 0) }}</td>
                                    <td>
                                        <small class="text-muted">{{ $export->created_at->format('M d, Y H:i') }}</small>
                                        @if($export->downloaded_at)
                                            <br><small class="text-muted">Downloaded: {{ $export->downloaded_at->diffForHumans() }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($export->status === 'completed')
                                            <a href="{{ route('user.data-portability.download', $export) }}" class="btn btn-sm btn-primary" title="Download">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        @endif
                                        <form action="{{ route('user.data-portability.delete', $export) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-light" role="alert">
                    <p class="mb-0 text-muted">No exports yet. Click the export buttons above to create your first data export.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Export Personal Data Modal -->
<div class="modal fade" id="exportPersonalModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Personal Data</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('user.data-portability.export-personal') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p>This will export your personal information (name, email, profile details) in JSON format.</p>
                    <div class="alert alert-info small">
                        <strong>File Format:</strong> JSON (machine-readable)<br>
                        <strong>Estimated Size:</strong> {{ $dataSize['personal_data_kb'] }} KB
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Export Order History Modal -->
<div class="modal fade" id="exportOrdersModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Order History</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('user.data-portability.export-orders') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="format">File Format</label>
                        <select class="form-control" id="format" name="format" required>
                            <option value="">-- Select Format --</option>
                            <option value="csv">CSV (Spreadsheet)</option>
                            <option value="excel">Excel (XLSX)</option>
                            <option value="pdf">PDF (Print-friendly)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="start_date">Start Date (Optional)</label>
                        <input type="date" class="form-control" id="start_date" name="start_date">
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date (Optional)</label>
                        <input type="date" class="form-control" id="end_date" name="end_date">
                    </div>
                    <div class="alert alert-info small">
                        <strong>Estimated Size:</strong> {{ $dataSize['order_history_kb'] }} KB<br>
                        Leave dates blank to export all orders
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Export Reading History Modal -->
<div class="modal fade" id="exportReadingModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Reading History</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('user.data-portability.export-reading') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="reading_format">File Format</label>
                        <select class="form-control" id="reading_format" name="format" required>
                            <option value="">-- Select Format --</option>
                            <option value="json">JSON (Machine-readable)</option>
                            <option value="csv">CSV (Spreadsheet)</option>
                            <option value="excel">Excel (XLSX)</option>
                        </select>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="includeReviews" name="include_reviews" value="1">
                        <label class="form-check-label" for="includeReviews">
                            Include Reviews & Ratings
                        </label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="includeWishlist" name="include_wishlist" value="1">
                        <label class="form-check-label" for="includeWishlist">
                            Include Wishlist (if available)
                        </label>
                    </div>
                    <div class="alert alert-info small mt-3">
                        <strong>Estimated Size:</strong> {{ $dataSize['reading_history_kb'] }} KB<br>
                        Includes all books you've purchased and reviewed
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </form>
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
</style>
@endsection
