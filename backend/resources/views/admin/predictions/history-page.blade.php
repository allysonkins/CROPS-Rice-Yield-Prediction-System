@extends('layouts.app')

@section('title', 'Prediction History')

@section('content')
@php
    $farmName = $farmRecord->farm->name ?? 'N/A';
    $barangay = $farmRecord->farm->barangay ?? 'N/A';
    $variety  = $farmRecord->riceVariety->name ?? 'N/A';
    $season   = $farmRecord->season ?? 'N/A';
    $year     = $farmRecord->year ?? '';
    $method   = $farmRecord->seeding_method ?? 'N/A';
    $water    = $farmRecord->water_source;

    // Method-specific variety average for yield-class computation
    $methodYield = $farmRecord->riceVariety
        ? $farmRecord->riceVariety->getYieldForMethod($method)
        : null;
    $avgYield = $methodYield->avg ?? ($farmRecord->riceVariety->avg_yield ?? null);
@endphp

{{-- ═══════════ HEADER ═══════════ --}}
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
        <a href="{{ route('admin.predictions.index') }}" class="text-decoration-none"
           style="font-size: 13px; color: var(--slate-500); font-weight: 500;">
            <i class="bi bi-arrow-left"></i> Back to Predictions
        </a>
        <h4 style="color: var(--slate-900); font-weight: 800; margin: 6px 0 4px; letter-spacing: -0.4px;">
        </h4>
        <p class="text-muted mb-0" style="font-size: 12.5px;">
            Every prediction ever generated for this season — with weather details.
        </p>
    </div>
    <span class="badge-count">
        {{ $predictions->count() }} prediction{{ $predictions->count() !== 1 ? 's' : '' }}
    </span>
</div>

{{-- ═══════════ RECORD SUMMARY ═══════════ --}}
<div class="card-custom mb-3" style="border-left: 4px solid var(--brand-green); padding: 16px 22px;">
    <div class="row g-3">
        <div class="col-6 col-md-3">
            <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.5px;">
                Farm
            </div>
            <div style="font-size: 14px; font-weight: 700; color: var(--slate-800); margin-top: 4px;">
                {{ $farmName }}
            </div>
            <div class="text-muted" style="font-size: 12px;">
                <i class="bi bi-geo-alt-fill"></i> {{ $barangay }}
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.5px;">
                Variety
            </div>
            <div style="font-size: 14px; font-weight: 700; color: var(--brand-green); margin-top: 4px;">
                {{ $variety }}
            </div>
            <div class="text-muted" style="font-size: 12px;">
                {{ $farmRecord->riceVariety->classification ?? '—' }}
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.5px;">
                Season
            </div>
            <div style="font-size: 14px; font-weight: 700; color: var(--slate-800); margin-top: 4px;">
                {{ $season }}{{ $year ? ' ' . $year : '' }}
            </div>
            <div class="text-muted" style="font-size: 12px;">
                {{ $method }}
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.5px;">
                Water Source
            </div>
            <div class="mt-1">
                @if($water === 'Rainfed')
                    <span class="badge-status" style="background: var(--brand-gold-light); color: #92400e; border-color: #fde68a;">
                        <span class="dot" style="background: var(--brand-gold);"></span> Rainfed
                    </span>
                @elseif($water === 'Irrigated')
                    <span class="badge-status" style="background: #dbeafe; color: #1e40af; border-color: #bfdbfe;">
                        <span class="dot" style="background: #1d4ed8;"></span> Irrigated
                    </span>
                @elseif($water === 'Both')
                    <span class="badge-status" style="background: var(--brand-green-light); color: var(--brand-green-dark); border-color: #c7e6d2;">
                        <span class="dot" style="background: var(--brand-green);"></span> Both
                    </span>
                @else
                    <span class="text-muted" style="font-size: 12px;">—</span>
                @endif
            </div>
        </div>
    </div>
</div>

@if($predictions->isEmpty())
    <div class="card-custom text-center py-5">
        <i class="bi bi-inbox" style="font-size: 42px; color: var(--slate-300);"></i>
        <h6 class="mt-3 mb-1" style="color: var(--slate-700); font-weight: 700;">
            No predictions yet
        </h6>
        <p class="mb-0" style="font-size: 13px; color: var(--slate-500);">
            This farm record doesn't have any predictions yet.
        </p>
    </div>
