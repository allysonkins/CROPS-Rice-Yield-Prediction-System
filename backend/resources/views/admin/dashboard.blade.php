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

    // ── Variety-relative yield class ──
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

    // ═══════════════════════════════════════════════════════════
    // YEARLY OVERVIEW — for the Compare Years widget
    // ═══════════════════════════════════════════════════════════
    $yearlyData = $uniquePredictions
        ->filter(fn ($p) => $p->farmRecord && $p->farmRecord->year)
        ->groupBy(fn ($p) => (int) $p->farmRecord->year)
        ->map(function ($group, $year) {
            $preds = $group->pluck('predicted_yield_tons_ha')->filter();
            $actuals = $group
                ->map(fn ($p) => $p->farmRecord?->actual_yield_tons_ha)
                ->filter();

            return [
                'year'      => (int) $year,
                'records'   => $group->count(),
                'avgPred'   => $preds->count() > 0 ? round($preds->avg(), 2) : null,
                'avgAct'    => $actuals->count() > 0 ? round($actuals->avg(), 2) : null,
                'delta'     => ($preds->count() > 0 && $actuals->count() > 0)
                    ? round($actuals->avg() - $preds->avg(), 2)
                    : null,
            ];
        })
        ->sortKeys()
        ->values();

    $yearlyRecent = $yearlyData->slice(-5)->values();

    $yearLabels    = $yearlyRecent->pluck('year')->map(fn ($y) => (string) $y);
    $yearPredicted = $yearlyRecent->pluck('avgPred');
    $yearActual    = $yearlyRecent->pluck('avgAct');

    $recent = $uniquePredictions->sortByDesc('created_at')->take(5);
@endphp

@php
    $weatherIcon = 'cloud-sun';
    $weatherDesc = strtolower($weather['description'] ?? '');
    if ($weather) {
        if (str_contains($weatherDesc, 'thunder') || str_contains($weatherDesc, 'storm')) $weatherIcon = 'cloud-lightning-rain';
        elseif (str_contains($weatherDesc, 'drizzle'))                                     $weatherIcon = 'cloud-drizzle';
        elseif (str_contains($weatherDesc, 'rain'))                                        $weatherIcon = 'cloud-rain';
        elseif (str_contains($weatherDesc, 'snow'))                                        $weatherIcon = 'cloud-snow';
        elseif (str_contains($weatherDesc, 'mist') || str_contains($weatherDesc, 'fog'))   $weatherIcon = 'cloud-fog2';
        elseif (str_contains($weatherDesc, 'few clouds') || str_contains($weatherDesc, 'scattered')) $weatherIcon = 'cloud-sun';
        elseif (str_contains($weatherDesc, 'cloud') || str_contains($weatherDesc, 'overcast'))       $weatherIcon = 'clouds';
        elseif (str_contains($weatherDesc, 'clear'))                                       $weatherIcon = 'sun';
    }
@endphp

