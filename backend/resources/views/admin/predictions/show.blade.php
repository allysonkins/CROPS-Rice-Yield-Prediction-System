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
                    <tr><td><strong>Fertilizer:</strong></td><td>{{ $farmRecord->fertilizer_kg_ha ?? 'N/A' }} kg/ha</td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card" style="background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200); border-top: 3px solid #3498db;">
            <div class="card-body">
                <h6><i class="bi bi-cloud-sun" style="color: var(--gold);"></i> Weather at Prediction</h6>
                @if($weather)
                    <div class="row align-items-center">
                        <div class="col-4 text-center">
                            <i class="bi bi-sun" style="font-size: 32px; color: var(--gold);"></i>
                            <div class="fw-bold" style="font-size: 18px;">{{ $weather['temperature'] ?? 'N/A' }}°C</div>
                        </div>
                        <div class="col-8">
                            <div class="mb-1"><strong>{{ $weather['description'] ?? 'N/A' }}</strong></div>
                            <div class="text-muted small">
                                <i class="bi bi-droplet"></i> Humidity: {{ $weather['humidity'] ?? 'N/A' }}%<br>
                                <i class="bi bi-cloud-rain"></i> Rainfall: {{ $weather['rainfall'] ?? 0 }}mm<br>
                                <i class="bi bi-wind"></i> Wind: {{ $weather['wind_speed'] ?? 'N/A' }} km/h
                            </div>
                        </div>
                    </div>
                @else
                    <p class="text-muted mb-0">No weather data available.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Prediction Results -->
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card" style="background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200); border-top: 3px solid #4f46e5;">
            <div class="card-body">
                <h6><i class="bi bi-cpu" style="color: #4f46e5;"></i> AI Model Predictions</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card p-3 text-center" style="border: none; background: white; border-radius: 10px; box-shadow: var(--shadow-sm);">
                            <strong>Random Forest</strong>
                            <h3 class="mt-2" style="color: #0f4c2b;">{{ $rf ? number_format($rf->predicted_yield_tons_ha, 2) : 'N/A' }} t/ha</h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card p-3 text-center" style="border: none; background: white; border-radius: 10px; box-shadow: var(--shadow-sm);">
                            <strong>XGBoost</strong>
                            <h3 class="mt-2" style="color: #b8860b;">{{ $xgb ? number_format($xgb->predicted_yield_tons_ha, 2) : 'N/A' }} t/ha</h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card p-3 text-center" style="border: 2px solid #4f46e5; background: white; border-radius: 10px; box-shadow: var(--shadow-sm);">
                            <strong>Ensemble (Recommended)</strong>
                            <h3 class="mt-2" style="color: #4f46e5;">{{ $ensemble ? number_format($ensemble->predicted_yield_tons_ha, 2) : 'N/A' }} t/ha</h3>
                        </div>
                    </div>
                </div>

                @if($ensemble)
                    @php
                        $yield = $ensemble->predicted_yield_tons_ha;
                        $status = $yield >= 4.5 ? 'High' : ($yield >= 3.5 ? 'Medium' : 'Low');
                        $color = $yield >= 4.5 ? 'success' : ($yield >= 3.5 ? 'warning' : 'danger');
                    @endphp
                    <div class="mt-3 text-center">
                        <span class="badge bg-{{ $color }}" style="font-size: 14px; padding: 6px 16px;">
                            <i class="bi bi-{{ $yield >= 4.5 ? 'check-circle' : ($yield >= 3.5 ? 'exclamation-triangle' : 'x-circle') }}"></i>
                            {{ $status }} Yield ({{ number_format($yield, 2) }} t/ha)
                        </span>
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
                <h6><i class="bi bi-sliders2"></i> Input Data Used</h6>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-unstyled small mb-0">
                            <li><strong>Barangay:</strong> {{ $farmRecord->farm->barangay ?? 'N/A' }}</li>
                            <li><strong>Variety:</strong> {{ $farmRecord->riceVariety->name ?? 'N/A' }}</li>
                            <li><strong>Soil Type:</strong> {{ $farmRecord->farm->soil_type ?? 'N/A' }}</li>
                            <li><strong>Season:</strong> {{ $farmRecord->season ?? 'N/A' }}</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled small mb-0">
                            <li><strong>Seeding Method:</strong> {{ $farmRecord->seeding_method ?? 'N/A' }}</li>
                            <li><strong>Fertilizer:</strong> {{ $farmRecord->fertilizer_kg_ha ?? 'N/A' }} kg/ha</li>
                            <li><strong>Historical Yield:</strong> {{ $farmRecord->historical_yield_tons_ha ?? 'N/A' }} t/ha</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>