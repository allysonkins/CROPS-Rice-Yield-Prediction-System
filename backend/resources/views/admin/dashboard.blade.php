@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
@php
    use App\Models\Prediction;
    use App\Models\Farm;
    use App\Models\FarmRecord;
    use App\Models\User;

    $farmerCount = User::where('role', 'farmer')->count();
    $farmCount   = Farm::count();
    $recordCount = FarmRecord::count();

    // Pull latest XGBoost predictions, eager-load what we need for class computation
    $allPredictions = Prediction::with([
            'farmRecord.farm',
            'farmRecord.riceVariety',
        ])
        ->where('model_type', 'XGBoost')
        ->orderBy('created_at', 'desc')
        ->get();

    $uniquePredictions = $allPredictions->unique('farm_record_id');

    $totalPred     = $uniquePredictions->count();
    $avgYield      = $uniquePredictions->whereNotNull('predicted_yield_tons_ha')->avg('predicted_yield_tons_ha');
    $avgConfidence = $uniquePredictions->whereNotNull('confidence')->count() > 0
        ? $uniquePredictions->whereNotNull('confidence')->avg('confidence')
        : null;

    // ── Variety-relative yield class (computed app-side, not a model output) ──
    $yieldClassOf = function ($prediction) {
        $yield = $prediction->predicted_yield_tons_ha;
        if ($yield === null) return null;

        $fr = $prediction->farmRecord;
        if (!$fr || !$fr->riceVariety) return null;

        $method   = $fr->seeding_method ?? 'Transplanted';
        $vy       = $fr->riceVariety->getYieldForMethod($method);
        $avgYield = $vy->avg ?? ($fr->riceVariety->avg_yield ?? null);

        if (!$avgYield || $avgYield <= 0) return null;

        $ratio = $yield / $avgYield;
        if ($ratio >= 1.125) return 'High';
        if ($ratio >= 0.875) return 'Medium';
        return 'Low';
    };

    // ── LOW YIELD IDENTIFICATION ──
    // Farms producing < 87.5% of their variety's own average yield.
    // Sorted worst-first (lowest ratio at the top) so the admin sees the
    // most critical cases immediately.
    $lowYieldPredictions = collect();
    foreach ($uniquePredictions as $p) {
        $yield = $p->predicted_yield_tons_ha;
        if ($yield === null) continue;

        $fr = $p->farmRecord;
        if (!$fr || !$fr->riceVariety) continue;

        $method   = $fr->seeding_method ?? 'Transplanted';
        $vy       = $fr->riceVariety->getYieldForMethod($method);
        $avgYield = $vy->avg ?? ($fr->riceVariety->avg_yield ?? null);
        if (!$avgYield || $avgYield <= 0) continue;

        $ratio = $yield / $avgYield;
        if ($ratio < 0.875) {
            $lowYieldPredictions->push([
                'prediction' => $p,
                'yield'      => $yield,
                'avg'        => $avgYield,
                'ratio'      => $ratio,
                'confidence' => $p->confidence,
            ]);
        }
    }
    $lowYieldPredictions = $lowYieldPredictions->sortBy('ratio')->values();
    $lowYieldCount = $lowYieldPredictions->count();
    $lowYieldPct   = $totalPred > 0 ? round(($lowYieldCount / $totalPred) * 100, 1) : 0;

    // ── Temporal trend (weekly, last 12 weeks) ──
    $trendLabels = []; $trendData = [];
    for ($i = 11; $i >= 0; $i--) {
        $start = now()->subWeeks($i)->startOfWeek();
        $end   = now()->subWeeks($i)->endOfWeek();
        $weekPreds = $uniquePredictions->filter(fn($p) => $p->created_at >= $start && $p->created_at <= $end);
        $trendLabels[] = $start->format('M d');
        $trendData[]   = $weekPreds->count() > 0
            ? round($weekPreds->avg('predicted_yield_tons_ha'), 2)
            : null;
    }

    // ── Spatial aggregation by barangay ──
    $barangayData = $uniquePredictions
        ->groupBy(fn($p) => $p->farmRecord->farm->barangay ?? 'Unknown')
        ->map(fn($g) => [
            'avg'   => round($g->avg('predicted_yield_tons_ha'), 2),
            'count' => $g->count(),
        ])
        ->filter(fn($d) => $d['avg'] !== null)
        ->sortByDesc('avg');

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
                    <span class="text-muted small">· Admin Account</span>
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
    <div class="col-6 col-lg-4 col-xl">
        <div class="stat-card" style="padding: 14px 18px;">
            <div class="stat-info">
                <div class="label" style="font-size: 12px;">Total Farmers</div>
                <div class="value" style="font-size: 22px;">{{ $farmerCount }}</div>
            </div>
            <div class="stat-icon" style="width: 40px; height: 40px; font-size: 18px;"><i class="bi bi-people-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-4 col-xl">
        <div class="stat-card" style="padding: 14px 18px;">
            <div class="stat-info">
                <div class="label" style="font-size: 12px;">Total Farms</div>
                <div class="value" style="font-size: 22px;">{{ $farmCount }}</div>
            </div>
            <div class="stat-icon" style="width: 40px; height: 40px; font-size: 18px;"><i class="bi bi-geo-alt-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-4 col-xl">
        <div class="stat-card" style="padding: 14px 18px;">
            <div class="stat-info">
                <div class="label" style="font-size: 12px;">Farm Records</div>
                <div class="value" style="font-size: 22px;">{{ $recordCount }}</div>
            </div>
            <div class="stat-icon" style="width: 40px; height: 40px; font-size: 18px;"><i class="bi bi-clipboard-data-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-4 col-xl">
        <div class="stat-card" style="padding: 14px 18px;">
            <div class="stat-info">
                <div class="label" style="font-size: 12px;">Avg City Yield</div>
                <div class="value" style="font-size: 22px;">
                    {{ $avgYield ? number_format($avgYield, 2) : '—' }}
                    <small style="font-size:12px;color:var(--gray-500);">t/ha</small>
                </div>
                <div class="text-muted" style="font-size: 11px; margin-top: 2px;">
                    Across {{ $totalPred }} prediction{{ $totalPred !== 1 ? 's' : '' }}
                </div>
            </div>
            <div class="stat-icon" style="width: 40px; height: 40px; font-size: 18px;"><i class="bi bi-graph-up-arrow"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-4 col-xl">
        <div class="stat-card" style="padding: 14px 18px;">
            <div class="stat-info">
                <div class="label" style="font-size: 12px;">
                    Avg Confidence
                    <i class="bi bi-question-circle-fill"
                       style="font-size: 11px; color: var(--gray-400); cursor: help;"
                       data-bs-toggle="tooltip"
                       data-bs-placement="top"
                       data-bs-html="true"
                       title="<div style='text-align:left; font-size:12px; line-height:1.5;'>
                           <strong>What is confidence?</strong><br>
                           Each prediction ships with an <em>80% interval</em> from two extra XGBoost models
                           trained on the 10th and 90th percentile.<br><br>
                           <strong>Confidence = 1 − (interval width ÷ predicted yield)</strong><br>
                           Narrow interval → high confidence. It is <em>not</em> accuracy.
                       </div>"></i>
                </div>
                <div class="value" style="font-size: 22px;">
                    @if($avgConfidence !== null)
                        {{ number_format($avgConfidence * 100, 1) }}<small style="font-size:12px;color:var(--gray-500);">%</small>
                    @else
                        —
                    @endif
                </div>
                <div class="text-muted" style="font-size: 11px; margin-top: 2px;">
                    Model certainty per prediction
                </div>
            </div>
            <div class="stat-icon" style="width: 40px; height: 40px; font-size: 18px;"><i class="bi bi-activity"></i></div>
        </div>
    </div>
</div>

<!-- PRIMARY PANEL — SPATIAL & TEMPORAL -->
<div class="row g-2 mb-3">
    <div class="col-12">
        <div class="card-custom" style="border-top: 3px solid var(--green); padding: 16px 20px;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-map" style="color: var(--green); font-size: 18px;"></i>
                    <span style="font-size: 14px; font-weight: 700;">Spatial & Temporal Yield Mapping</span>
                </div>
                <span class="text-muted" style="font-size: 11px;">{{ $totalPred }} prediction{{ $totalPred !== 1 ? 's' : '' }}</span>
            </div>

            <div class="row g-2">
                <div class="col-12 col-lg-6">
                    <div style="background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 10px; padding: 10px 14px;">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-clock-history" style="color: var(--green); font-size: 14px;"></i>
                            <span style="font-size: 12px; font-weight: 700;">Temporal — Yield Trend</span>
                        </div>
                        <div style="height: 190px;"><canvas id="trendChart"></canvas></div>
                    </div>
                </div>

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

<!-- LOW YIELD IDENTIFICATION + RECENT -->
<div class="row g-2">
    <div class="col-12 col-lg-5">
        <div class="card-custom" style="padding: 16px 20px; height: 100%;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill" style="color: #c0392b; font-size: 16px;"></i>
                    <span style="font-size: 13px; font-weight: 700;">Low Yield Identification</span>
                </div>
                @if($lowYieldCount > 0)
                    <span style="background: #fef2f2; color: #991b1b; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 6px;">
                        {{ $lowYieldCount }} farm{{ $lowYieldCount !== 1 ? 's' : '' }} · {{ $lowYieldPct }}%
                    </span>
                @else
                    <span style="background: #ecfdf5; color: #065f46; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 6px;">
                        All good
                    </span>
                @endif
            </div>

            <p class="text-muted mb-2" style="font-size: 11px; line-height: 1.4;">
                Farms producing below <strong>87.5%</strong> of their variety's own average yield.
                Worst performers listed first.
            </p>

            @if($lowYieldCount === 0)
                <div class="text-center py-4">
                    <i class="bi bi-check-circle-fill" style="font-size: 38px; color: #27ae60;"></i>
                    <p class="mt-2 mb-0" style="font-size: 12px; color: var(--slate-600);">
                        No low-yield farms detected.
                    </p>
                    <p class="mb-0" style="font-size: 11px; color: var(--slate-400);">
                        All predictions are at or above 87.5% of their variety average.
                    </p>
                </div>
            @else
                <div style="max-height: 210px; overflow-y: auto;">
                    <table class="table table-sm align-middle mb-0" style="font-size: 11px;">
                        <thead>
                            <tr style="position: sticky; top: 0; background: white; z-index: 2; box-shadow: inset 0 -1px 0 var(--gray-200);">
                                <th style="padding: 5px 8px; font-weight: 700; color: var(--slate-500);">Farm</th>
                                <th style="padding: 5px 8px; font-weight: 700; color: var(--slate-500);">Variety</th>
                                <th style="padding: 5px 8px; font-weight: 700; color: var(--slate-500); text-align: right;">Yield</th>
                                <th style="padding: 5px 8px; font-weight: 700; color: var(--slate-500); text-align: right;">Ratio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowYieldPredictions->take(8) as $row)
                                @php
                                    $p    = $row['prediction'];
                                    $farm = $p->farmRecord->farm ?? null;
                                    $var  = $p->farmRecord->riceVariety ?? null;
                                    $ratioPct = round($row['ratio'] * 100, 0);
                                @endphp
                                <tr>
                                    <td style="padding: 5px 8px;">
                                        <strong>{{ $farm->name ?? 'N/A' }}</strong>
                                        <br><small style="color: var(--slate-400);">{{ $farm->barangay ?? '' }}</small>
                                    </td>
                                    <td style="padding: 5px 8px; color: var(--slate-600);">
                                        {{ \Illuminate\Support\Str::limit($var->name ?? 'N/A', 22) }}
                                    </td>
                                    <td style="padding: 5px 8px; text-align: right;">
                                        <strong style="color: #991b1b;">{{ number_format($row['yield'], 2) }}</strong>
                                        <br><small style="color: var(--slate-400);">avg {{ number_format($row['avg'], 2) }}</small>
                                    </td>
                                    <td style="padding: 5px 8px; text-align: right;">
                                        <span style="background: #fef2f2; color: #991b1b; font-weight: 700; padding: 2px 7px; border-radius: 5px; font-size: 10px;">
                                            {{ $ratioPct }}%
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if($lowYieldCount > 8)
                        <div class="text-center pt-2" style="font-size: 11px;">
                            <a href="{{ route('admin.predictions.index') }}"
                               style="color: var(--brand-green); text-decoration: none; font-weight: 600;">
                                View all {{ $lowYieldCount }} low-yield farms →
                            </a>
                        </div>
                    @endif
                </div>
            @endif
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
                            <th style="padding: 6px 10px;">
                                Yield Class
                                <i class="bi bi-question-circle-fill"
                                   style="font-size: 10px; color: var(--gray-400); cursor: help;"
                                   data-bs-toggle="tooltip"
                                   data-bs-placement="top"
                                   data-bs-html="true"
                                   title="<div style='text-align:left; font-size:12px; line-height:1.5;'>
                                       <strong>Variety-relative</strong><br>
                                       High ≥ 112.5% of variety average<br>
                                       Medium 87.5–112.5%<br>
                                       Low below 87.5%<br><br>
                                       <em>Derived in the app — not a model output.</em>
                                   </div>"></i>
                            </th>
                            <th style="padding: 6px 10px;">Confidence</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recent as $index => $pred)
                            @php
                                $yieldVal = $pred->predicted_yield_tons_ha;
                                $conf     = $pred->confidence;
                                $lo       = $pred->yield_lower;
                                $hi       = $pred->yield_upper;

                                $class = $yieldClassOf($pred);
                                $statusClass = match($class) {
                                    'High'   => 'high',
                                    'Low'    => 'low',
                                    default  => 'medium',
                                };
                            @endphp
                            <tr>
                                <td style="padding: 6px 10px;">{{ $index + 1 }}</td>
                                <td style="padding: 6px 10px;">
                                    <strong>{{ $pred->farmRecord->farm->name ?? 'N/A' }}</strong>
                                    <br><small class="text-muted">{{ $pred->farmRecord->farm->barangay ?? '' }}</small>
                                </td>
                                <td style="padding: 6px 10px;">
                                    <small>{{ $pred->farmRecord->riceVariety->name ?? 'N/A' }}</small>
                                </td>
                                <td style="padding: 6px 10px;">
                                    @if($yieldVal !== null)
                                        <strong style="color: #0f4c2b;">{{ number_format($yieldVal, 2) }}</strong>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td style="padding: 6px 10px;">
                                    @if($class)
                                        <span class="badge-status {{ $statusClass }}" style="font-size: 11px; padding: 2px 10px;">
                                            <span class="dot"></span> {{ $class }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td style="padding: 6px 10px;">
                                    @if($conf !== null)
                                        <span class="small"
                                              data-bs-toggle="tooltip"
                                              data-bs-placement="left"
                                              title="{{ $lo !== null && $hi !== null ? '80% interval: ' . number_format($lo, 2) . ' – ' . number_format($hi, 2) . ' t/ha' : '' }}">
                                            <strong style="color: var(--slate-700);">{{ number_format($conf * 100, 1) }}%</strong>
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-3 text-muted" style="font-size: 12px;">
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
    // Tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el, { container: 'body' });
    });

    const GREEN  = '#1b5e3a';
    const GOLD   = '#b8860b';
    const GRAY_L = '#e5e7eb';
    Chart.defaults.font.size = 10;

    // ── Temporal Trend ──
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

    // ── Spatial Barangay Bar ──
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
});
</script>
@endpush