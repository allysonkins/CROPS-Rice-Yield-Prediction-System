@extends('layouts.app')

@section('title', 'My Predictions')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-graph-up" style="color: var(--green);"></i> My Predictions</h4>
    <span class="badge bg-secondary">{{ $stats['total'] }} Predictions</span>
</div>

<!-- Stats Cards -->
<div class="row g-2 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Total</div>
                <div class="value">{{ $stats['total'] }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-bar-chart"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Average Yield</div>
                <div class="value">{{ $stats['avg_yield'] ? number_format($stats['avg_yield'], 2) : 'N/A' }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Highest Yield</div>
                <div class="value">{{ $stats['max_yield'] ? number_format($stats['max_yield'], 2) : 'N/A' }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-arrow-up"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Lowest Yield</div>
                <div class="value">{{ $stats['min_yield'] ? number_format($stats['min_yield'], 2) : 'N/A' }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-arrow-down"></i></div>
        </div>
    </div>
</div>

<!-- Predictions Table -->
<div class="card-custom">
    <div class="card-title"><i class="bi bi-table"></i> Prediction Records</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Farm</th>
                    <th>Variety</th>
                    <th>Season</th>
                    <th>RF Yield</th>
                    <th>Status</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody>
                @forelse($predictions as $index => $pred)
                    @php
                        $yield = $pred->predicted_yield_tons_ha;
                        $statusClass = $yield >= 4.5 ? 'high' : ($yield >= 3.5 ? 'medium' : 'low');
                        $statusText = $yield >= 4.5 ? 'High' : ($yield >= 3.5 ? 'Medium' : 'Low');
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $pred->farmRecord->farm->name ?? 'N/A' }}</strong>
                            <br><small class="text-muted">{{ $pred->farmRecord->farm->barangay ?? '' }}</small>
                        </td>
                        <td>{{ $pred->farmRecord->riceVariety->name ?? 'N/A' }}</td>
                        <td>{{ $pred->farmRecord->season ?? 'N/A' }}</td>
                        <td><strong style="color: #0f4c2b;">{{ number_format($yield, 2) }}</strong></td>
                        <td>
                            <span class="badge-status {{ $statusClass }}">
                                <span class="dot"></span> {{ $statusText }}
                            </span>
                        </td>
                        <td>{{ $pred->updated_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 28px;"></i>
                            <p class="mt-2 mb-0">No predictions for your farms yet.</p>
                            <small>CAO staff will generate predictions from your farm records.</small>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection