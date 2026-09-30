@extends('layouts.app')

@section('title', 'Staff Dashboard')

@section('content')
@php
    use App\Models\Prediction;
    use App\Models\Farm;
    use App\Models\FarmRecord;
    use App\Models\User;

    $farmerCount  = User::where('role', 'farmer')->count();
    $farmCount    = Farm::count();
    $recordCount  = FarmRecord::count();

    $allPredictions = Prediction::with(['farmRecord.farm', 'farmRecord.riceVariety'])
    ->where('model_type', 'RandomForest')
    ->orderBy('created_at', 'desc')
    ->get();
    $uniquePredictions = $allPredictions->unique('farm_record_id');

    $totalPred = $uniquePredictions->count();
    $avgYield  = $uniquePredictions->avg('predicted_yield_tons_ha');

    $trendLabels = []; $trendData = [];
    for ($i = 11; $i >= 0; $i--) {
        $start = now()->subWeeks($i)->startOfWeek();
        $end   = now()->subWeeks($i)->endOfWeek();
        $weekPreds = $uniquePredictions->filter(fn($p) => $p->created_at >= $start && $p->created_at <= $end);
        $trendLabels[] = $start->format('M d');
        $trendData[]   = $weekPreds->count() > 0 ? round($weekPreds->avg('predicted_yield_tons_ha'), 2) : null;
    }

    $barangayData = $uniquePredictions
        ->groupBy(fn($p) => $p->farmRecord->farm->barangay ?? 'Unknown')
        ->map(fn($g) => ['avg' => round($g->avg('predicted_yield_tons_ha'), 2), 'count' => $g->count()])
        ->filter(fn($d) => $d['avg'] !== null)
        ->sortByDesc('avg');

    $bins = ['< 2.5'=>0,'2.5–3.0'=>0,'3.0–3.5'=>0,'3.5–4.0'=>0,'4.0–4.5'=>0,'4.5–5.0'=>0,'5.0–5.5'=>0,'≥ 5.5'=>0];
    foreach ($uniquePredictions as $p) {
        $y = $p->predicted_yield_tons_ha;
        if ($y === null) continue;
        if     ($y < 2.5) $bins['< 2.5']++;
        elseif ($y < 3.0) $bins['2.5–3.0']++;
        elseif ($y < 3.5) $bins['3.0–3.5']++;
        elseif ($y < 4.0) $bins['3.5–4.0']++;
        elseif ($y < 4.5) $bins['4.0–4.5']++;
        elseif ($y < 5.0) $bins['4.5–5.0']++;
        elseif ($y < 5.5) $bins['5.0–5.5']++;
        else              $bins['≥ 5.5']++;
    }

    $recent = $uniquePredictions->sortByDesc('created_at')->take(5);
@endphp

<!-- WELCOME BANNER -->
<div class="row g-2 mb-3">
    <div class="col-12">
        <div class="card-custom" style="border-left: 4px solid var(--gold); background: linear-gradient(135deg, #f9fafb 0%, #f0f4f2 100%); padding: 16px 22px;">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-badge" style="color: var(--gold); font-size: 20px;"></i>
                    <span style="font-size: 15px; font-weight: 700; color: var(--green);">
                        Welcome, {{ auth()->user()->name }}!
                    </span>
                    <span class="text-muted small">· Staff Account</span>
                </div>
                <span class="badge bg-light text-muted" style="font-size: 12px; padding: 6px 12px;">
                    <i class="bi bi-clock"></i> {{ now()->format('M d, Y') }}
                </span>
            </div>
        </div>
    </div>
</div>

<!-- STATS ROW -->
<div class="row g-2 mb-3">
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="padding: 14px 18px;">
            <div class="stat-info">
                <div class="label" style="font-size: 12px;">Total Farmers</div>
                <div class="value" style="font-size: 22px;">{{ $farmerCount }}</div>
            </div>
            <div class="stat-icon" style="width: 40px; height: 40px; font-size: 18px;"><i class="bi bi-people-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="padding: 14px 18px;">
            <div class="stat-info">
                <div class="label" style="font-size: 12px;">Total Farms</div>
                <div class="value" style="font-size: 22px;">{{ $farmCount }}</div>
            </div>
            <div class="stat-icon" style="width: 40px; height: 40px; font-size: 18px;"><i class="bi bi-geo-alt-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="padding: 14px 18px;">
            <div class="stat-info">
                <div class="label" style="font-size: 12px;">Farm Records</div>
                <div class="value" style="font-size: 22px;">{{ $recordCount }}</div>
            </div>
            <div class="stat-icon" style="width: 40px; height: 40px; font-size: 18px;"><i class="bi bi-clipboard-data-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="padding: 14px 18px;">
            <div class="stat-info">
                <div class="label" style="font-size: 12px;">Avg City Yield</div>
                <div class="value" style="font-size: 22px;">
                    {{ $avgYield ? number_format($avgYield, 2) : '—' }}
                    <small style="font-size:12px;color:var(--gray-500);">t/ha</small>
                </div>
            </div>
            <div class="stat-icon" style="width: 40px; height: 40px; font-size: 18px;"><i class="bi bi-graph-up-arrow"></i></div>
        </div>
    </div>
</div>

