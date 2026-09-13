{{-- ============================================================ --}}
{{-- HEADER --}}
{{-- ============================================================ --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h6 class="mb-1">
            <i class="bi bi-clock-history"></i>
            Prediction History — {{ $farmRecord->farm->name ?? 'N/A' }}
        </h6>
        <div class="text-muted small">
            {{ $farmRecord->riceVariety->name ?? 'N/A' }} •
            {{ $farmRecord->season }} •
            {{ $farmRecord->seeding_method ?? 'N/A' }}
        </div>
    </div>
    <span class="badge bg-secondary">{{ $predictions->count() }} prediction{{ $predictions->count() !== 1 ? 's' : '' }}</span>
</div>

@if($predictions->isEmpty())
    <div class="text-center py-4 text-muted">
        <i class="bi bi-inbox" style="font-size: 32px;"></i>
        <p class="mt-2 mb-0">No predictions yet for this farm record.</p>
    </div>
@else
    {{-- ============================================================ --}}
    {{-- SUMMARY CARDS --}}
    {{-- ============================================================ --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="card p-2" style="background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 10px;">
                <div class="text-muted small" style="font-size: 11px;">AVERAGE</div>
                <div style="font-size: 20px; font-weight: 700; color: var(--green);">{{ number_format($analysis['avg'], 2) }}</div>
                <div class="text-muted small">t/ha</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card p-2" style="background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 10px;">
                <div class="text-muted small" style="font-size: 11px;">BEST</div>
                <div style="font-size: 20px; font-weight: 700; color: #16a34a;">{{ number_format($analysis['best'], 2) }}</div>
                <div class="text-muted small">t/ha</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card p-2" style="background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 10px;">
                <div class="text-muted small" style="font-size: 11px;">WORST</div>
                <div style="font-size: 20px; font-weight: 700; color: var(--red);">{{ number_format($analysis['worst'], 2) }}</div>
                <div class="text-muted small">t/ha</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            @php
                $delta = $analysis['delta'];
                $deltaClass = $delta > 0.05 ? 'text-success' : ($delta < -0.05 ? 'text-danger' : 'text-muted');
                $deltaIcon = $delta > 0.05 ? 'arrow-up' : ($delta < -0.05 ? 'arrow-down' : 'dash');
            @endphp
            <div class="card p-2" style="background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 10px;">
                <div class="text-muted small" style="font-size: 11px;">CHANGE (FIRST → LATEST)</div>
                <div style="font-size: 20px; font-weight: 700;" class="{{ $deltaClass }}">
                    <i class="bi bi-{{ $deltaIcon }}"></i>
                    {{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 2) }}
                </div>
                <div class="text-muted small">{{ number_format($analysis['delta_pct'], 1) }}%</div>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- STATUS DISTRIBUTION --}}
    {{-- ============================================================ --}}
    <div class="d-flex gap-2 mb-3">
        <span class="badge-status high" style="font-size: 12px; padding: 4px 12px;">
            <span class="dot"></span> High: {{ $analysis['statusCounts']['high'] }}
        </span>
        <span class="badge-status medium" style="font-size: 12px; padding: 4px 12px;">
            <span class="dot"></span> Medium: {{ $analysis['statusCounts']['medium'] }}
        </span>
        <span class="badge-status low" style="font-size: 12px; padding: 4px 12px;">
            <span class="dot"></span> Low: {{ $analysis['statusCounts']['low'] }}
        </span>
        @if($analysis['maxYield'])
            <span class="badge bg-light text-muted ms-auto" style="font-size: 11px; padding: 4px 12px;">
                Max potential: {{ number_format($analysis['maxYield'], 2) }} t/ha
            </span>
        @endif
    </div>

    {{-- ============================================================ --}}
    {{-- TREND CHART --}}
    {{-- ============================================================ --}}
    @if($predictions->count() > 1)
        <div class="card mb-3" style="background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 10px;">
            <div class="card-body">
                <h6 class="mb-2" style="font-size: 13px;"><i class="bi bi-graph-up"></i> Yield Trend</h6>
                <div style="height: 180px;">
                    <canvas id="predictionTrendChart"></canvas>
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- PREDICTIONS TABLE --}}
    {{-- ============================================================ --}}
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Predicted Yield</th>
                    <th>Change</th>
                    <th>Status</th>
                    <th>Weather</th>
                    <th>Fertilizer</th>
                    <th>Historical Yield</th>
                </tr>
            </thead>
            <tbody>
                @foreach($predictions as $index => $pred)
                    @php
                        $yield = $pred->predicted_yield_tons_ha;

                        // Status relative to variety max
                        $maxYield = $analysis['maxYield'];
                        if ($maxYield !== null && $maxYield > 0) {
                            $ratio = $yield / $maxYield;
                            $statusClass = $ratio >= 0.9 ? 'high' : ($ratio >= 0.7 ? 'medium' : 'low');
                            $statusText = $ratio >= 0.9 ? 'High' : ($ratio >= 0.7 ? 'Medium' : 'Low');
                        } else {
                            $statusClass = $yield >= 4.5 ? 'high' : ($yield >= 3.5 ? 'medium' : 'low');
                            $statusText = $yield >= 4.5 ? 'High' : ($yield >= 3.5 ? 'Medium' : 'Low');
                        }

                        // Change vs previous (next in the display order, which is older)
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
                        <td class="{{ $deltaClass }}" style="font-weight: 600;">
                            <i class="bi bi-{{ $deltaIcon }}"></i> {{ $deltaText }}
                        </td>
                        <td>
                            <span class="badge-status {{ $statusClass }}">
                                <span class="dot"></span> {{ $statusText }}
                            </span>
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

    {{-- ============================================================ --}}
    {{-- TREND CHART SCRIPT --}}
    {{-- ============================================================ --}}
    @if($predictions->count() > 1)
        @php
            // chronological order for chart
            $chartData = $predictions->sortBy('created_at')->values();
            $labels = $chartData->map(fn($p) => $p->created_at->format('M d H:i'))->toArray();
            $yields = $chartData->pluck('predicted_yield_tons_ha')->toArray();
            $maxLine = $analysis['maxYield'] ? array_fill(0, count($labels), $analysis['maxYield']) : null;
        @endphp
        <script>
            (function() {
                const ctx = document.getElementById('predictionTrendChart');
                if (!ctx || typeof Chart === 'undefined') return;
                if (ctx._chartInstance) ctx._chartInstance.destroy();

                const datasets = [{
                    label: 'Predicted Yield (t/ha)',
                    data: @json($yields),
                    borderColor: '#0f4c2b',
                    backgroundColor: 'rgba(15, 76, 43, 0.08)',
                    borderWidth: 2,
                    pointBackgroundColor: '#0f4c2b',
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

                ctx._chartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json($labels),
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: true, position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                            tooltip: { mode: 'index', intersect: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: false,
                                title: { display: true, text: 't/ha', font: { size: 11 } },
                                ticks: { font: { size: 11 } }
                            },
                            x: {
                                ticks: { font: { size: 10 }, maxRotation: 45, minRotation: 0 }
                            }
                        }
                    }
                });
            })();
        </script>
    @endif
@endif