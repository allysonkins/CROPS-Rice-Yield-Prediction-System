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
<div class="row g-2 mb-4">
    <!-- Low Yield Areas -->
    <div class="col-12 col-lg-4">
        <div class="card-custom" style="height: 100%;">
            <div class="card-title">
                <i class="bi bi-exclamation-triangle-fill" style="color: var(--red);"></i> Low Yield Areas
                <span class="badge bg-danger text-white ms-1" style="font-size: 10px;">
                    {{ $lowYieldFarms->count() ?? 0 }}
                </span>
            </div>
            <div class="text-muted small mb-2" style="font-size: 11px;">
                Predictions below 70% of variety potential
            </div>
            <div style="max-height: 220px; overflow-y: auto; padding-right: 4px;">
                @forelse($lowYieldFarms ?? [] as $pred)
                    @php
                        $farmName = $pred->farmRecord->farm->name ?? 'N/A';
                        $barangay = $pred->farmRecord->farm->barangay ?? '';
                        $variety  = $pred->farmRecord->riceVariety->name ?? 'N/A';
                        $y = $pred->predicted_yield_tons_ha;

                        $maxYield = $pred->farmRecord->riceVariety
                            ? $pred->farmRecord->riceVariety->getMaxYieldForMethod($pred->farmRecord->seeding_method)
                            : null;
                        $pct = ($maxYield && $maxYield > 0) ? round(($y / $maxYield) * 100) : null;
                    @endphp
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="font-size: 12.5px;">
                        <div style="flex: 1; min-width: 0;">
                            <strong>{{ $farmName }}</strong>
                            <span class="text-muted ms-1" style="font-size: 11px;">{{ $barangay }}</span>
                            <div class="text-muted" style="font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <i class="bi bi-flower1"></i> {{ $variety }}
                                @if($pct !== null)
                                    • {{ $pct }}% of max
                                @endif
                            </div>
                        </div>
                        <span class="badge-status low ms-2" style="font-size: 11px; padding: 2px 10px; white-space: nowrap;">
                            <span class="dot"></span> {{ number_format($y, 2) }} t/ha
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

                                // Status relative to variety max
                                $maxYield = $pred->farmRecord->riceVariety
                                    ? $pred->farmRecord->riceVariety->getMaxYieldForMethod($pred->farmRecord->seeding_method)
                                    : null;

                                if ($maxYield !== null && $maxYield > 0) {
                                    $ratio = $yield / $maxYield;
                                    $statusClass = $ratio >= 0.9 ? 'high' : ($ratio >= 0.7 ? 'medium' : 'low');
                                    $statusText = $ratio >= 0.9 ? 'High' : ($ratio >= 0.7 ? 'Medium' : 'Low');
                                } else {
                                    $statusClass = $yield >= 4.5 ? 'high' : ($yield >= 3.5 ? 'medium' : 'low');
                                    $statusText = $yield >= 4.5 ? 'High' : ($yield >= 3.5 ? 'Medium' : 'Low');
                                }
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $pred->farmRecord->farm->name ?? 'N/A' }}</strong>
                                    <br><small class="text-muted">{{ $pred->farmRecord->farm->barangay ?? '' }}</small>
                                </td>
                                <td>{{ $pred->farmRecord->riceVariety->name ?? 'N/A' }}</td>
                                <td>{{ $pred->farmRecord->season ?? 'N/A' }}</td>
                                <td><strong style="color: var(--green);">{{ number_format($yield, 2) }}</strong></td>
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
                    <a href="{{ route('admin.predictions.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;">
                        View All <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection