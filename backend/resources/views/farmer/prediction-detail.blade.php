@php
    $yieldTons   = $prediction?->predicted_yield_tons_ha;
    $yieldCavan  = $yieldTons !== null ? t_ha_to_cavan_ha((float) $yieldTons) : null;
    $farmArea    = (float) ($farmRecord->farm->land_area_ha ?? 1.0);
    $totalCavan  = $yieldTons !== null ? cavan_total((float) $yieldTons, $farmArea) : null;
@endphp

<!-- ═══════════ PREDICTION RESULT ═══════════ -->
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card" style="background: var(--slate-50); border-radius: var(--radius-md); border: 1px solid var(--slate-200); border-top: 3px solid #4f46e5;">
            <div class="card-body">
                <h6 style="color: var(--slate-800); font-weight: 700;">
                    <i class="bi bi-cpu" style="color: #4f46e5;"></i> Random Forest Prediction
                </h6>

                @if($prediction)
                    @php
                        $class      = $prediction->predicted_class;
                        $confidence = $prediction->confidence;

                        $classMap = [
                            'High'   => ['css' => 'high',   'icon' => 'check-circle-fill',        'text' => 'Good harvest expected'],
                            'Medium' => ['css' => 'medium', 'icon' => 'exclamation-triangle-fill','text' => 'Average harvest expected'],
                            'Low'    => ['css' => 'low',    'icon' => 'x-circle-fill',            'text' => 'Below average expected'],
                        ];
                        $meta = $classMap[$class] ?? ['css' => 'medium', 'icon' => 'question-circle', 'text' => 'Unknown'];
                    @endphp

                    {{-- Big yield card --}}
                    <div class="row g-3">
                        <div class="col-md-6 offset-md-3">
                            <div class="card p-3 text-center" style="border: 2px solid var(--brand-green-dark); background: white; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
                                <strong style="color: var(--slate-700);">Estimated Yield</strong>
                                <h3 class="mt-2" style="color: var(--brand-green-dark); font-weight: 800; letter-spacing: -0.6px;">
                                    {{ number_format($yieldCavan, 0) }}
                                    <small style="font-size: 15px; font-weight: 600;">cavan/ha</small>
                                </h3>
                                <div style="font-size: 11px; color: var(--slate-400); margin-top: 2px;">
                                    ({{ number_format($yieldTons, 2) }} t/ha)
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Total for this farm --}}
                    @if($totalCavan !== null)
                        <div class="row g-3 mt-2">
                            <div class="col-md-6 offset-md-3">
                                <div class="text-center p-3"
                                     style="background: var(--brand-green-light); border-radius: var(--radius-md);">
                                    <div style="font-size: 11px; text-transform: uppercase; color: var(--brand-green-dark); font-weight: 700; letter-spacing: 0.4px;">
                                        <i class="bi bi-box-seam"></i> Total from your {{ number_format($farmArea, 2) }} ha
                                    </div>
                                    <div style="font-size: 24px; font-weight: 800; color: var(--brand-green-dark); line-height: 1.1; margin-top: 4px;">
                                        {{ number_format($totalCavan, 0) }}
                                        <small style="font-size: 14px; font-weight: 600;">cavan</small>
                                    </div>
                                    <div style="font-size: 11px; color: var(--brand-green-dark); opacity: 0.8; margin-top: 2px;">
                                        ≈ {{ number_format($totalCavan * 50, 0) }} kg
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Classification badge --}}
                    <div class="mt-3 text-center">
                        <span class="badge-status {{ $meta['css'] }}" style="font-size: 13px; padding: 8px 18px;">
                            <i class="bi bi-{{ $meta['icon'] }}"></i>
                            {{ $class ?? 'Unknown' }} Yield
                            @if($confidence)
                                · {{ number_format($confidence * 100, 1) }}% confidence
                            @endif
                        </span>
                        <div style="font-size: 12px; color: var(--slate-600); margin-top: 6px;">
                            {{ $meta['text'] }}
                        </div>
                    </div>

                    {{-- Confidence explanation --}}
                    @if($confidence)
                        <div class="mt-3 text-center">
                            <div class="d-inline-flex align-items-center gap-2 px-3 py-2"
                                 style="background: var(--slate-50); border: 1px solid var(--slate-200); border-radius: var(--radius-md); font-size: 12px; color: var(--slate-600); max-width: 640px;">
                                <i class="bi bi-info-circle" style="color: var(--brand-gold);"></i>
                                <span style="text-align: left;">
                                    Confidence reflects how many of the model's 800 decision trees voted for
                                    <strong>{{ $class }}</strong>. Higher = more agreement among trees.
                                    It is <em>not</em> a probability that the yield will occur.
                                </span>
                            </div>
                        </div>
                    @endif

                    {{-- Tree vote distribution --}}
                    @php
                        $probas = json_decode($prediction->input_features, true)['probabilities'] ?? null;
                    @endphp
                    @if($probas)
                        <div class="mt-3 text-center small" style="color: var(--slate-500);">
                            @foreach($probas as $lbl => $p)
                                <span class="me-3">
                                    <strong style="color: var(--slate-700);">{{ $lbl }}:</strong>
                                    <span style="color: var(--brand-green-dark); font-weight: 600;">{{ number_format($p * 100, 1) }}%</span>
                                </span>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="mt-3 text-center" style="color: var(--slate-500); font-size: 13px;">
                        No prediction available for this farm record.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- ═══════════ INPUT DATA ═══════════ -->
