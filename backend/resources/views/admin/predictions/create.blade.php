@php
    $farmRecords = \App\Models\FarmRecord::with(['farm', 'riceVariety'])->get();
    $weather = app(\App\Services\WeatherService::class)->getWeather();
@endphp

<div id="generatePredictionError"></div>

<!-- Weather Display -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card" style="background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200);">
            <div class="card-body">
                <h6><i class="bi bi-cloud-sun" style="color: var(--gold);"></i> Current Weather (Santiago City)</h6>
                <div class="row align-items-center">
                    <div class="col-3 text-center">
                        <i class="bi bi-sun" style="font-size: 32px; color: var(--gold);"></i>
                        <div class="fw-bold" style="font-size: 18px;">{{ $weather['temperature'] ?? 'N/A' }}°C</div>
                    </div>
                    <div class="col-9">
                        <div class="mb-1"><strong>{{ $weather['description'] ?? 'Loading...' }}</strong></div>
                        <div class="text-muted small">
                            <i class="bi bi-droplet"></i> Humidity: {{ $weather['humidity'] ?? 'N/A' }}% &nbsp;|&nbsp;
                            <i class="bi bi-cloud-rain"></i> Rainfall: {{ $weather['rainfall'] ?? 0 }}mm &nbsp;|&nbsp;
                            <i class="bi bi-wind"></i> Wind: {{ $weather['wind_speed'] ?? 'N/A' }} km/h
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-satellite"></i> Source: {{ $weather['source'] ?? 'Weather API' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="generatePredictionForm" action="{{ route('admin.predictions.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-12 mb-3">
            <label class="form-label fw-semibold">Select Farm Record <span class="text-danger">*</span></label>
            <select name="farm_record_id" class="form-select" required>
                <option value="">Select a farm record...</option>
                @foreach($farmRecords as $record)
                    <option value="{{ $record->id }}">
                        {{ $record->farm->name ?? 'N/A' }} - 
                        {{ $record->riceVariety->name ?? 'N/A' }} - 
                        {{ $record->season }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-12">
            <div class="p-3 bg-light rounded">
                <h6><i class="bi bi-info-circle"></i> How It Works</h6>
                <ul class="text-muted small mb-0">
                    <li><strong>Random Forest</strong> — Captures complex non-linear interactions in the data</li>
                    <li class="mt-2"><i class="bi bi-cloud-sun"></i> <strong>Weather Data</strong> — Real-time weather from OpenWeatherMap is used as input</li>
                </ul>
            </div>
        </div>

        <div class="col-12 mt-3">
            <button type="submit" class="btn btn-success w-100">
                <i class="bi bi-graph-up"></i> Generate Prediction
            </button>
        </div>
    </div>
</form>