<!-- ═════════ WELCOME BANNER + WEATHER ═════════ -->
<div class="row g-2 mb-3">
    <div class="col-12">
        <div class="card-custom" style="border-left: 4px solid var(--gold); background: linear-gradient(135deg, #f9fafb 0%, #f0f4f2 100%); padding: 16px 22px;">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <i class="bi bi-person-badge" style="color: var(--gold); font-size: 20px;"></i>
                    <span style="font-size: 15px; font-weight: 700; color: var(--green);">
                        Welcome, {{ auth()->user()->name }}!
                    </span>
                    <span class="text-muted small">· Admin Account</span>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">

                    @if($weather)
                        <div class="d-flex align-items-center gap-3 px-3 py-2"
                             style="background: linear-gradient(135deg, #eef7ff 0%, #f7fbff 100%); border-radius: 10px; border: 1px solid #c7e2f5;">
                            <div style="width: 38px; height: 38px; background: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid #c7e2f5;">
                                <i class="bi bi-{{ $weatherIcon }}" style="color: #0284c7; font-size: 20px;"></i>
                            </div>
                            <div style="line-height: 1.2;">
                                <div style="display: flex; align-items: baseline; gap: 6px;">
                                    <span style="font-size: 18px; font-weight: 800; color: #0c4a6e;">
                                        {{ $weather['temperature'] ?? '—' }}°C
                                    </span>
                                    <span style="font-size: 11px; color: #0369a1; font-weight: 600;">
                                        {{ $weather['description'] ?? '' }}
                                    </span>
                                </div>
                                <div style="font-size: 10px; color: #0369a1; margin-top: 3px; display: flex; flex-wrap: wrap; gap: 8px;">
                                    <span><i class="bi bi-droplet"></i> {{ $weather['humidity'] ?? '—' }}%</span>
                                    <span><i class="bi bi-cloud-rain"></i> {{ $weather['rainfall'] ?? 0 }} mm</span>
                                    <span><i class="bi bi-wind"></i> {{ $weather['wind_speed'] ?? '—' }} km/h</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="d-flex align-items-center gap-2 px-3 py-2"
                             style="background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200);">
                            <i class="bi bi-cloud-slash" style="color: var(--gray-400); font-size: 16px;"></i>
                            <span style="font-size: 11px; color: var(--gray-500);">Weather unavailable</span>
                        </div>
                    @endif

                    <span class="badge bg-light text-muted" style="font-size: 12px; padding: 6px 12px;">
                        <i class="bi bi-clock"></i> {{ now()->format('M d, Y') }}
                    </span>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ═════════ STATS ROW ═════════ -->
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

