<!-- ============================================================ -->
<!-- FARM RECORD DETAILS                                          -->
<!-- ============================================================ -->
<div class="row g-3">
    <div class="col-md-6">
        <div class="card" style="background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200);">
            <div class="card-body">
                <h6><i class="bi bi-info-circle"></i> Farm Information</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr><td><strong>Farm Name:</strong></td><td>{{ $farmRecord->farm->name ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Barangay:</strong></td><td>{{ $farmRecord->farm->barangay ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Rice Variety:</strong></td><td>{{ $farmRecord->riceVariety->name ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Classification:</strong></td><td>{{ $farmRecord->riceVariety->classification ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Season:</strong></td><td>{{ $farmRecord->season ?? 'N/A' }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card" style="background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200);">
            <div class="card-body">
                <h6><i class="bi bi-clipboard-data"></i> Record Details</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr><td><strong>Fertilizer (kg/ha):</strong></td><td>{{ number_format($farmRecord->fertilizer_kg_ha, 2) }}</td></tr>
                    <tr><td><strong>Historical Yield (t/ha):</strong></td><td>{{ $farmRecord->historical_yield_tons_ha ? number_format($farmRecord->historical_yield_tons_ha, 2) : 'N/A' }}</td></tr>
                    <tr><td><strong>Actual Yield (t/ha):</strong></td>
                        <td>
                            @if($farmRecord->status === 'Harvested')
                                {{ $farmRecord->actual_yield_tons_ha ? number_format($farmRecord->actual_yield_tons_ha, 2) : 'N/A' }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    <tr><td><strong>Seeding Method:</strong></td><td>{{ $farmRecord->seeding_method ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Status:</strong></td>
                        <td>
                            <span class="badge {{ $farmRecord->status_badge_class }}">
                                <i class="bi {{ $farmRecord->status_icon }}"></i>
                                {{ $farmRecord->status ?? 'N/A' }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Expected Yield Section -->
<div class="row g-3 mt-2">
    <div class="col-12">
        <div class="card" style="background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200); border-top: 3px solid #4f46e5;">
            <div class="card-body">
                <h6><i class="bi bi-flower1" style="color: #4f46e5;"></i> Expected Yield (Variety)</h6>
                @php
                    $expected = null;
                    if ($farmRecord->riceVariety && $farmRecord->seeding_method) {
                        $expected = $farmRecord->riceVariety->getYieldForMethod($farmRecord->seeding_method);
                    }
                @endphp
                @if($expected && $expected->avg !== null && $expected->max !== null)
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Average:</strong> <span class="fw-bold text-success">{{ number_format($expected->avg, 2) }} t/ha</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Maximum:</strong> <span class="fw-bold text-success">{{ number_format($expected->max, 2) }} t/ha</span>
                        </div>
                    </div>
                @else
                    <p class="text-muted mb-0">No expected yield data available for this variety and seeding method.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- XGBoost Predicted Yield Section -->
@php
    $prediction = $farmRecord->latest_xgboost_prediction;
    $predicted  = $prediction?->predicted_yield_tons_ha;
    $confidence = $prediction?->confidence;
    $lo         = $prediction?->yield_lower;
    $hi         = $prediction?->yield_upper;
    $yieldClass = $farmRecord->predicted_yield_class;
    $badge      = $farmRecord->predicted_yield_badge;
    $ratio      = $farmRecord->predicted_yield_ratio;
    $avgYield   = $farmRecord->variety_avg_yield;

    if ($confidence === null)    $tier = 'secondary';
    elseif ($confidence >= 0.90) $tier = 'success';
    elseif ($confidence >= 0.70) $tier = 'primary';
    elseif ($confidence >= 0.50) $tier = 'warning';
    else                         $tier = 'danger';
@endphp

<div class="row g-3 mt-2">
    <div class="col-12">
        <div class="card" style="background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200); border-top: 3px solid var(--green);">
            <div class="card-body">
                <h6><i class="bi bi-cpu" style="color: var(--green);"></i> XGBoost Predicted Yield</h6>
                @if($predicted !== null)
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div>
                            <div style="font-size: 11px; text-transform: uppercase; color: var(--gray-500); font-weight: 700; letter-spacing: 0.5px;">Predicted</div>
                            <div style="font-size: 26px; font-weight: 800; color: #0f4c2b;">
                                {{ number_format($predicted, 2) }} <span style="font-size: 15px;">t/ha</span>
                            </div>
                            @if($lo !== null && $hi !== null)
                                <div class="text-muted small mt-1">
                                    80% interval: <strong>{{ number_format($lo, 2) }} – {{ number_format($hi, 2) }} t/ha</strong>
                                </div>
                            @endif
                        </div>

                        @if($yieldClass)
                            <div style="margin-left: auto;">
                                <span class="badge-status {{ $badge }}" style="font-size: 13px; padding: 6px 14px;">
                                    <span class="dot"></span> {{ $yieldClass }} Yield
                                </span>
                                @if($ratio !== null)
                                    <div class="text-muted small mt-1" style="text-align: right; font-size: 11px;">
                                        {{ number_format($ratio * 100, 1) }}% of variety average
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if($confidence !== null)
                            <div>
                                <span class="badge bg-{{ $tier }}" style="font-size: 12px; padding: 6px 14px;">
                                    <i class="bi bi-activity"></i>
                                    {{ number_format($confidence * 100, 1) }}% confidence
                                </span>
                            </div>
                        @endif
                    </div>

                    @if($confidence !== null)
                        <div class="mt-3 text-muted" style="font-size: 11px; line-height: 1.5;">
                            <i class="bi bi-info-circle"></i>
                            Confidence is derived from an <strong>80% prediction interval</strong> produced by two additional
                            XGBoost models trained on the 10th and 90th percentile. The tighter the interval,
                            the more certain the model.
                        </div>
                    @endif

                    <div class="mt-3 text-muted" style="font-size: 11px;">
                        Generated {{ $prediction->created_at->diffForHumans() }}
                    </div>
                @else
                    <p class="text-muted mb-0">No prediction available for this farm record yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Close Button -->
<div class="row mt-3">
    <div class="col-12 text-end">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    </div>
</div>