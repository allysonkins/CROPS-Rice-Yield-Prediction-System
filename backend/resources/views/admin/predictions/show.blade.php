<!-- Prediction Results -->
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card" style="background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200); border-top: 3px solid #4f46e5;">
            <div class="card-body">
                <h6><i class="bi bi-cpu" style="color: #4f46e5;"></i> XGBoost Prediction</h6>

                @if($prediction)
                    @php
                        $yield      = $prediction->predicted_yield_tons_ha;
                        $confidence = $prediction->confidence;
                        $lo         = $prediction->yield_lower;
                        $hi         = $prediction->yield_upper;

                        if ($confidence === null)    $tier = 'secondary';
                        elseif ($confidence >= 0.90) $tier = 'success';
                        elseif ($confidence >= 0.70) $tier = 'primary';
                        elseif ($confidence >= 0.50) $tier = 'warning';
                        else                         $tier = 'danger';
                    @endphp

                    <div class="row g-3">
                        <div class="col-md-6 offset-md-3">
                            <div class="card p-3 text-center" style="border: 2px solid #0f4c2b; background: white; border-radius: 10px;">
                                <strong>Predicted Yield</strong>
                                <h3 class="mt-2" style="color: #0f4c2b;">{{ number_format($yield, 2) }} t/ha</h3>

                                @if($lo !== null && $hi !== null)
                                    <div class="text-muted small mt-1">
                                        80% prediction interval:
                                        <strong>{{ number_format($lo, 2) }} – {{ number_format($hi, 2) }} t/ha</strong>
                                    </div>
                                @endif

                                @if($confidence !== null)
                                    <div class="mt-2">
                                        <span class="badge bg-{{ $tier }}" style="font-size: 13px; padding: 6px 14px;">
                                            <i class="bi bi-activity"></i>
                                            {{ number_format($confidence * 100, 1) }}% confidence
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($confidence !== null)
                        <div class="mt-3 text-center">
                            <div class="d-inline-flex align-items-center gap-2 px-3 py-2"
                                 style="background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 10px; font-size: 12px; color: var(--gray-600); max-width: 640px;">
                                <i class="bi bi-info-circle" style="color: var(--gold-dark);"></i>
                                <span style="text-align: left;">
                                    Confidence is derived from an <strong>80% prediction interval</strong> produced by
                                    two additional XGBoost models trained on the 10th and 90th percentile.
                                    The tighter the interval, the more certain the model.
                                </span>
                            </div>
                        </div>
                    @endif

                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            Generated {{ $prediction->created_at->diffForHumans() }}
                        </small>
                    </div>
                @else
                    <div class="mt-3 text-center text-muted">
                        No prediction available for this farm record.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Input Data -->
<div class="row g-3">
    <div class="col-12">
        <div class="card" style="background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200);">
            <div class="card-body">
                <h6><i class="bi bi-sliders2"></i> Input Data Used for Prediction</h6>
                @if($prediction)
                    @php
                        $inputFeatures = json_decode($prediction->input_features, true);
                        $input = $inputFeatures['input'] ?? [];
                    @endphp
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled small mb-0">
                                <li><strong>Variety:</strong> {{ $input['variety'] ?? 'N/A' }}</li>
                                <li><strong>Soil Type:</strong> {{ $input['soil_type'] ?? 'N/A' }}</li>
                                <li><strong>Season:</strong> {{ $input['season'] ?? 'N/A' }}</li>
                                <li><strong>Seeding Method:</strong> {{ $input['seeding_method'] ?? 'N/A' }}</li>
                                <li><strong>Land Area (ha):</strong> {{ $input['land_area_ha'] ?? 'N/A' }}</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled small mb-0">
                                <li><strong>Fertilizer (kg/ha):</strong> {{ $input['fertilizer_kg_ha'] ?? 'N/A' }}</li>
                                <li><strong>Temperature (°C):</strong> {{ $input['temperature_avg'] ?? 'N/A' }}</li>
                                <li><strong>Rainfall (mm):</strong> {{ $input['rainfall_mm'] ?? 'N/A' }}</li>
                                <li><strong>Humidity (%):</strong> {{ $input['humidity_avg'] ?? 'N/A' }}</li>
                                <li><strong>Historical Yield (t/ha):</strong> {{ $input['historical_yield_tons_ha'] ?? 'N/A' }}</li>
                            </ul>
                        </div>
                    </div>
                @else
                    <p class="text-muted mb-0">No input data available.</p>
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