<!-- PRIMARY PANEL -->
<div class="row g-2 mb-3">
    <div class="col-12">
        <div class="card-custom" style="border-top: 3px solid var(--green); padding: 16px 20px;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-map" style="color: var(--green); font-size: 18px;"></i>
                    <span style="font-size: 14px; font-weight: 700;">Spatial & Temporal Yield Mapping</span>
                </div>
                <span class="text-muted" style="font-size: 11px;">{{ $totalPred }} predictions</span>
            </div>

            <div class="row g-2">
                <!-- Temporal -->
                <div class="col-12 col-lg-6">
                    <div style="background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 10px; padding: 10px 14px;">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-clock-history" style="color: var(--green); font-size: 14px;"></i>
                            <span style="font-size: 12px; font-weight: 700;">Temporal — Yield Trend</span>
                        </div>
                        <div style="height: 190px;"><canvas id="trendChart"></canvas></div>
                    </div>
                </div>

                <!-- Spatial -->
                <div class="col-12 col-lg-6">
                    <div style="background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 10px; padding: 10px 14px;">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-geo-alt" style="color: var(--gold-dark); font-size: 14px;"></i>
                            <span style="font-size: 12px; font-weight: 700;">Spatial — Yield by Barangay</span>
                        </div>
                        <div style="height: 190px;"><canvas id="barangayChart"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DISTRIBUTION + RECENT -->
<div class="row g-2">
    <div class="col-12 col-lg-5">
        <div class="card-custom" style="padding: 16px 20px; height: 100%;">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-bar-chart-fill" style="color: var(--green); font-size: 16px;"></i>
                <span style="font-size: 13px; font-weight: 700;">Yield Output Distribution</span>
            </div>
            <div style="height: 210px;"><canvas id="distChart"></canvas></div>
        </div>
    </div>

    <div class="col-12 col-lg-7">
        <div class="card-custom" style="padding: 16px 20px; height: 100%;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-clipboard-data-fill" style="color: var(--green); font-size: 16px;"></i>
                    <span style="font-size: 13px; font-weight: 700;">Recent Predictions</span>
                </div>
                <a href="{{ route('admin.predictions.index') }}" class="btn btn-sm btn-outline-secondary" style="font-size: 11px; padding: 3px 10px;">
                    View All
                </a>
            </div>

            <div class="table-responsive" style="max-height: 210px; overflow-y: auto;">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th style="padding: 6px 10px;">#</th>
                            <th style="padding: 6px 10px;">Farm</th>
                            <th style="padding: 6px 10px;">Variety</th>
                            <th style="padding: 6px 10px;">Yield</th>
                            <th style="padding: 6px 10px;">Class</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recent as $index => $pred)
                            @php
                                $class = $pred->predicted_class ?? 'Medium';
                                $statusClass = match($class) {
                                    'High' => 'high',
                                    'Low'  => 'low',
                                    default => 'medium',
                                };
                            @endphp
                            <tr>
                                <td style="padding: 6px 10px;">{{ $index + 1 }}</td>
                                <td style="padding: 6px 10px;">
                                    <strong>{{ $pred->farmRecord->farm->name ?? 'N/A' }}</strong>
                                    <br><small class="text-muted">{{ $pred->farmRecord->farm->barangay ?? '' }}</small>
                                </td>
                                <td style="padding: 6px 10px;"><small>{{ $pred->farmRecord->riceVariety->name ?? 'N/A' }}</small></td>
                                <td style="padding: 6px 10px;"><strong style="color: #0f4c2b;">{{ number_format($pred->predicted_yield_tons_ha, 2) }}</strong></td>
                                <td style="padding: 6px 10px;">
                                    <span class="badge-status {{ $statusClass }}" style="font-size: 11px; padding: 2px 10px;">
                                        <span class="dot"></span> {{ $class }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted" style="font-size: 12px;">
                                    <i class="bi bi-inbox"></i> No predictions yet.
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
document.addEventListener('DOMContentLoaded', function() {
    const GREEN = '#1b5e3a';
    const GOLD  = '#b8860b';
    const GRAY_L = '#e5e7eb';
    Chart.defaults.font.size = 10;

    const tc = document.getElementById('trendChart');
    if (tc) new Chart(tc, {
        type: 'line',
        data: {
            labels: @json($trendLabels),
            datasets: [{
                data: @json($trendData),
                borderColor: GREEN,
                backgroundColor: 'rgba(27, 94, 58, 0.10)',
                borderWidth: 2.5, pointRadius: 4,
                pointBackgroundColor: GREEN,
                pointBorderColor: 'white', pointBorderWidth: 2,
                tension: 0.35, fill: true, spanGaps: true,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: false, grid: { color: GRAY_L }, ticks: { font: { size: 10 } } },
                x: { grid: { display: false }, ticks: { font: { size: 9 } } }
            }
        }
    });

    const bc = document.getElementById('barangayChart');
    if (bc) {
        const values = @json($barangayData->pluck('avg'));
        new Chart(bc, {
            type: 'bar',
            data: {
                labels: @json($barangayData->keys()),
                datasets: [{
                    data: values,
                    backgroundColor: values.map(v => v >= 4.5 ? GREEN : (v >= 3.5 ? GOLD : '#c0392b')),
                    borderRadius: 5,
                }]
            },
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, grid: { color: GRAY_L }, ticks: { font: { size: 10 } } },
                    y: { grid: { display: false }, ticks: { font: { size: 10, weight: '600' } } }
                }
            }
        });
    }

    const dc = document.getElementById('distChart');
    if (dc) new Chart(dc, {
        type: 'bar',
        data: {
            labels: @json(array_keys($bins)),
            datasets: [{
                data: @json(array_values($bins)),
                backgroundColor: ['#c0392b','#e67e22','#f2b705','#f1c40f','#a3c644','#4a9c5d','#2e7d32','#1b5e3a'],
                borderRadius: 5,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: GRAY_L }, ticks: { font: { size: 10 }, precision: 0 } },
                x: { grid: { display: false }, ticks: { font: { size: 9 } } }
            }
        }
    });
});
</script>
@endpush