<!-- ═════════ YEARLY YIELD TREND — COMPARE YEARS WIDGET ═════════ -->
<div class="row g-2 mb-3">
    <div class="col-12">
        <div class="card-custom" style="border-top: 3px solid var(--gold); padding: 16px 20px;">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-calendar-range" style="color: var(--gold); font-size: 18px;"></i>
                    <span style="font-size: 14px; font-weight: 700;">Yearly Yield Trend</span>
                    <span class="text-muted" style="font-size: 11px;">
                        · last {{ $yearlyRecent->count() }} year{{ $yearlyRecent->count() !== 1 ? 's' : '' }}
                    </span>
                </div>
                <a href="{{ route('admin.predictions.compare') }}" class="btn btn-sm btn-outline-success" style="font-size: 12px;">
                    <i class="bi bi-bar-chart-line"></i> Compare Years in Detail →
                </a>
            </div>

            @if($yearlyRecent->isEmpty())
                <div class="text-center py-4">
                    <i class="bi bi-bar-chart" style="font-size: 38px; color: var(--slate-300);"></i>
                    <p class="mt-2 mb-0" style="font-size: 13px; color: var(--slate-500);">
                        No year data available yet.
                    </p>
                    <p class="mb-0" style="font-size: 11px; color: var(--slate-400);">
                        Add farm records across different years to see the trend.
                    </p>
                </div>
            @else
                <div class="row g-3">
                    {{-- Mini trend chart --}}
                    <div class="col-12 col-lg-8">
                        <div style="background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 10px; padding: 12px 14px;">
                            <div style="height: 220px; position: relative;">
                                <canvas id="yearlyChart"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- Compact year table --}}
                    <div class="col-12 col-lg-4">
                        <div style="background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 10px; padding: 12px 14px;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-list-ul" style="color: var(--green); font-size: 13px;"></i>
                                <span style="font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--slate-600);">
                                    Year Summary
                                </span>
                            </div>
                            <table class="table table-sm align-middle mb-0" style="font-size: 11.5px;">
                                <thead>
                                    <tr>
                                        <th style="padding: 5px 6px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.4px; color: var(--slate-500);">Year</th>
                                        <th style="padding: 5px 6px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.4px; color: var(--slate-500); text-align: right;">Predicted</th>
                                        <th style="padding: 5px 6px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.4px; color: var(--slate-500); text-align: right;">Actual</th>
                                        <th style="padding: 5px 6px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.4px; color: var(--slate-500); text-align: right;">Δ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($yearlyRecent as $row)
                                        @php
                                            $isCurrent = (int) $row['year'] === (int) now()->year;
                                        @endphp
                                        <tr>
                                            <td style="padding: 6px;">
                                                <strong style="color: {{ $isCurrent ? 'var(--green)' : 'var(--slate-800)' }};">
                                                    {{ $row['year'] }}
                                                </strong>
                                                @if($isCurrent)
                                                    <span style="font-size: 9px; background: var(--brand-green-light); color: var(--brand-green-dark); padding: 1px 5px; border-radius: 4px; font-weight: 700; margin-left: 3px;">
                                                        NOW
                                                    </span>
                                                @endif
                                                <br>
                                                <small style="color: var(--slate-400); font-size: 10px;">
                                                    {{ $row['records'] }} record{{ $row['records'] !== 1 ? 's' : '' }}
                                                </small>
                                            </td>
                                            <td style="padding: 6px; text-align: right;">
                                                @if($row['avgPred'] !== null)
                                                    <strong style="color: var(--brand-green);">{{ number_format($row['avgPred'], 2) }}</strong>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td style="padding: 6px; text-align: right;">
                                                @if($row['avgAct'] !== null)
                                                    <strong style="color: var(--brand-gold-dark);">{{ number_format($row['avgAct'], 2) }}</strong>
                                                @else
                                                    <span class="text-muted" style="font-size: 10px;">pending</span>
                                                @endif
                                            </td>
                                            <td style="padding: 6px; text-align: right;">
                                                @if($row['delta'] !== null)
                                                    @php
                                                        $deltaColor = $row['delta'] > 0.05 ? '#15803d'
                                                            : ($row['delta'] < -0.05 ? '#b91c1c' : 'var(--slate-500)');
                                                        $deltaIcon = $row['delta'] > 0.05 ? 'arrow-up'
                                                            : ($row['delta'] < -0.05 ? 'arrow-down' : 'dash');
                                                    @endphp
                                                    <span style="color: {{ $deltaColor }}; font-weight: 700; font-size: 11px;">
                                                        <i class="bi bi-{{ $deltaIcon }}"></i>
                                                        {{ $row['delta'] > 0 ? '+' : '' }}{{ number_format($row['delta'], 2) }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Legend --}}
                <div class="d-flex gap-3 flex-wrap mt-3 justify-content-center" style="font-size: 11px; color: var(--slate-500);">
                    <span>
                        <span style="display: inline-block; width: 14px; height: 3px; background: #165b33; border-radius: 2px; vertical-align: middle;"></span>
                        Predicted Yield
                    </span>
                    <span>
                        <span style="display: inline-block; width: 14px; height: 3px; background: #d97706; border-radius: 2px; vertical-align: middle; border-bottom: 1px dashed #d97706;"></span>
                        Actual Harvest
                    </span>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- ═════════ LOW YIELD IDENTIFICATION + RECENT ═════════ -->
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
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el, { container: 'body' });
    });

    const GREEN  = '#1b5e3a';
    const GOLD   = '#b8860b';
    const GRAY_L = '#e5e7eb';
    Chart.defaults.font.size = 10;

    // ── Yearly Trend (Compare Years widget) ──
    const yc = document.getElementById('yearlyChart');
    if (yc) {
        const yearLabels = @json($yearLabels);
        const predData   = @json($yearPredicted);
        const actData    = @json($yearActual);

        new Chart(yc, {
            type: 'line',
            data: {
                labels: yearLabels,
                datasets: [
                    {
                        label: 'Predicted',
                        data: predData,
                        borderColor: '#165b33',
                        backgroundColor: 'rgba(22, 91, 51, 0.10)',
                        borderWidth: 3,
                        pointRadius: 6,
                        pointBackgroundColor: '#165b33',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        tension: 0.25,
                        spanGaps: true,
                        fill: true,
                    },
                    {
                        label: 'Actual',
                        data: actData,
                        borderColor: '#d97706',
                        backgroundColor: 'rgba(217, 119, 6, 0.08)',
                        borderWidth: 3,
                        borderDash: [6, 4],
                        pointRadius: 6,
                        pointBackgroundColor: '#d97706',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        tension: 0.25,
                        spanGaps: false,
                        fill: false,
                    },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.y ?? '—'} t/ha`,
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: { color: GRAY_L },
                        ticks: { font: { size: 10 } },
                        title: { display: true, text: 't/ha', font: { size: 10 } },
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11, weight: '600' } },
                    },
                },
            },
        });
    }
});
</script>
@endpush