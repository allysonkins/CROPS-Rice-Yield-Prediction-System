@extends('layouts.app')

@section('title', 'Staff Dashboard')

@section('content')
<!-- ============================================================ -->
<!-- WELCOME BANNER -->
<!-- ============================================================ -->
<div class="row g-2 mb-4">
    <div class="col-12">
        <div class="card-custom" style="border-left: 4px solid var(--gold); background: linear-gradient(135deg, #f9fafb 0%, #f0f4f2 100%);">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h5 style="color: var(--green); font-weight: 700; margin-bottom: 4px;">
                        <i class="bi bi-person-badge" style="color: var(--gold);"></i> 
                        Welcome, {{ auth()->user()->name }}!
                    </h5>
                    <p style="color: var(--gray-600); font-size: 14px; margin-bottom: 0;">
                        You have access to manage farms, records, and advisories. 
                        <span class="text-muted small">Staff Account</span>
                    </p>
                </div>
                <div class="d-none d-md-block">
                    <span class="badge bg-light text-muted" style="font-size: 14px; padding: 8px 16px;">
                        <i class="bi bi-clock"></i> {{ now()->format('F d, Y') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- STATS ROW -->
<!-- ============================================================ -->
<div class="row g-2 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Total Farms</div>
                <div class="value">{{ $totalFarms ?? 0 }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-geo-alt-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Total Farmers</div>
                <div class="value">{{ $totalFarmers ?? 0 }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Rice Varieties</div>
                <div class="value">{{ $totalVarieties ?? 0 }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-flower1"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Avg Yield (t/ha)</div>
                <div class="value">{{ $avgYield ?? 'N/A' }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- LOW YIELD AREAS + RECENT PREDICTIONS -->
<!-- ============================================================ -->
<div class="row g-2">
    <!-- Low Yield Areas -->
    <div class="col-12 col-lg-4">
        <div class="card-custom" style="height: 100%;">
            <div class="card-title">
                <i class="bi bi-exclamation-triangle-fill" style="color: var(--red);"></i> Low Yield Areas
                <span class="badge bg-danger text-white ms-1" style="font-size: 10px;">{{ count($lowYieldFarms ?? []) }}</span>
            </div>
            <div style="max-height: 220px; overflow-y: auto; padding-right: 4px;">
                @forelse($lowYieldFarms ?? [] as $farm)
                    @php
                        $latestPrediction = $farm->farmRecords
                            ->flatMap(function($r) { return $r->predictions; })
                            ->where('model_type', 'RandomForest')
                            ->last();
                        $yield = $latestPrediction ? $latestPrediction->predicted_yield_tons_ha : null;
                    @endphp
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom" style="font-size: 13px;">
                        <div>
                            <strong>{{ $farm->name }}</strong>
                            <span class="text-muted ms-2" style="font-size: 11px;">{{ $farm->barangay }}</span>
                        </div>
                        <span class="badge-status low" style="font-size: 11px; padding: 1px 10px;">
                            <span class="dot"></span> {{ $yield ?? 'N/A' }} t/ha
                        </span>
                    </div>
                @empty
                    <div class="text-center py-3 text-muted">
                        <i class="bi bi-check-circle-fill" style="font-size: 22px; color: var(--green);"></i>
                        <p class="mt-1 mb-0" style="font-size: 13px;">No low yield areas detected</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Predictions -->
    <div class="col-12 col-lg-8">
        <div class="card-custom" style="height: 100%;">
            <div class="card-title">
                <i class="bi bi-clipboard-data-fill"></i> Recent Predictions
                <span class="badge bg-light text-muted ms-1" style="font-weight: 400; font-size: 10px;">Latest Random Forest outputs</span>
            </div>

            <div class="table-responsive" style="max-height: 220px; overflow-y: auto;">
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
                        @forelse($recentPredictions as $index => $pred)
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
                                <td colspan="7" class="text-center py-3 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 22px;"></i>
                                    <p class="mt-1 mb-0" style="font-size: 13px;">No predictions generated yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($recentPredictions->count() > 0)
                <div class="mt-2 text-end">
                    <a href="{{ route('admin.predictions.index') }}" class="btn btn-sm btn-outline-secondary">
                        View All <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- QUICK ACTIONS -->
<!-- ============================================================ -->
<div class="row g-3 mt-2">
    <div class="col-12">
        <div class="card-custom">
            <div class="card-title"><i class="bi bi-lightning-fill" style="color: var(--gold);"></i> Quick Actions</div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.farms.index') }}" class="btn btn-outline-success">
                    <i class="bi bi-geo-alt"></i> Manage Farms
                </a>
                <a href="{{ route('admin.farm-records.index') }}" class="btn btn-outline-primary">
                    <i class="bi bi-clipboard-data"></i> Farm Records
                </a>
                <a href="{{ route('admin.advisories.index') }}" class="btn btn-outline-warning">
                    <i class="bi bi-megaphone"></i> Advisories
                </a>
                <a href="{{ route('admin.predictions.index') }}" class="btn btn-outline-info">
                    <i class="bi bi-graph-up"></i> View Predictions
                </a>
                <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-file-earmark-pdf"></i> Reports
                </a>
            </div>
        </div>
    </div>
</div>
@endsection