@php
    $yieldTons   = $prediction?->predicted_yield_tons_ha;
    $yieldCavan  = $yieldTons !== null ? t_ha_to_cavan_ha((float) $yieldTons) : null;
    $farmArea    = (float) ($farmRecord->farm->land_area_ha ?? 1.0);
    $totalCavan  = $yieldTons !== null ? cavan_total((float) $yieldTons, $farmArea) : null;

    // ── Variety-relative yield class (same logic as admin) ──
    $method       = $farmRecord->seeding_method ?? 'Transplanted';
    $varietyModel = $farmRecord->riceVariety;
    $methodYield  = $varietyModel ? $varietyModel->getYieldForMethod($method) : null;
    $avgYieldTons = $methodYield->avg ?? ($varietyModel->avg_yield ?? null);
    $avgCavan     = $avgYieldTons !== null ? t_ha_to_cavan_ha((float) $avgYieldTons) : null;

    $class = null;
    $ratio = null;
    if ($avgYieldTons && $avgYieldTons > 0 && $yieldTons !== null) {
        $ratio = $yieldTons / $avgYieldTons;
        if     ($ratio >= 1.125) $class = 'High';
        elseif ($ratio >= 0.875) $class = 'Medium';
        else                     $class = 'Low';
    }

    // ── 80% prediction interval ──
    $conf    = $prediction?->confidence;
    $lo      = $prediction?->yield_lower;
    $hi      = $prediction?->yield_upper;
    $loCavan = $lo !== null ? t_ha_to_cavan_ha((float) $lo) : null;
    $hiCavan = $hi !== null ? t_ha_to_cavan_ha((float) $hi) : null;

    $classMap = [
        'High'   => ['css' => 'high',   'icon' => 'check-circle-fill',         'text' => 'Mas mataas sa karaniwan ng variety na ito'],
        'Medium' => ['css' => 'medium', 'icon' => 'exclamation-triangle-fill', 'text' => 'Katulad ng karaniwang ani ng variety na ito'],
        'Low'    => ['css' => 'low',    'icon' => 'x-circle-fill',             'text' => 'Mas mababa sa karaniwan ng variety na ito'],
    ];
    $meta = $classMap[$class] ?? ['css' => 'medium', 'icon' => 'question-circle', 'text' => 'Hindi sapat ang datos para sa class'];
@endphp

<!-- ═══════════ PREDICTION RESULT ═══════════ -->
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card" style="background: var(--slate-50); border-radius: var(--radius-md); border: 1px solid var(--slate-200); border-top: 3px solid #4f46e5;">
            <div class="card-body">
                <h6 style="color: var(--slate-800); font-weight: 700;">
                    <i class="bi bi-cpu" style="color: #4f46e5;"></i> XGBoost Prediction
                </h6>

                @if($prediction && $yieldCavan !== null)
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

                                @if($loCavan !== null && $hiCavan !== null)
                                    <div class="mt-2" style="font-size: 11px; color: var(--slate-500);">
                                        <i class="bi bi-arrows-collapse"></i>
                                        80% range: <strong>{{ number_format($loCavan, 0) }}–{{ number_format($hiCavan, 0) }} cavan/ha</strong>
                                    </div>
                                @endif
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

                    {{-- Yield class badge --}}
                    <div class="mt-3 text-center">
                        @if($class)
                            <span class="badge-status {{ $meta['css'] }}"
                                  style="font-size: 13px; padding: 8px 18px; cursor: help;"
                                  data-bs-toggle="tooltip"
                                  data-bs-placement="top"
                                  data-bs-html="true"
                                  title="<div style='text-align:left; font-size:12px; line-height:1.5;'>
                                      <strong>Kumpara sa karaniwan ng variety</strong><br>
                                      Karaniwan ({{ $method }}): <strong>{{ number_format($avgCavan, 0) }} cavan/ha</strong><br>
                                      Ratio ng iyong hula: <strong>{{ number_format($ratio * 100, 1) }}%</strong><br><br>
                                      <strong>Bands:</strong><br>
                                      • High — ≥ 112.5% ng karaniwan<br>
                                      • Medium — 87.5% – 112.5%<br>
                                      • Low — mas mababa sa 87.5%<br><br>
                                      <em>Kinukuwenta ito ng system — hindi galing sa model.</em>
                                  </div>">
                                <i class="bi bi-{{ $meta['icon'] }}"></i>
                                {{ $class }} Yield
                                @if($conf !== null)
                                    · {{ number_format($conf * 100, 1) }}% confidence
                                @endif
                            </span>
                            <div style="font-size: 12px; color: var(--slate-600); margin-top: 6px;">
                                {{ $meta['text'] }}
                            </div>
                        @else
                            <span class="badge-status medium" style="font-size: 13px; padding: 8px 18px;">
                                <i class="bi bi-question-circle"></i> Walang class (kulang ang variety average)
                            </span>
                        @endif
                    </div>

                    {{-- Confidence explanation (XGBoost) --}}
                    @if($conf !== null)
                        <div class="mt-3 text-center">
                            <div class="d-inline-flex align-items-center gap-2 px-3 py-2"
                                 style="background: var(--slate-50); border: 1px solid var(--slate-200); border-radius: var(--radius-md); font-size: 12px; color: var(--slate-600); max-width: 640px;">
                                <i class="bi bi-info-circle" style="color: var(--brand-gold);"></i>
                                <span style="text-align: left;">
                                    Ang <strong>confidence</strong> ay galing sa <strong>80% prediction interval</strong> —
                                    dalawang karagdagang XGBoost model (10th at 90th percentile) ang gumagawa ng range.<br>
                                    <strong>Confidence = 1 − (haba ng range ÷ hula)</strong>.
                                    Mas makitid ang range, mas mataas ang kumpiyansa.
                                    <em>Hindi ito garantiya ng aktwal na ani.</em>
                                </span>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="mt-3 text-center" style="color: var(--slate-500); font-size: 13px;">
                        Walang prediction na available para sa farm record na ito.
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
                    <p style="color: var(--slate-500); margin: 0;">Walang input data.</p>
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