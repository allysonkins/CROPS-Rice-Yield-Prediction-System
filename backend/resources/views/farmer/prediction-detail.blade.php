<!-- Farm Information -->
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card" style="background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200);">
            <div class="card-body">
                <h6><i class="bi bi-info-circle"></i> Farm Information</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr><td><strong>Farm Name:</strong></td><td>{{ $farmRecord->farm->name ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Barangay:</strong></td><td>{{ $farmRecord->farm->barangay ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Variety:</strong></td><td>{{ $farmRecord->riceVariety->name ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Season:</strong></td><td>{{ $farmRecord->season ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Soil Type:</strong></td><td>{{ $farmRecord->farm->soil_type ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Seeding Method:</strong></td><td>{{ $farmRecord->seeding_method ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Fertilizer (kg/ha):</strong></td><td>{{ $farmRecord->fertilizer_kg_ha ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Historical Yield:</strong></td><td>{{ $farmRecord->historical_yield_tons_ha ? number_format($farmRecord->historical_yield_tons_ha, 2) : 'N/A' }} t/ha</td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card" style="background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200); border-top: 3px solid #3498db;">
            <div class="card-body">
                <h6><i class="bi bi-cloud-sun" style="color: var(--gold);"></i> Weather Conditions</h6>
                @if($weather)
                    <div class="row align-items-center">
                        <div class="col-4 text-center">
                            <i class="bi bi-{{ $weather['icon'] ?? 'sun' }}" style="font-size: 32px; color: var(--gold);"></i>
                            <div class="fw-bold" style="font-size: 18px;">{{ $weather['temperature'] ?? 'N/A' }}°C</div>
                            <small class="text-muted">{{ $weather['description'] ?? 'N/A' }}</small>
                        </div>
                        <div class="col-8">
                            <div class="row small">
                                <div class="col-6">
                                    <span class="text-muted"><i class="bi bi-droplet"></i> Humidity</span><br>
                                    <strong>{{ $weather['humidity'] ?? 'N/A' }}%</strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted"><i class="bi bi-cloud-rain"></i> Rainfall</span><br>
                                    <strong>{{ $weather['rainfall'] ?? 0 }} mm</strong>
                                </div>
                                <div class="col-6 mt-1">
                                    <span class="text-muted"><i class="bi bi-wind"></i> Wind Speed</span><br>
                                    <strong>{{ $weather['wind_speed'] ?? 'N/A' }} km/h</strong>
                                </div>
                                <div class="col-6 mt-1">
                                    <span class="text-muted"><i class="bi bi-satellite"></i> Source</span><br>
                                    <strong>{{ $weather['source'] ?? 'Weather API' }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <p class="text-muted mb-0">No weather data available for this prediction.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Prediction Results (Random Forest) -->
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card" style="background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200); border-top: 3px solid #4f46e5;">
            <div class="card-body">
                <h6><i class="bi bi-cpu" style="color: #4f46e5;"></i> Random Forest Prediction</h6>
                <div class="row g-3">
                    <div class="col-md-6 offset-md-3">
                        <div class="card p-3 text-center" style="border: 2px solid #0f4c2b; background: white; border-radius: 10px; box-shadow: var(--shadow-sm);">
                            <strong>Predicted Yield</strong>
                            <h3 class="mt-2" style="color: #0f4c2b;">
                                {{ $prediction ? number_format($prediction->predicted_yield_tons_ha, 2) : 'N/A' }} t/ha
                            </h3>
                        </div>
                    </div>
                </div>

                @if($prediction)
                    @php
                        $yield = $prediction->predicted_yield_tons_ha;

                        // --- Status based on variety's max yield ---
                        $maxYield = $farmRecord->riceVariety
                            ? $farmRecord->riceVariety->getMaxYieldForMethod($farmRecord->seeding_method)
                            : null;

                        if ($maxYield !== null && $maxYield > 0) {
                            $ratio = $yield / $maxYield;
                            if ($ratio >= 0.9) {
                                $statusClass = 'success';
                                $statusText = 'High';
                                $statusIcon = 'check-circle';
                            } elseif ($ratio >= 0.7) {
                                $statusClass = 'warning';
                                $statusText = 'Medium';
                                $statusIcon = 'exclamation-triangle';
                            } else {
                                $statusClass = 'danger';
                                $statusText = 'Low';
                                $statusIcon = 'x-circle';
                            }
                        } else {
                            $statusClass = $yield >= 4.5 ? 'success' : ($yield >= 3.5 ? 'warning' : 'danger');
                            $statusText = $yield >= 4.5 ? 'High' : ($yield >= 3.5 ? 'Medium' : 'Low');
                            $statusIcon = $yield >= 4.5 ? 'check-circle' : ($yield >= 3.5 ? 'exclamation-triangle' : 'x-circle');
                        }
                    @endphp
                    <div class="mt-3 text-center">
                        <span class="badge bg-{{ $statusClass }}" style="font-size: 14px; padding: 6px 16px;">
                            <i class="bi bi-{{ $statusIcon }}"></i>
                            {{ $statusText }} Yield ({{ number_format($yield, 2) }} t/ha)
                        </span>
                        @if($maxYield)
                            <div class="text-muted small mt-1">
                                Max potential for this variety: {{ number_format($maxYield, 2) }} t/ha
                            </div>
                        @endif
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
                                <li><strong>Barangay:</strong> {{ $input['barangay'] ?? 'N/A' }}</li>
                                <li><strong>Variety:</strong> {{ $input['variety'] ?? 'N/A' }}</li>
                                <li><strong>Soil Type:</strong> {{ $input['soil_type'] ?? 'N/A' }}</li>
                                <li><strong>Season:</strong> {{ $input['season'] ?? 'N/A' }}</li>
                                <li><strong>Seeding Method:</strong> {{ $input['seeding_method'] ?? 'N/A' }}</li>
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

<!-- Close Button -->
<div class="row mt-3">
    <div class="col-12 text-end">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    </div>
</div>