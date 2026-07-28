@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <!-- ============================================================ -->
    <!-- STATS ROW -->
    <!-- ============================================================ -->
    <div class="row g-2 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-info">
                    <div class="label">Total Farmers</div>
                    <div class="value">{{ $farmerCount ?? 0 }}</div>
                </div>
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-info">
                    <div class="label">Total Farms</div>
                    <div class="value">{{ $farmCount ?? 0 }}</div>
                </div>
                <div class="stat-icon"><i class="bi bi-geo-alt-fill"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-info">
                    <div class="label">Rice Varieties</div>
                    <div class="value">{{ $varietyCount ?? 0 }}</div>
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
    <!-- CHART + MONTHLY TRENDS ROW -->
    <!-- ============================================================ -->
    <div class="row g-2 mb-3">
        <!-- Model Comparison Chart -->
        <div class="col-12 col-lg-6">
            <div class="card-custom" style="height: 100%;">
                <div class="card-title">
                    <i class="bi bi-bar-chart-fill"></i> Model Comparison
                    <span class="badge bg-light text-muted ms-1" style="font-weight: 400; font-size: 10px;">RF · XGB · Ensemble</span>
                </div>
                <canvas id="yieldChart" height="140"></canvas>
                <div class="mt-1 text-muted small" style="font-size: 11px;">
                    Average yield predictions across farms.
                </div>
            </div>
        </div>

        <!-- Monthly Yield Trends -->
        <div class="col-12 col-lg-6">
            <div class="card-custom" style="height: 100%;">
                <div class="card-title">
                    <i class="bi bi-graph-up"></i> Monthly Yield Trends
                    <span class="badge bg-light text-muted ms-1" style="font-weight: 400; font-size: 10px;">Last 6 months</span>
                </div>
                <canvas id="trendChart" height="140"></canvas>
                <div class="mt-1 text-muted small" style="font-size: 11px;">
                    Average Ensemble yield over time.
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- TOP FARMS + VARIETY PERFORMANCE ROW -->
    <!-- ============================================================ -->
    <div class="row g-2 mb-3">
        <!-- Top Performing Farms -->
        <div class="col-12 col-lg-6">
            <div class="card-custom" style="height: 100%;">
                <div class="card-title">
                    <i class="bi bi-trophy-fill" style="color: var(--gold);"></i> Top Performing Farms
                    <span class="badge bg-light text-muted ms-1" style="font-weight: 400; font-size: 10px;">Highest yields</span>
                </div>
                @php
                    $topFarms = \App\Models\Prediction::with(['farmRecord.farm', 'farmRecord.riceVariety'])
                        ->where('model_type', 'Ensemble')
                        ->orderBy('predicted_yield_tons_ha', 'desc')
                        ->limit(5)
                        ->get();
                @endphp
                <div style="max-height: 160px; overflow-y: auto; padding-right: 4px;">
                    @forelse($topFarms as $index => $pred)
                        @php
                            $y = $pred->predicted_yield_tons_ha;
                            $rank = $index + 1;
                            $icon = $rank == 1 ? '1' : ($rank == 2 ? '2' : ($rank == 3 ? '3' : $rank));
                            $badgeClass = $rank == 1 ? 'bg-warning' : ($rank == 2 ? 'bg-secondary' : ($rank == 3 ? 'bg-warning' : 'bg-light'));
                        @endphp
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom" style="font-size: 13px;">
                            <div>
                                <span class="badge {{ $badgeClass }} me-1" style="font-size: 10px; min-width: 20px;">{{ $icon }}</span>
                                <strong>{{ $pred->farmRecord->farm->name ?? 'N/A' }}</strong>
                                <span class="text-muted ms-2" style="font-size: 11px;">{{ $pred->farmRecord->farm->barangay ?? '' }}</span>
                            </div>
                            <span style="font-weight: 700; color: var(--green);">{{ number_format($y, 2) }} t/ha</span>
                        </div>
                    @empty
                        <div class="text-center py-3 text-muted">
                            <i class="bi bi-inbox" style="font-size: 22px;"></i>
                            <p class="mt-1 mb-0" style="font-size: 13px;">No predictions yet</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Rice Variety Performance -->
        <div class="col-12 col-lg-6">
            <div class="card-custom" style="height: 100%;">
                <div class="card-title">
                    <i class="bi bi-flower1"></i> Variety Performance
                    <span class="badge bg-light text-muted ms-1" style="font-weight: 400; font-size: 10px;">By average yield</span>
                </div>
                @php
                    $varietyPerformance = \App\Models\Prediction::with(['farmRecord.riceVariety'])
                        ->where('model_type', 'Ensemble')
                        ->get()
                        ->groupBy(function($item) {
                            return $item->farmRecord->riceVariety->name ?? 'Unknown';
                        })
                        ->map(function($group) {
                            return [
                                'avg' => $group->avg('predicted_yield_tons_ha'),
                                'count' => $group->count()
                            ];
                        })
                        ->sortByDesc('avg')
                        ->take(5);
                @endphp
                <div style="max-height: 160px; overflow-y: auto; padding-right: 4px;">
                    @forelse($varietyPerformance as $name => $data)
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom" style="font-size: 13px;">
                            <div>
                                <strong>{{ $name }}</strong>
                                <span class="text-muted ms-2" style="font-size: 11px;">{{ $data['count'] }} records</span>
                            </div>
                            <span style="font-weight: 700; color: var(--gold-dark);">{{ number_format($data['avg'], 2) }} t/ha</span>
                        </div>
                    @empty
                        <div class="text-center py-3 text-muted">
                            <i class="bi bi-inbox" style="font-size: 22px;"></i>
                            <p class="mt-1 mb-0" style="font-size: 13px;">No variety data available</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- LOW YIELD AREAS + RECENT PREDICTIONS ROW -->
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
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom" style="font-size: 13px;">
                            <div>
                                <strong>{{ $farm->name }}</strong>
                                <span class="text-muted ms-2" style="font-size: 11px;">{{ $farm->barangay }}</span>
                            </div>
                            <span class="badge-status low" style="font-size: 11px; padding: 1px 10px;">
                                <span class="dot"></span> {{ $farm->predicted_yield ?? 'N/A' }} t/ha
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

        <!-- Recent Predictions (Read-only, NO Actions) -->
        <div class="col-12 col-lg-8">
            <div class="card-custom" style="height: 100%;">
                <div class="card-title">
                    <i class="bi bi-clipboard-data-fill"></i> Recent Predictions
                    <span class="badge bg-light text-muted ms-1" style="font-weight: 400; font-size: 10px;">Latest Ensemble outputs</span>
                </div>

                @php
                    $recent = \App\Models\Prediction::with(['farmRecord.farm', 'farmRecord.riceVariety'])
                        ->where('model_type', 'Ensemble')
                        ->latest('updated_at')
                        ->limit(5)
                        ->get();
                @endphp

                <div class="table-responsive" style="max-height: 220px; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Farm</th>
                                <th>Variety</th>
                                <th>Season</th>
                                <th>Random Forest</th>
                                <th>XGBoost</th>
                                <th>Ensemble</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recent as $index => $pred)
                                @php
                                    $farmName = $pred->farmRecord->farm->name ?? 'N/A';
                                    $barangay = $pred->farmRecord->farm->barangay ?? '';
                                    $variety = $pred->farmRecord->riceVariety->name ?? 'N/A';
                                    $season = $pred->farmRecord->season ?? 'N/A';
                                    $yield = $pred->predicted_yield_tons_ha;

                                    $rf = \App\Models\Prediction::where('farm_record_id', $pred->farm_record_id)
                                        ->where('model_type', 'RandomForest')
                                        ->first();
                                    $xgb = \App\Models\Prediction::where('farm_record_id', $pred->farm_record_id)
                                        ->where('model_type', 'XGBoost')
                                        ->first();

                                    $statusClass = $yield >= 4.5 ? 'high' : ($yield >= 3.5 ? 'medium' : 'low');
                                    $statusText = $yield >= 4.5 ? 'High' : ($yield >= 3.5 ? 'Medium' : 'Low');
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <strong>{{ $farmName }}</strong>
                                        <br><small class="text-muted">{{ $barangay }}</small>
                                    </td>
                                    <td>{{ $variety }}</td>
                                    <td>{{ $season }}</td>
                                    <td>{{ $rf ? number_format($rf->predicted_yield_tons_ha, 2) : 'N/A' }}</td>
                                    <td>{{ $xgb ? number_format($xgb->predicted_yield_tons_ha, 2) : 'N/A' }}</td>
                                    <td><strong style="color: #4f46e5;">{{ number_format($yield, 2) }}</strong></td>
                                    <td>
                                        <span class="badge-status {{ $statusClass }}">
                                            <span class="dot"></span> {{ $statusText }}
                                        </span>
                                    </td>
                                    <td>{{ $pred->updated_at->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-3 text-muted">
                                        <i class="bi bi-inbox" style="font-size: 22px;"></i>
                                        <p class="mt-1 mb-0" style="font-size: 13px;">No predictions generated yet.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($recent->count() > 0)
                    <div class="mt-2 text-end">
                        <a href="{{ route('admin.predictions.index') }}" class="btn btn-sm btn-outline-secondary">
                            View All <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ─── Model Comparison Chart ───
        const ctx1 = document.getElementById('yieldChart').getContext('2d');
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: ['Farm A', 'Farm B', 'Farm C', 'Farm D'],
                datasets: [
                    {
                        label: 'Random Forest',
                        data: [4.2, 3.8, 5.1, 4.0],
                        backgroundColor: '#0f4c2b',
                        borderRadius: 3
                    },
                    {
                        label: 'XGBoost',
                        data: [4.5, 3.9, 4.8, 4.2],
                        backgroundColor: '#b8860b',
                        borderRadius: 3
                    },
                    {
                        label: 'Ensemble',
                        data: [4.3, 3.8, 4.9, 4.1],
                        backgroundColor: '#4f46e5',
                        borderRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: { weight: '600', size: 10 }, boxWidth: 10, padding: 6 }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 6,
                        grid: { color: '#f0f0f0', drawBorder: false },
                        ticks: { font: { size: 9 }, color: '#6b7280', stepSize: 1 }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 9 }, color: '#6b7280' }
                    }
                },
                layout: { padding: { top: 2, bottom: 2 } }
            }
        });

        // ─── Monthly Trend Chart ───
        const ctx2 = document.getElementById('trendChart').getContext('2d');
        new Chart(ctx2, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Ensemble Yield (t/ha)',
                    data: [4.1, 4.3, 4.0, 4.5, 4.7, 4.4],
                    borderColor: '#0f4c2b',
                    backgroundColor: 'rgba(15, 76, 43, 0.08)',
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#0f4c2b',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 1,
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 6,
                        grid: { color: '#f0f0f0', drawBorder: false },
                        ticks: { font: { size: 9 }, color: '#6b7280', stepSize: 1 }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 9 }, color: '#6b7280' }
                    }
                },
                layout: { padding: { top: 2, bottom: 2 } }
            }
        });
    });
</script>
@endpush