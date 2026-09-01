@extends('layouts.app')

@section('title', 'Farmer Dashboard')

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
                        <i class="bi bi-person" style="color: var(--gold);"></i> 
                        Welcome, {{ $farmer->name }}!
                    </h5>
                    <p style="color: var(--gray-600); font-size: 14px; margin-bottom: 0;">
                        <i class="bi bi-geo-alt"></i> {{ $farmer->barangay ?? 'No barangay set' }}
                        <span class="text-muted small ms-2">Farmer Account</span>
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
                <div class="label">My Farms</div>
                <div class="value">{{ $totalFarms ?? 0 }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-geo-alt-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Total Area (ha)</div>
                <div class="value">{{ number_format($totalArea ?? 0, 1) }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-rulers"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Varieties Used</div>
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
<!-- MY FARMS LIST -->
<!-- ============================================================ -->
<div class="row g-2 mb-4">
    <div class="col-12">
        <div class="card-custom">
            <div class="card-title"><i class="bi bi-list-ul"></i> My Farms</div>
            @if($farms->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Farm Name</th>
                                <th>Barangay</th>
                                <th>Area (ha)</th>
                                <th>Soil Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($farms as $index => $farm)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><strong>{{ $farm->name }}</strong></td>
                                    <td>{{ $farm->barangay }}</td>
                                    <td>{{ number_format($farm->land_area_ha, 2) }}</td>
                                    <td>{{ $farm->soil_type }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted text-center py-3">You don't have any registered farms yet.</p>
            @endif
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- RECENT PREDICTIONS + ADVISORIES -->
<!-- ============================================================ -->
<div class="row g-2">
    <!-- Recent Predictions -->
    <div class="col-12 col-lg-8">
        <div class="card-custom" style="height: 100%;">
            <div class="card-title">
                <i class="bi bi-clipboard-data-fill"></i> Recent Predictions
                <span class="badge bg-light text-muted ms-1" style="font-weight: 400; font-size: 10px;">Latest Random Forest outputs</span>
                <a href="{{ route('farmer.predictions.index') }}" class="btn btn-sm btn-outline-secondary float-end">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
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
                                    <p class="mt-1 mb-0" style="font-size: 13px;">No predictions for your farms yet.</p>
                                    <small>CAO staff will generate predictions from farm records.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Advisories -->
    <div class="col-12 col-lg-4">
        <div class="card-custom" style="height: 100%;">
            <div class="card-title">
                <i class="bi bi-megaphone" style="color: var(--gold);"></i> Latest Advisories
            </div>
            <div style="max-height: 220px; overflow-y: auto; padding-right: 4px;">
                @forelse($advisories as $advisory)
                    <div class="py-2 border-bottom" style="font-size: 13px;">
                        <div class="d-flex justify-content-between align-items-start">
                            <strong>{{ $advisory->title }}</strong>
                            <span class="badge bg-info text-white" style="font-size: 9px;">New</span>
                        </div>
                        <p class="text-muted small mb-1">{{ Str::limit($advisory->content, 80) }}</p>
                        <small class="text-muted">{{ $advisory->created_at->diffForHumans() }}</small>
                    </div>
                @empty
                    <div class="text-center py-3 text-muted">
                        <i class="bi bi-inbox" style="font-size: 22px;"></i>
                        <p class="mt-1 mb-0" style="font-size: 13px;">No advisories available.</p>
                    </div>
                @endforelse
            </div>
            @if($advisories->count() > 0)
                <div class="mt-2 text-end">
                    <a href="{{ route('admin.advisories.index') }}" class="btn btn-sm btn-outline-secondary">
                        View All <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection