@if($predictions->isEmpty())
    <div class="card-custom text-center py-5">
        <i class="bi bi-inbox" style="font-size: 42px; color: var(--slate-300);"></i>
        <h6 class="mt-3 mb-1" style="color: var(--slate-700); font-weight: 700;">No predictions yet</h6>
        <p style="font-size: 13px; color: var(--slate-500); margin: 0;">
            No predictions have been generated for this farm record.
        </p>
    </div>
@else

    @php
        // Convert every yield in the collection once, so we don't repeat the helper
        $yieldsCavan = $predictions->map(fn($p) => t_ha_to_cavan_ha((float) $p->predicted_yield_tons_ha))->values();

        $avgCavan     = $yieldsCavan->avg();
        $bestCavan    = $yieldsCavan->max();
        $worstCavan   = $yieldsCavan->min();
        $firstCavan   = $yieldsCavan->last();  // collection is newest-first, so last = oldest
        $latestCavan  = $yieldsCavan->first();
        $deltaCavan   = $latestCavan - $firstCavan;
        $deltaPct     = $firstCavan > 0 ? ($deltaCavan / $firstCavan) * 100 : 0;
        $deltaColor   = $deltaCavan > 1 ? 'var(--brand-green)' : ($deltaCavan < -1 ? 'var(--brand-danger)' : 'var(--slate-400)');
        $deltaIcon    = $deltaCavan > 1 ? 'arrow-up' : ($deltaCavan < -1 ? 'arrow-down' : 'dash');

        $maxYieldTons  = $analysis['maxYield'];
        $maxCavan      = $maxYieldTons !== null ? t_ha_to_cavan_ha((float) $maxYieldTons) : null;

        $farmArea = (float) ($farmRecord->farm->land_area_ha ?? 1.0);
    @endphp

    {{-- SUMMARY CARDS --}}
    <div class="row g-2 mb-4">
        <div class="col-6 col-md-3">
            <div class="card-custom" style="padding: 16px 20px;">
                <div style="font-size: 10px; font-weight: 700; color: var(--slate-500); text-transform: uppercase; letter-spacing: 0.5px;">Average</div>
                <div style="font-size: 24px; font-weight: 800; color: var(--brand-green); line-height: 1.1; margin-top: 4px;">
                    {{ number_format($avgCavan, 0) }}
                </div>
                <div style="font-size: 11px; color: var(--slate-500);">cavan/ha</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-custom" style="padding: 16px 20px;">
                <div style="font-size: 10px; font-weight: 700; color: var(--slate-500); text-transform: uppercase; letter-spacing: 0.5px;">Best</div>
                <div style="font-size: 24px; font-weight: 800; color: var(--brand-green); line-height: 1.1; margin-top: 4px;">
                    {{ number_format($bestCavan, 0) }}
                </div>
                <div style="font-size: 11px; color: var(--slate-500);">cavan/ha</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-custom" style="padding: 16px 20px;">
                <div style="font-size: 10px; font-weight: 700; color: var(--slate-500); text-transform: uppercase; letter-spacing: 0.5px;">Lowest</div>
                <div style="font-size: 24px; font-weight: 800; color: var(--brand-danger); line-height: 1.1; margin-top: 4px;">
                    {{ number_format($worstCavan, 0) }}
                </div>
                <div style="font-size: 11px; color: var(--slate-500);">cavan/ha</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-custom" style="padding: 16px 20px;">
                <div style="font-size: 10px; font-weight: 700; color: var(--slate-500); text-transform: uppercase; letter-spacing: 0.5px;">Change</div>
                <div style="font-size: 24px; font-weight: 800; color: {{ $deltaColor }}; line-height: 1.1; margin-top: 4px;">
                    <i class="bi bi-{{ $deltaIcon }}"></i>
                    {{ $deltaCavan > 0 ? '+' : '' }}{{ number_format($deltaCavan, 0) }}
                </div>
                <div style="font-size: 11px; color: var(--slate-500);">{{ number_format($deltaPct, 1) }}% from first</div>
            </div>
        </div>
    </div>

    {{-- STATUS DISTRIBUTION --}}
    <div class="card-custom" style="padding: 14px 20px;">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge-status" style="background: var(--brand-green-light); color: var(--brand-green-dark); border-color: #c7e6d2; font-size: 12px; padding: 5px 14px;">
                <span class="dot" style="background: var(--brand-green);"></span>
                High: {{ $analysis['statusCounts']['high'] }}
            </span>
            <span class="badge-status" style="background: var(--brand-gold-light); color: #92400e; border-color: #fde68a; font-size: 12px; padding: 5px 14px;">
                <span class="dot" style="background: var(--brand-gold);"></span>
                Medium: {{ $analysis['statusCounts']['medium'] }}
            </span>
            <span class="badge-status" style="background: var(--brand-danger-light); color: #991b1b; border-color: #fecaca; font-size: 12px; padding: 5px 14px;">
                <span class="dot" style="background: var(--brand-danger);"></span>
                Low: {{ $analysis['statusCounts']['low'] }}
            </span>
            @if($maxCavan)
                <span class="badge-status ms-auto" style="background: var(--slate-100); color: var(--slate-700); border-color: var(--slate-200); font-size: 11px; padding: 5px 14px;">
                    <i class="bi bi-trophy"></i>
                    Max potential: {{ number_format($maxCavan, 0) }} cavan/ha
                </span>
            @endif
        </div>
    </div>

    {{-- TREND CHART --}}
    @if($predictions->count() > 1)
        <div class="card-custom">
            <div class="card-title" style="font-size: 14px;">
                <i class="bi bi-graph-up"></i> Yield Trend
            </div>
            <div style="height: 280px;">
                <canvas id="farmerPredictionTrendChart"></canvas>
            </div>
        </div>
    @endif

    {{-- PREDICTIONS TABLE --}}
    <div class="card-custom">
        <div class="card-title">
            <i class="bi bi-table"></i> All Predictions
            <span class="badge-status ms-auto"
                  style="background: var(--slate-100); color: var(--slate-700); border-color: var(--slate-200); font-size: 11px;">
                {{ $predictions->count() }} record{{ $predictions->count() !== 1 ? 's' : '' }}
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Estimated Yield</th>
                        <th>Change</th>
                        <th>Class</th>
                        <th>Weather</th>
                        <th>Fertilizer</th>
                        <th>Historical Yield</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($predictions as $index => $pred)
                        @php
                            $yieldTons  = (float) $pred->predicted_yield_tons_ha;
                            $yieldCavan = t_ha_to_cavan_ha($yieldTons);
                            $class      = $pred->predicted_class ?? 'Medium';

                            $statusClass = match($class) {
                                'High' => 'high',
                                'Low'  => 'low',
                                default => 'medium',
                            };

                            $prev = $predictions[$index + 1] ?? null;
                            if ($prev) {
                                $deltaCavanRow = $yieldCavan - t_ha_to_cavan_ha((float) $prev->predicted_yield_tons_ha);
                                $deltaColorRow = $deltaCavanRow > 1 ? 'var(--brand-green)' : ($deltaCavanRow < -1 ? 'var(--brand-danger)' : 'var(--slate-400)');
                                $deltaIconRow  = $deltaCavanRow > 1 ? 'arrow-up' : ($deltaCavanRow < -1 ? 'arrow-down' : 'dash');
                                $deltaTextRow  = ($deltaCavanRow > 0 ? '+' : '') . number_format($deltaCavanRow, 0);
                            } else {
                                $deltaColorRow = 'var(--slate-400)';
                                $deltaIconRow  = 'dash';
                                $deltaTextRow  = '—';
                            }

                            $features = json_decode($pred->input_features, true);
                            $weather = $features['weather'] ?? [];
                            $input = $features['input'] ?? [];
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <div style="font-size: 13px;">{{ $pred->created_at->format('M d, Y') }}</div>
                                <small style="color: var(--slate-500);">{{ $pred->created_at->format('H:i') }} · {{ $pred->created_at->diffForHumans() }}</small>
                            </td>
                            <td>
                                <strong style="color: var(--brand-green-dark);">{{ number_format($yieldCavan, 0) }}</strong>
                                <small style="color: var(--slate-500);">cavan/ha</small>
                            </td>
                            <td style="color: {{ $deltaColorRow }}; font-weight: 600;">
                                <i class="bi bi-{{ $deltaIconRow }}"></i> {{ $deltaTextRow }}
                            </td>
                            <td>
                                <span class="badge-status {{ $statusClass }}">
                                    <span class="dot"></span> {{ $class }}
                                </span>
                            </td>
                            <td>
                                <small style="color: var(--slate-600);">
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

    {{-- TREND CHART SCRIPT --}}
    @if($predictions->count() > 1)
        @php
            $chartData   = $predictions->sortBy('created_at')->values();
            $labels      = $chartData->map(fn($p) => $p->created_at->format('M d H:i'))->toArray();
            $yieldsChart = $chartData->map(fn($p) => t_ha_to_cavan_ha((float) $p->predicted_yield_tons_ha))->toArray();
            $maxLine     = $maxCavan ? array_fill(0, count($labels), $maxCavan) : null;

            // Compute suggested Y-axis bounds so the line doesn't sit on zero
            $allPoints    = $maxLine ? array_merge($yieldsChart, $maxLine) : $yieldsChart;
            $chartMin     = min($allPoints);
            $chartMax     = max($allPoints);
            $range        = max($chartMax - $chartMin, 1);
            $suggestedMin = max(0, floor($chartMin - $range * 0.15));
            $suggestedMax = ceil($chartMax + $range * 0.15);
        @endphp
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const ctx = document.getElementById('farmerPredictionTrendChart');
                if (!ctx || typeof Chart === 'undefined') return;
                if (ctx._chartInstance) ctx._chartInstance.destroy();

                const datasets = [{
                    label: 'Estimated Yield (cavan/ha)',
                    data: @json($yieldsChart),
                    borderColor: '#165b33',
                    backgroundColor: 'rgba(22, 91, 51, 0.08)',
                    borderWidth: 2,
                    pointBackgroundColor: '#165b33',
                    pointRadius: 5,
                    tension: 0.3,
                    fill: true,
                }];

                @if($maxLine)
                    datasets.push({
                        label: 'Variety Max (cavan/ha)',
                        data: @json($maxLine),
                        borderColor: '#d97706',
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
                                suggestedMin: {{ $suggestedMin }},
                                suggestedMax: {{ $suggestedMax }},
                                title: { display: true, text: 'cavan/ha', font: { size: 11 } },
                                ticks: { font: { size: 11 } }
                            },
                            x: {
                                ticks: { font: { size: 10 }, maxRotation: 45, minRotation: 0 }
                            }
                        }
                    }
                });
            });
        </script>
    @endif

@endif