@else

{{-- ═══════════ STATS ═══════════ --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="stat-card" style="border-left-color: var(--brand-green);">
            <div class="stat-info">
                <div class="label" style="text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;">
                    Average
                </div>
                <div class="value" style="font-size: 24px;">
                    {{ number_format($analysis['avg'], 2) }}
                    <span style="font-size: 13px; color: var(--slate-400);">t/ha</span>
                </div>
                <div class="text-muted" style="font-size: 11px;">
                    {{ number_format($analysis['avg'] * 20, 0) }} cavan/ha
                </div>
            </div>
            <div class="stat-icon"><i class="bi bi-graph-up-arrow" style="color: var(--brand-green);"></i></div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="stat-card" style="border-left-color: #16a34a;">
            <div class="stat-info">
                <div class="label" style="text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;">
                    Best
                </div>
                <div class="value" style="font-size: 24px;">
                    {{ number_format($analysis['best'], 2) }}
                    <span style="font-size: 13px; color: var(--slate-400);">t/ha</span>
                </div>
                <div class="text-muted" style="font-size: 11px;">
                    {{ number_format($analysis['best'] * 20, 0) }} cavan/ha
                </div>
            </div>
            <div class="stat-icon"><i class="bi bi-award-fill" style="color: #16a34a;"></i></div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="stat-card" style="border-left-color: var(--brand-danger);">
            <div class="stat-info">
                <div class="label" style="text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;">
                    Lowest
                </div>
                <div class="value" style="font-size: 24px;">
                    {{ number_format($analysis['worst'], 2) }}
                    <span style="font-size: 13px; color: var(--slate-400);">t/ha</span>
                </div>
                <div class="text-muted" style="font-size: 11px;">
                    {{ number_format($analysis['worst'] * 20, 0) }} cavan/ha
                </div>
            </div>
            <div class="stat-icon"><i class="bi bi-graph-down-arrow" style="color: var(--brand-danger);"></i></div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        @php
            $delta      = $analysis['delta'];
            $deltaColor = $delta > 0.05 ? '#15803d' : ($delta < -0.05 ? '#b91c1c' : 'var(--slate-500)');
            $deltaIcon  = $delta > 0.05 ? 'arrow-up' : ($delta < -0.05 ? 'arrow-down' : 'dash');
            $deltaBorder= $delta > 0.05 ? '#15803d' : ($delta < -0.05 ? '#b91c1c' : 'var(--slate-400)');
        @endphp
        <div class="stat-card" style="border-left-color: {{ $deltaBorder }};">
            <div class="stat-info">
                <div class="label" style="text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;">
                    Change (First → Latest)
                </div>
                <div class="value" style="font-size: 24px; color: {{ $deltaColor }};">
                    <i class="bi bi-{{ $deltaIcon }}"></i>
                    {{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 2) }}
                </div>
                <div class="text-muted" style="font-size: 11px;">
                    {{ number_format($analysis['delta_pct'], 1) }}% from first
                </div>
            </div>
            <div class="stat-icon"><i class="bi bi-arrow-repeat" style="color: {{ $deltaColor }};"></i></div>
        </div>
    </div>
</div>

@if($analysis['maxYield'])
    <div class="card-custom mb-3" style="padding: 12px 20px; border-left: 4px solid var(--brand-gold);">
        <span class="text-muted" style="font-size: 12px;">
            <i class="bi bi-award" style="color: var(--brand-gold);"></i>
            Variety max potential for <strong>{{ $method }}</strong>:
            <strong style="color: var(--slate-800);">{{ number_format($analysis['maxYield'], 2) }} t/ha</strong>
            <span style="color: var(--slate-400);">({{ number_format($analysis['maxYield'] * 20, 0) }} cavan/ha)</span>
        </span>
    </div>
@endif

{{-- ═══════════ TREND CHART ═══════════ --}}
@if($predictions->count() > 1)
    <div class="card-custom mb-3">
        <div class="card-title d-flex justify-content-between align-items-center">
            <span><i class="bi bi-graph-up-arrow"></i> Yield Trend</span>
            <span class="text-muted" style="font-size: 11px;">
                {{ $predictions->count() }} points · {{ $predictions->sortBy('created_at')->first()->created_at->format('M d, Y') }} → {{ $predictions->sortByDesc('created_at')->first()->created_at->format('M d, Y') }}
            </span>
        </div>
        <div style="height: 260px;">
            <canvas id="predictionTrendChart"></canvas>
        </div>
    </div>
@endif

{{-- ═══════════ TABLE ═══════════ --}}
<div class="card-custom">
    <div class="card-title">
        <i class="bi bi-table"></i> All Predictions
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 40px;">#</th>
                    <th>Date</th>
                    <th>Predicted Yield</th>
                    <th>
                        Yield Class
                        <i class="bi bi-question-circle-fill"
                           style="font-size: 12px; color: var(--slate-400); cursor: help;"
                           data-bs-toggle="tooltip"
                           data-bs-placement="top"
                           data-bs-html="true"
                           title="<div style='text-align:left; font-size:12px; line-height:1.5;'>
                               <strong>Variety-relative class</strong><br>
                               Compared against the variety's <em>own</em> average yield for the chosen seeding method.<br><br>
                               • <strong>High</strong> — ≥ 112.5% of variety average<br>
                               • <strong>Medium</strong> — 87.5% – 112.5%<br>
                               • <strong>Low</strong> — below 87.5%<br><br>
                               <em>Derived in the application — not a model output.</em>
                           </div>"></i>
                    </th>
                    <th>
                        Confidence
                        <i class="bi bi-question-circle-fill"
                           style="font-size: 12px; color: var(--slate-400); cursor: help;"
                           data-bs-toggle="tooltip"
                           data-bs-placement="top"
                           data-bs-html="true"
                           title="<div style='text-align:left; font-size:12px; line-height:1.5;'>
                               <strong>How confidence works</strong><br>
                               The model predicts an 80% interval from two quantile models. Confidence = 1 − (half-width ÷ predicted yield).<br>
                               Narrow interval → high confidence.
                           </div>"></i>
                    </th>
                    <th>Change</th>
                    <th>Weather</th>
                </tr>
            </thead>
            <tbody>
                @foreach($predictions as $index => $pred)
                    @php
                        $yield      = $pred->predicted_yield_tons_ha;
                        $yieldCavan = $yield * 20;
                        $conf       = $pred->confidence;
                        $lo         = $pred->yield_lower;
                        $hi         = $pred->yield_upper;

                        // ── App-level yield class ──
                        $yieldClass = null;
                        $ratio      = null;
                        if ($avgYield && $avgYield > 0) {
                            $ratio = $yield / $avgYield;
                            if ($ratio >= 1.125)     $yieldClass = 'High';
                            elseif ($ratio >= 0.875) $yieldClass = 'Medium';
                            else                     $yieldClass = 'Low';
                        }
                        $clsBadge = match($yieldClass) {
                            'High'   => 'high',
                            'Medium' => 'medium',
                            'Low'    => 'low',
                            default  => 'medium',
                        };

                        $prev = $predictions[$index + 1] ?? null;
                        if ($prev) {
                            $delta      = $yield - $prev->predicted_yield_tons_ha;
                            $deltaClass = $delta > 0.05 ? 'text-success' : ($delta < -0.05 ? 'text-danger' : 'text-muted');
                            $deltaIcon  = $delta > 0.05 ? 'arrow-up' : ($delta < -0.05 ? 'arrow-down' : 'dash');
                            $deltaText  = ($delta > 0 ? '+' : '') . number_format($delta, 2);
                        } else {
                            $deltaClass = 'text-muted';
                            $deltaIcon  = 'dash';
                            $deltaText  = '—';
                        }

                        $features = json_decode($pred->input_features, true);
                        $weather  = $features['weather'] ?? [];
                    @endphp
                    <tr>
                        <td style="color: var(--slate-400); font-weight: 700;">{{ $index + 1 }}</td>
                        <td>
                            <strong style="color: var(--slate-800);">{{ $pred->created_at->format('M d, Y') }}</strong>
                            <br><small class="text-muted">{{ $pred->created_at->format('H:i') }} · {{ $pred->created_at->diffForHumans() }}</small>
                        </td>
                        <td>
                            <strong style="color: var(--brand-green); font-size: 14px;">{{ number_format($yield, 2) }}</strong>
                            <small class="text-muted">t/ha</small>
                            <br>
                            <small style="color: var(--slate-500);">{{ number_format($yieldCavan, 0) }} cavan/ha</small>
                        </td>
                        <td>
                            @if($yieldClass)
                                <span class="badge-status {{ $clsBadge }}"
                                      style="cursor: help;"
                                      data-bs-toggle="tooltip"
                                      data-bs-placement="top"
                                      data-bs-html="true"
                                      title="<div style='text-align:left; font-size:12px; line-height:1.5;'>
                                          <strong>Variety-relative class</strong><br>
                                          Predicted: <strong>{{ number_format($yield, 2) }} t/ha</strong><br>
                                          Variety average ({{ $method }}): <strong>{{ number_format($avgYield, 2) }} t/ha</strong><br>
                                          Ratio: <strong>{{ number_format($ratio * 100, 1) }}%</strong> of average
                                      </div>">
                                    <span class="dot"></span> {{ $yieldClass }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($conf !== null)
                                <strong style="color: var(--slate-700);">{{ number_format($conf * 100, 1) }}%</strong>
                                @if($lo !== null && $hi !== null)
                                    <br><small class="text-muted">{{ number_format($lo, 2) }}–{{ number_format($hi, 2) }} t/ha</small>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="{{ $deltaClass }}" style="font-weight: 600;">
                            <i class="bi bi-{{ $deltaIcon }}"></i> {{ $deltaText }}
                        </td>
                        <td>
                            <small style="color: var(--slate-600); line-height: 1.7;">
                                <i class="bi bi-thermometer-half"></i> {{ $weather['temperature'] ?? '—' }}°C
                                &nbsp;·&nbsp;
                                <i class="bi bi-droplet"></i> {{ $weather['humidity'] ?? '—' }}%
                                <br>
                                <i class="bi bi-cloud-rain"></i> {{ $weather['rainfall'] ?? 0 }} mm
                                @if(!empty($weather['source']))
                                    <span class="text-muted">· {{ $weather['source'] }}</span>
                                @endif
                            </small>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

@push('scripts')
@if(!$predictions->isEmpty() && $predictions->count() > 1)
@php
    $chartData = $predictions->sortBy('created_at')->values();
    $labels    = $chartData->map(fn($p) => $p->created_at->format('M d H:i'))->toArray();
    $yields    = $chartData->pluck('predicted_yield_tons_ha')->toArray();
    $maxLine   = $analysis['maxYield'] ? array_fill(0, count($labels), $analysis['maxYield']) : null;
@endphp
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el, { container: 'body' });
    });

    const ctx = document.getElementById('predictionTrendChart');
    if (!ctx || typeof Chart === 'undefined') return;

    const datasets = [{
        label: 'Predicted Yield (t/ha)',
        data: @json($yields),
        borderColor: '#165b33',
        backgroundColor: 'rgba(22, 91, 51, 0.10)',
        borderWidth: 2.5,
        pointBackgroundColor: '#165b33',
        pointBorderColor: 'white',
        pointBorderWidth: 2,
        pointRadius: 5,
        tension: 0.3,
        fill: true,
    }];

    @if($maxLine)
        datasets.push({
            label: 'Variety Max (t/ha)',
            data: @json($maxLine),
            borderColor: '#d97706',
            borderWidth: 1.5,
            borderDash: [6, 4],
            pointRadius: 0,
            fill: false,
        });
    @endif

    new Chart(ctx, {
        type: 'line',
        data: { labels: @json($labels), datasets: datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 11 } }
                },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 't/ha', font: { size: 11 } },
                    ticks: { font: { size: 11 } },
                    grid: { color: '#e2e8f0' }
                },
                x: {
                    ticks: { font: { size: 10 }, maxRotation: 45 },
                    grid: { display: false }
                }
            }
        }
    });
});
</script>
@endif
@endpush