<div class="row g-3">
    <div class="col-12">
        <div class="card" style="background: var(--slate-50); border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
            <div class="card-body">
                <h6 style="color: var(--slate-800); font-weight: 700;">
                    <i class="bi bi-sliders2"></i> Input Data Used for Prediction
                </h6>
                @if($prediction)
                    @php
                        $inputFeatures = json_decode($prediction->input_features, true);
                        $input = $inputFeatures['input'] ?? [];
                    @endphp
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled small mb-0" style="line-height: 1.8;">
                                <li><strong style="color: var(--slate-700);">Variety:</strong> {{ $input['variety'] ?? 'N/A' }}</li>
                                <li><strong style="color: var(--slate-700);">Soil Type:</strong> {{ $input['soil_type'] ?? 'N/A' }}</li>
                                <li><strong style="color: var(--slate-700);">Season:</strong> {{ $input['season'] ?? 'N/A' }}</li>
                                <li><strong style="color: var(--slate-700);">Seeding Method:</strong> {{ $input['seeding_method'] ?? 'N/A' }}</li>
                                <li><strong style="color: var(--slate-700);">Land Area (ha):</strong> {{ $input['land_area_ha'] ?? 'N/A' }}</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled small mb-0" style="line-height: 1.8;">
                                <li><strong style="color: var(--slate-700);">Fertilizer (kg/ha):</strong> {{ $input['fertilizer_kg_ha'] ?? 'N/A' }}</li>
                                <li><strong style="color: var(--slate-700);">Temperature (°C):</strong> {{ $input['temperature_avg'] ?? 'N/A' }}</li>
                                <li><strong style="color: var(--slate-700);">Rainfall (mm):</strong> {{ $input['rainfall_mm'] ?? 'N/A' }}</li>
                                <li><strong style="color: var(--slate-700);">Humidity (%):</strong> {{ $input['humidity_avg'] ?? 'N/A' }}</li>
                                <li><strong style="color: var(--slate-700);">Historical Yield (t/ha):</strong> {{ $input['historical_yield_tons_ha'] ?? 'N/A' }}</li>
                            </ul>
                        </div>
                    </div>
                @else
                    <p style="color: var(--slate-500); margin: 0;">No input data available.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- ═══════════ FOOTER ═══════════ -->
<div class="row mt-3">
    <div class="col-12 text-end">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-circle"></i> Close
        </button>
    </div>
</div>