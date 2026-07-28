@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <!-- ─── STATS ROW ─── -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-info">
                    <div class="label">Total Farmers</div>
                    <div class="value">{{ $farmerCount ?? 0 }}</div>
                </div>
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-info">
                    <div class="label">Total Farms</div>
                    <div class="value">{{ $farmCount ?? 0 }}</div>
                </div>
                <div class="stat-icon"><i class="bi bi-geo-alt-fill"></i></div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-info">
                    <div class="label">Rice Varieties</div>
                    <div class="value">{{ $varietyCount ?? 0 }}</div>
                </div>
                <div class="stat-icon"><i class="bi bi-flower1"></i></div>
            </div>
        </div>
        <class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-info">
                    <div class="label">Avg Yield (Tons/ha)</div>
                    <div class="value">{{ $avgYield ?? 'N/A' }}</div>
                </div>
                <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
            </div>
        </div>
    </div>

    <!-- ─── CHART + LOW YIELD ─── -->
    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card-custom">
                <div class="card-title"><i class="bi bi-bar-chart-fill"></i> Yield Prediction Comparison</div>
                <canvas id="yieldChart" height="250"></canvas>
                <p class="text-muted small mt-3 mb-0">Comparison of Random Forest, XGBoost, and Ensemble predictions across farms.</p>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card-custom">
                <div class="card-title"><i class="bi bi-exclamation-triangle-fill" style="color: var(--red);"></i> Low Yield Areas</div>
                @forelse($lowYieldFarms ?? [] as $farm)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <strong>{{ $farm->name }}</strong>
                            <br><small class="text-muted">{{ $farm->barangay }}</small>
                        </div>
                        <span class="badge-status low">
                            <span class="dot"></span> {{ $farm->predicted_yield ?? 'N/A' }} t/ha
                        </span>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-check-circle-fill" style="font-size: 28px; color: var(--green);"></i>
                        <p class="mt-2 mb-0 fw-semibold">No low yield areas detected</p>
                        <small>All farms are performing well.</small>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- ─── RECENT PREDICTIONS ─── -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card-custom">
                <div class="card-title"><i class="bi bi-clipboard-data-fill"></i> Recent Farm Records</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Farm</th>
                                <th>Variety</th>
                                <th>Season</th>
                                <th>Ensemble Yield</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $recent = \App\Models\Prediction::with(['farmRecord.farm', 'farmRecord.riceVariety'])
                                    ->where('model_type', 'Ensemble')
                                    ->latest('updated_at')
                                    ->limit(5)
                                    ->get();
                            @endphp

                            @forelse($recent as $pred)
                                <tr>
                                    <td><strong>{{ $pred->farmRecord->farm->name ?? 'N/A' }}</strong><br><small class="text-muted">{{ $pred->farmRecord->farm->barangay ?? '' }}</small></td>
                                    <td>{{ $pred->farmRecord->riceVariety->name ?? 'N/A' }}</td>
                                    <td>{{ $pred->farmRecord->season ?? 'N/A' }}</td>
                                    <td><strong>{{ number_format($pred->predicted_yield_tons_ha, 2) }} t/ha</strong></td>
                                    <td>
                                        @php $y = $pred->predicted_yield_tons_ha; @endphp
                                        <span class="badge-status {{ $y >= 4.5 ? 'high' : ($y >= 3.5 ? 'medium' : 'low') }}">
                                            <span class="dot"></span>
                                            {{ $y >= 4.5 ? 'High' : ($y >= 3.5 ? 'Medium' : 'Low') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox" style="font-size: 26px;"></i>
                                        <p class="mt-2 mb-0">No predictions generated yet.</p>
                                        <small>Run the ML service to see results here.</small>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const ctx = document.getElementById('yieldChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Farm A', 'Farm B', 'Farm C', 'Farm D'],
            datasets: [
                { label: 'Random Forest', data: [4.2, 3.8, 5.1, 4.0], backgroundColor: '#0f4c2b', borderRadius: 6 },
                { label: 'XGBoost', data: [4.5, 3.9, 4.8, 4.2], backgroundColor: '#b8860b', borderRadius: 6 },
                { label: 'Ensemble', data: [4.3, 3.8, 4.9, 4.1], backgroundColor: '#4f46e5', borderRadius: 6 }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top', labels: { font: { weight: '600', size: 12 } } } },
            scales: {
                y: { beginAtZero: true, max: 6, grid: { color: '#f0f0f0' } },
                x: { grid: { display: false } }
            }
        }
    });
</script>
@endpush