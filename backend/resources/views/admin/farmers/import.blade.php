@extends('layouts.app')

@section('title', 'Import Farmers from RSBSA')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="{{ route('admin.farmers.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Farmers
    </a>
    <a href="{{ route('admin.farmers.import.diagnostics') }}" target="_blank" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-speedometer2"></i> Server Diagnostics
    </a>
</div>

@if(session('error'))
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
    </div>
@endif

<div class="card-custom">
    <div class="card-title">
        <i class="bi bi-upload"></i> Bulk Import Farmers (RSBSA CSV)
    </div>

    @if(!isset($rows))
        {{-- ════════ UPLOAD STAGE ════════ --}}
        <p class="text-muted mb-3">
            Upload your RSBSA export. One row per parcel — the system groups parcels by farmer automatically.
        </p>

        @php
            $uploadMax = ini_get('upload_max_filesize');
            $postMax   = ini_get('post_max_size');
        @endphp

        <div class="alert alert-info small py-2 mb-3">
            <i class="bi bi-info-circle"></i>
            Server upload limit: <strong>{{ $uploadMax }}</strong> &nbsp;·&nbsp;
            POST limit: <strong>{{ $postMax }}</strong>.
            For files bigger than this, ask your host to raise the limits or split the CSV into smaller chunks.
        </div>

        <form action="{{ route('admin.farmers.import.preview') }}" method="POST"
              enctype="multipart/form-data" id="importForm">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">RSBSA CSV File</label>
                <input type="file" name="csv" id="csvFile" class="form-control" accept=".csv,.txt" required>
                <small class="text-muted d-block mt-2">
                    <strong>Required columns:</strong> <code>first_name, last_name, barangay</code><br>
                    <strong>Required (at least one):</strong> <code>phone</code> <em>or</em> <code>rsbsa_number</code><br>
                    <strong>Optional:</strong> <code>middle_name, sex, parcel_no, parcel_barangay, area_ha, commodity</code>
                </small>
            </div>
            <button class="btn btn-success" id="submitBtn">
                <i class="bi bi-search"></i> Preview Import
            </button>
            <a href="{{ route('admin.farmers.import.template') }}" class="btn btn-outline-secondary">
                <i class="bi bi-download"></i> Download CSV Template
            </a>
        </form>

    @else
        {{-- ════════ PREVIEW STAGE ════════ --}}
        <div class="row g-2 mb-4">
            <div class="col-6 col-md">
                <div class="stat-card"><div class="stat-info">
                    <div class="label">Total Rows</div>
                    <div class="value">{{ number_format($summary['total']) }}</div>
                </div></div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card"><div class="stat-info">
                    <div class="label">New</div>
                    <div class="value text-success">{{ number_format($summary['new']) }}</div>
                </div></div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card"><div class="stat-info">
                    <div class="label">Duplicates</div>
                    <div class="value text-warning">{{ number_format($summary['duplicate']) }}</div>
                </div></div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card"><div class="stat-info">
                    <div class="label">Invalid</div>
                    <div class="value text-danger">{{ number_format($summary['invalid']) }}</div>
                </div></div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card"><div class="stat-info">
                    <div class="label">Non-Rice</div>
                    <div class="value text-muted">{{ number_format($summary['skipped']) }}</div>
                </div></div>
            </div>
        </div>

        <div class="alert alert-secondary small py-2">
            Showing first {{ count($rows) }} of {{ number_format($summary['total']) }} rows.
        </div>

        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
            <table class="table table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Line</th>
                        <th>Status</th>
                        <th>Name</th>
                        <th>RSBSA #</th>
                        <th>Barangay</th>
                        <th>Phone</th>
                        <th>Parcel</th>
                        <th>Area</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($rows as $r)
                    <tr class="{{ $r['status'] === 'new' ? '' : 'table-light' }}">
                        <td>{{ $r['line'] }}</td>
                        <td>
                            @if($r['status'] === 'new')
                                <span class="badge bg-success">New</span>
                            @elseif($r['status'] === 'duplicate')
                                <span class="badge bg-warning text-dark">Duplicate</span>
                            @else
                                <span class="badge bg-danger">Invalid</span>
                            @endif
                            @if(!$r['is_rice'])
                                <br><span class="badge bg-secondary mt-1">Non-rice</span>
                            @endif
                        </td>
                        <td>{{ $r['name'] }}</td>
                        <td><small>{{ $r['rsbsa_number'] ?: '—' }}</small></td>
                        <td>{{ $r['barangay'] }}</td>
                        <td><small>{{ $r['phone'] ?: '—' }}</small></td>
                        <td>{{ $r['parcel_no'] ?: '—' }}</td>
                        <td>{{ $r['land_area_ha'] ? number_format($r['land_area_ha'], 2) : '—' }}</td>
                        <td><small class="text-muted">{{ implode('; ', $r['errors']) }}</small></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if($summary['new'] > 0)
            <form action="{{ route('admin.farmers.import.commit') }}" method="POST" class="mt-3">
                @csrf
                <input type="hidden" name="batch_uuid" value="{{ $batch->uuid }}">
                <button class="btn btn-success" id="commitBtn">
                    <i class="bi bi-check-circle"></i>
                    Import {{ number_format($summary['new']) }} Farmers
                </button>
                <a href="{{ route('admin.farmers.import.form') }}" class="btn btn-secondary">Cancel</a>
            </form>
        @else
            <div class="alert alert-info mt-3">
                No new farmers to import. All rows are duplicates or invalid.
            </div>
            <a href="{{ route('admin.farmers.import.form') }}" class="btn btn-secondary">Upload Another File</a>
        @endif
    @endif
</div>

@endsection

@push('scripts')
<script>
    // Client-side file size guard — fail fast before the request even leaves
    document.getElementById('csvFile')?.addEventListener('change', function() {
        const maxBytes = 50 * 1024 * 1024; // 50MB matches server validation
        if (this.files[0] && this.files[0].size > maxBytes) {
            alert('File too large. Maximum is 50MB. Split the CSV and import in chunks.');
            this.value = '';
        }
    });

    // Show loading state on submit
    document.getElementById('importForm')?.addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Uploading & parsing... (this may take a minute)';
    });

    document.getElementById('commitBtn')?.addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Importing... (do not close this tab)';
    });
</script>
@endpush