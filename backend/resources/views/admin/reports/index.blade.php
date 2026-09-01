@extends('layouts.app')

@section('title', 'Reports')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-left: 4px solid var(--green);">
        <div class="d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-2" style="color: var(--green);"></i>
            <strong>Success!</strong> {{ session('success') }}
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-left: 4px solid var(--red);">
        <div class="d-flex align-items-center">
            <i class="bi bi-x-circle-fill me-2" style="color: var(--red);"></i>
            <strong>Error!</strong> {{ session('error') }}
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-file-earmark-pdf" style="color: var(--red);"></i> Reports</h4>
    <a href="{{ route('admin.reports.generate') }}" class="btn btn-danger">
        <i class="bi bi-file-earmark-pdf"></i> Generate PDF Report
    </a>
</div>

<!-- Stats Cards -->
<div class="row g-2 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Total Farmers</div>
                <div class="value">{{ $stats['total_farmers'] ?? 0 }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Total Farms</div>
                <div class="value">{{ $stats['total_farms'] ?? 0 }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-geo-alt-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Rice Varieties</div>
                <div class="value">{{ $stats['total_varieties'] ?? 0 }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-flower1"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Avg Yield (t/ha)</div>
                <div class="value">{{ $stats['avg_yield'] ?? 'N/A' }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
        </div>
    </div>
</div>

<!-- Report Summary -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
        <div class="card-custom">
            <div class="card-title"><i class="bi bi-clipboard-data"></i> Report Summary</div>
            <ul class="list-unstyled">
                <li class="py-2 border-bottom">
                    <strong>Total Predictions:</strong> {{ $stats['total_predictions'] ?? 0 }}
                </li>
                <li class="py-2 border-bottom">
                    <strong>Low Yield Farms:</strong> 
                    <span class="text-danger">{{ $stats['low_yield_count'] ?? 0 }}</span> farms below 4.0 t/ha
                </li>
                <li class="py-2">
                    <strong>Generated:</strong> {{ now()->format('F d, Y h:i A') }}
                </li>
            </ul>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card-custom text-center py-5" style="border-top: 4px solid var(--red);">
            <i class="bi bi-file-earmark-pdf" style="font-size: 48px; color: var(--red);"></i>
            <h5 class="mt-3">Export Full Report</h5>
            <p class="text-muted small">Generate a comprehensive PDF report containing all farm data, predictions, and statistics.</p>
            <a href="{{ route('admin.reports.generate') }}" class="btn btn-danger">
                <i class="bi bi-file-earmark-pdf"></i> Download PDF
            </a>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-3">
    <div class="col-12">
        <div class="card-custom">
            <div class="card-title"><i class="bi bi-lightning-fill" style="color: var(--gold);"></i> Quick Actions</div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.reports.generate') }}" class="btn btn-outline-danger">
                    <i class="bi bi-file-earmark-pdf"></i> Generate Full Report
                </a>
                <a href="{{ route('admin.predictions.index') }}" class="btn btn-outline-primary">
                    <i class="bi bi-graph-up"></i> View Predictions
                </a>
                <a href="{{ route('admin.farms.index') }}" class="btn btn-outline-success">
                    <i class="bi bi-geo-alt"></i> Manage Farms
                </a>
            </div>
        </div>
    </div>
</div>
@endsection