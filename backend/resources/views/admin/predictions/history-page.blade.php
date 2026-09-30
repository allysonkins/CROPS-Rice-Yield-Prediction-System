@extends('layouts.app')

@section('title', 'Prediction History')

@section('content')
@php
    $farmName = $farmRecord->farm->name ?? 'N/A';
    $barangay = $farmRecord->farm->barangay ?? 'N/A';
    $variety  = $farmRecord->riceVariety->name ?? 'N/A';
    $season   = $farmRecord->season ?? 'N/A';
    $method   = $farmRecord->seeding_method ?? 'N/A';
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
    <div>
        <a href="{{ route('admin.predictions.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Back to Predictions
        </a>
        <h4 style="color: var(--green-dark); font-weight: 800; margin-bottom: 4px;">
            <i class="bi bi-clock-history"></i> Prediction History
        </h4>
        <div class="text-muted" style="font-size: 14px;">
            <strong>{{ $farmName }}</strong> · {{ $barangay }} · {{ $variety }} · {{ $season }} · {{ $method }}
        </div>
    </div>
    <span class="badge bg-secondary" style="font-size: 13px; padding: 8px 16px;">
        {{ $predictions->count() }} prediction{{ $predictions->count() !== 1 ? 's' : '' }}
    </span>
</div>

@if($predictions->isEmpty())
    <div class="card-custom text-center py-5">
        <i class="bi bi-inbox" style="font-size: 42px; color: var(--gray-400);"></i>
        <p class="mt-3 mb-0">No predictions yet for this farm record.</p>
    </div>
@else

<div class="row g-2 mb-4">
    <div class="col-6 col-md-3">
        <div class="card-custom" style="padding: 16px 20px;">
            <div class="text-muted small" style="font-size: 11px; font-weight: 600;">AVERAGE</div>
            <div style="font-size: 26px; font-weight: 800; color: var(--green);">{{ number_format($analysis['avg'], 2) }}</div>
            <div class="text-muted small">t/ha</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-custom" style="padding: 16px 20px;">
            <div class="text-muted small" style="font-size: 11px; font-weight: 600;">BEST</div>
            <div style="font-size: 26px; font-weight: 800; color: #16a34a;">{{ number_format($analysis['best'], 2) }}</div>
            <div class="text-muted small">t/ha</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-custom" style="padding: 16px 20px;">
            <div class="text-muted small" style="font-size: 11px; font-weight: 600;">WORST</div>
            <div style="font-size: 26px; font-weight: 800; color: var(--red);">{{ number_format($analysis['worst'], 2) }}</div>
            <div class="text-muted small">t/ha</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        @php
            $delta = $analysis['delta'];
            $deltaClass = $delta > 0.05 ? 'text-success' : ($delta < -0.05 ? 'text-danger' : 'text-muted');
            $deltaIcon = $delta > 0.05 ? 'arrow-up' : ($delta < -0.05 ? 'arrow-down' : 'dash');
        @endphp
        <div class="card-custom" style="padding: 16px 20px;">
            <div class="text-muted small" style="font-size: 11px; font-weight: 600;">CHANGE (FIRST → LATEST)</div>
            <div style="font-size: 26px; font-weight: 800;" class="{{ $deltaClass }}">
                <i class="bi bi-{{ $deltaIcon }}"></i>
                {{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 2) }}
            </div>
            <div class="text-muted small">{{ number_format($analysis['delta_pct'], 1) }}%</div>
        </div>
    </div>
</div>

@if($analysis['maxYield'])
    <div class="card-custom mb-4" style="padding: 12px 20px;">
        <span class="text-muted" style="font-size: 12px;">
            <i class="bi bi-trophy"></i>
            Variety max potential: <strong>{{ number_format($analysis['maxYield'], 2) }} t/ha</strong>
        </span>
    </div>
@endif

@if($predictions->count() > 1)
    <div class="card-custom mb-4">
        <div class="card-title" style="font-size: 14px;"><i class="bi bi-graph-up"></i> Yield Trend</div>
        <div style="height: 260px;">
            <canvas id="predictionTrendChart"></canvas>
        </div>
    </div>
@endif

@php
    // Compute variety average for this farm record (used by every row)
    $methodYield = $farmRecord->riceVariety
        ? $farmRecord->riceVariety->getYieldForMethod($method)
        : null;
    $avgYield = $methodYield->avg ?? ($farmRecord->riceVariety->avg_yield ?? null);
@endphp

<div class="card-custom">
    <div class="card-title"><i class="bi bi-table"></i> All Predictions</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Predicted Yield</th>
                    <th>
                        Yield Class
                        <i class="bi bi-question-circle-fill"
                           style="font-size: 12px; color: var(--gray-400); cursor: help;"
                           data-bs-toggle="tooltip"
                           data-bs-placement="top"
                           data-bs-html="true"
                           title="<div style='text-align:left; font-size:12px; line-height:1.5;'>
                               <strong>Variety-relative class</strong><br>
                               Compared against the variety's <em>own</em> average yield for the chosen seeding method.<br><br>
                               <strong>Bands:</strong><br>
                               • <strong>High</strong> — ≥ 112.5% of variety average<br>
                               • <strong>Medium</strong> — 87.5% – 112.5%<br>
                               • <strong>Low</strong> — below 87.5%<br><br>
                               <em>Derived in the application — not a model output.</em>
                           </div>"></i>
                    </th>
                    <th>Confidence</th>
                    <th>Change</th>
                    <th>Weather</th>
                    <th>Fertilizer</th>
                    <th>Historical Yield</th>
                </tr>
            </thead>
            <tbody>
                @foreach($predictions as $index => $pred)
                    @php
                        $yield = $pred->predicted_yield_tons_ha;
                        $conf  = $pred->confidence;
                        $lo    = $pred->yield_lower;
                        $hi    = $pred->yield_upper;

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
                            $delta = $yield - $prev->predicted_yield_tons_ha;
                            $deltaClass = $delta > 0.05 ? 'text-success' : ($delta < -0.05 ? 'text-danger' : 'text-muted');
                            $deltaIcon = $delta > 0.05 ? 'arrow-up' : ($delta < -0.05 ? 'arrow-down' : 'dash');
                            $deltaText = ($delta > 0 ? '+' : '') . number_format($delta, 2);
                        } else {
                            $deltaClass = 'text-muted';
                            $deltaIcon = 'dash';
                            $deltaText = '—';
                        }

                        $features = json_decode($pred->input_features, true);
                        $weather = $features['weather'] ?? [];
                        $input = $features['input'] ?? [];
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div>{{ $pred->created_at->format('M d, Y') }}</div>
                            <small class="text-muted">{{ $pred->created_at->format('H:i') }} ({{ $pred->created_at->diffForHumans() }})</small>
                        </td>
                        <td><strong style="color: #0f4c2b;">{{ number_format($yield, 2) }} t/ha</strong></td>
                        <td>
                            @if($yieldClass)
                                <span class="badge-status {{ $clsBadge }}"
                                      style="cursor: help;"
                                      data-bs-toggle="tooltip"
                                      data-bs-placement="top"
                                      data-bs-html="true"
                                      title="<div style='text-align:left; font-size:12px; line-height:1.5;'>
                                          <strong>Variety-relative class</strong><br>
                                          Predicted yield: <strong>{{ number_format($yield, 2) }} t/ha</strong><br>
                                          Variety average ({{ $method }}): <strong>{{ number_format($avgYield, 2) }} t/ha</strong><br>
                                          Ratio: <strong>{{ number_format($ratio * 100, 1) }}%</strong> of average<br><br>
                                          <strong>Bands:</strong><br>
                                          • High — ≥ 112.5%<br>
                                          • Medium — 87.5% – 112.5%<br>
                                          • Low — below 87.5%
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
                                    <br><small class="text-muted">{{ number_format($lo, 2) }}–{{ number_format($hi, 2) }}</small>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="{{ $deltaClass }}" style="font-weight: 600;">
                            <i class="bi bi-{{ $deltaIcon }}"></i> {{ $deltaText }}
                        </td>
                        <td>
                            <small>
                                <i class="bi bi-thermometer-half"></i> {{ $weather['temperature'] ?? 'N/A' }}°C<br>
                                <i class="bi bi-droplet"></i> {{ $weather['humidity'] ?? 'N/A' }}%<br>
                                <i class="bi bi-cloud-rain"></i> {{ $weather['rainfall'] ?? 0 }} mm
                            </small>
                        </td>
                        <td>{{ $input['fertilizer_kg_ha'] ?? 'N/A' }} kg/ha</td>
                        <td>{{ $input['historical_yield_tons_ha'] ?? 'N/A' }} t/ha</td>
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
    $labels = $chartData->map(fn($p) => $p->created_at->format('M d H:i'))->toArray();
    $yields = $chartData->pluck('predicted_yield_tons_ha')->toArray();
    $maxLine = $analysis['maxYield'] ? array_fill(0, count($labels), $analysis['maxYield']) : null;
@endphp
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el, { container: 'body' });
    });

    const ctx = document.getElementById('predictionTrendChart');
    if (!ctx || typeof Chart === 'undefined') return;

    const datasets = [{
        label: 'Predicted Yield (t/ha)',
        data: @json($yields),
        borderColor: '#0f4c2b',
        backgroundColor: 'rgba(15, 76, 43, 0.08)',
        borderWidth: 2.5,
        pointBackgroundColor: '#0f4c2b',
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
            borderColor: '#b8860b',
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
                legend: { display: true, position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 't/ha', font: { size: 11 } },
                    ticks: { font: { size: 11 } }
                },
                x: { ticks: { font: { size: 10 }, maxRotation: 45 } }
            }
        }
    });
});
</script>
@endif
@endpush