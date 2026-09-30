@php
    $expected = null;
    if ($farmRecord->riceVariety && $farmRecord->seeding_method) {
        $expected = $farmRecord->riceVariety->getYieldForMethod($farmRecord->seeding_method);
    }
    $latest = $farmRecord->predictions->sortByDesc('created_at')->first();

    // ── Convert expected (t/ha) → cavan/ha ──
    $expectedAvgCavan = ($expected && $expected->avg !== null) ? $expected->avg * 20 : null;
    $expectedMaxCavan = ($expected && $expected->max !== null) ? $expected->max * 20 : null;

    // ── Latest prediction (t/ha → cavan/ha) ──
    $predictedTons  = $latest?->predicted_yield_tons_ha;
    $predictedCavan = $predictedTons !== null ? t_ha_to_cavan_ha((float) $predictedTons) : null;
    $conf           = $latest?->confidence;
    $loTons         = $latest?->yield_lower;
    $hiTons         = $latest?->yield_upper;
    $loCavan        = $loTons !== null ? t_ha_to_cavan_ha((float) $loTons) : null;
    $hiCavan        = $hiTons !== null ? t_ha_to_cavan_ha((float) $hiTons) : null;

    // ── Variety-relative class (same logic as admin) ──
    $class = null;
    if ($latest && $farmRecord->riceVariety && $predictedTons !== null) {
        $methodYield = $farmRecord->riceVariety->getYieldForMethod($farmRecord->seeding_method ?? 'Transplanted');
        $avgTons = $methodYield->avg ?? $farmRecord->riceVariety->avg_yield;
        if ($avgTons && $avgTons > 0) {
            $ratio = $predictedTons / $avgTons;
            if     ($ratio >= 1.125) $class = 'High';
            elseif ($ratio >= 0.875) $class = 'Medium';
            else                     $class = 'Low';
        }
    }
    $statusClass = match($class) {
        'High' => 'high',
        'Low'  => 'low',
        'Medium' => 'medium',
        default => null,
    };

    // ── Actual yield (t/ha → cavan/ha) ──
    $actualTons  = $farmRecord->actual_yield_tons_ha;
    $actualCavan = $actualTons ? t_ha_to_cavan_ha((float) $actualTons) : null;

    // ── Historical yield (t/ha → cavan/ha) ──
    $historicalTons  = $farmRecord->historical_yield_tons_ha;
    $historicalCavan = $historicalTons ? t_ha_to_cavan_ha((float) $historicalTons) : null;
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <div class="card" style="background: var(--slate-50); border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
            <div class="card-body">
                <h6 class="mb-3" style="color: var(--slate-800); font-weight: 700;">
                    <i class="bi bi-info-circle"></i> Season Info
                </h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr><td><strong>Farm:</strong></td><td>{{ $farmRecord->farm->name ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Barangay:</strong></td><td>{{ $farmRecord->farm->barangay ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Variety:</strong></td><td>{{ $farmRecord->riceVariety->name ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Season:</strong></td><td>{{ $farmRecord->season ?? 'N/A' }} {{ $farmRecord->year ?? '' }}</td></tr>
                    <tr><td><strong>Method:</strong></td><td>{{ $farmRecord->seeding_method ?? '—' }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card" style="background: var(--slate-50); border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
            <div class="card-body">
                <h6 class="mb-3" style="color: var(--slate-800); font-weight: 700;">
                    <i class="bi bi-clipboard-data"></i> Record Details
                </h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td><strong>Fertilizer:</strong></td>
                        <td>{{ number_format($farmRecord->fertilizer_kg_ha, 2) }} kg/ha</td>
                    </tr>
                    <tr>
                        <td><strong>Last Yield:</strong></td>
                        <td>
                            @if($historicalCavan !== null)
                                <strong>{{ number_format($historicalCavan, 0) }} cavan/ha</strong>
                                <br><small class="text-muted">({{ number_format($historicalTons, 2) }} t/ha)</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Actual Yield:</strong></td>
                        <td>
                            @if($farmRecord->status === 'Harvested' && $actualCavan !== null)
                                <strong>{{ number_format($actualCavan, 0) }} cavan/ha</strong>
                                <br><small class="text-muted">({{ number_format($actualTons, 2) }} t/ha)</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            @if($farmRecord->status === 'Harvested')
                                <span class="badge-status" style="background: var(--brand-green-light); color: var(--brand-green-dark); border-color: #c7e6d2;">
                                    <span class="dot" style="background: var(--brand-green);"></span> Harvested
                                </span>
                            @else
                                <span class="badge-status" style="background: var(--brand-gold-light); color: #92400e; border-color: #fde68a;">
                                    <span class="dot" style="background: var(--brand-gold);"></span> Growing
                                </span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-2">
    <div class="col-12">
        <div class="card" style="background: var(--brand-green-light); border-radius: var(--radius-md); border: 1px solid #a7f3d0;">
            <div class="card-body">
                <h6 class="mb-3" style="color: var(--brand-green-dark); font-weight: 700;">
                    <i class="bi bi-cpu" style="color: #4f46e5;"></i> Yield Estimates
                </h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div style="font-size: 11px; text-transform: uppercase; color: var(--slate-500); font-weight: 600;">
                            Expected (avg)
                        </div>
                        <div style="font-size: 20px; font-weight: 800; color: var(--slate-800); margin-top: 4px;">
                            @if($expectedAvgCavan !== null)
                                {{ number_format($expectedAvgCavan, 0) }}
                                <small style="font-size: 12px; font-weight: 600;">cavan/ha</small>
                                <div style="font-size: 11px; color: var(--slate-500); font-weight: 500;">
                                    ({{ number_format($expected->avg, 2) }} t/ha)
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div style="font-size: 11px; text-transform: uppercase; color: var(--slate-500); font-weight: 600;">
                            Expected (max)
                        </div>
                        <div style="font-size: 20px; font-weight: 800; color: var(--slate-800); margin-top: 4px;">
                            @if($expectedMaxCavan !== null)
                                {{ number_format($expectedMaxCavan, 0) }}
                                <small style="font-size: 12px; font-weight: 600;">cavan/ha</small>
                                <div style="font-size: 11px; color: var(--slate-500); font-weight: 500;">
                                    ({{ number_format($expected->max, 2) }} t/ha)
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div style="font-size: 11px; text-transform: uppercase; color: var(--brand-green-dark); font-weight: 600;">
                            <i class="bi bi-cpu"></i> Predicted (XGBoost)
                        </div>
                        <div style="font-size: 20px; font-weight: 800; color: var(--brand-green-dark); margin-top: 4px;">
                            @if($predictedCavan !== null)
                                {{ number_format($predictedCavan, 0) }}
                                <small style="font-size: 12px; font-weight: 600;">cavan/ha</small>
                                <div style="font-size: 11px; color: var(--brand-green-dark); opacity: 0.8; font-weight: 500;">
                                    ({{ number_format($predictedTons, 2) }} t/ha)
                                </div>
                                @if($loCavan !== null && $hiCavan !== null)
                                    <div style="font-size: 11px; color: var(--slate-500); font-weight: 500; margin-top: 2px;">
                                        80% range: <strong>{{ number_format($loCavan, 0) }}–{{ number_format($hiCavan, 0) }}</strong> cavan/ha
                                    </div>
                                @endif
                                @if($statusClass)
                                    <div style="margin-top: 6px;">
                                        <span class="badge-status {{ $statusClass }}" style="font-size: 11px;">
                                            <span class="dot"></span> {{ $class }}
                                            @if($conf)
                                                · {{ number_format($conf * 100, 0) }}%
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>
                </div>

                @if($predictedCavan === null)
                    <div class="mt-3" style="font-size: 12px; color: var(--slate-500);">
                        <i class="bi bi-info-circle"></i>
                        No prediction has been generated for this season yet. Ask CAO staff to generate one from the prediction page.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12 text-end">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    </div>
</div>