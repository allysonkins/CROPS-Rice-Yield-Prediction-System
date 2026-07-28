@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">📄 Reports</h4>
    <a href="{{ route('admin.reports.generate') }}" class="btn btn-danger">
        <i class="bi bi-file-earmark-pdf"></i> Generate PDF Report
    </a>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon"><i class="bi bi-people"></i></div>
            <div class="label">Total Farmers</div>
            <div class="value">{{ $stats['total_farmers'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon"><i class="bi bi-geo-alt"></i></div>
            <div class="label">Total Farms</div>
            <div class="value">{{ $stats['total_farms'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon"><i class="bi bi-seedling"></i></div>
            <div class="label">Rice Varieties</div>
            <div class="value">{{ $stats['total_varieties'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-left-color: #f59e0b;">
            <div class="icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="label">Avg Yield (t/ha)</div>
            <div class="value">{{ $stats['avg_yield'] ? round($stats['avg_yield'], 2) : 'N/A' }}</div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card-custom">
            <h6>📊 Report Summary</h6>
            <p class="text-muted">
                <strong>Low Yield Farms:</strong> {{ $stats['low_yield_count'] ?? 0 }} farms below 4.0 t/ha
            </p>
            <p class="text-muted">
                <strong>Report Generated:</strong> {{ now()->format('F d, Y h:i A') }}
            </p>
            <div class="mt-3">
                <a href="{{ route('admin.reports.generate') }}" class="btn btn-danger">
                    <i class="bi bi-file-earmark-pdf"></i> Download Full Report
                </a>
            </div>
        </div>
    </div>
</div>
@endsection