<!-- ============================================================ -->
<!-- RICE VARIETY DETAILS (loaded into modal)                      -->
<!-- ============================================================ -->
<div class="row g-3">
    <div class="col-md-6">
        <div class="card" style="background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200);">
            <div class="card-body">
                <h6><i class="bi bi-info-circle"></i> Variety Information</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr><td><strong>Name:</strong></td><td>{{ $variety->name }}</td></tr>
                    <tr><td><strong>Classification:</strong></td><td>
                        <span class="badge {{ $variety->classification == 'Hybrid' ? 'bg-warning text-dark' : 'bg-success' }}">
                            {{ $variety->classification }}
                        </span>
                    </td></tr>
                    <tr><td><strong>Description:</strong></td><td>{{ $variety->description ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Grain Quality:</strong></td><td>{{ $variety->grain_quality ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Disease Susceptibility:</strong></td><td>{{ $variety->disease_susceptibility ?? 'N/A' }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card" style="background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200);">
            <div class="card-body">
                <h6><i class="bi bi-flower1"></i> Seeding Method Specifics</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr><td><strong>Growth Period (Transplanted):</strong></td><td>{{ $variety->growth_period_transplanted ?? $variety->growth_period ?? 'N/A' }} days</td></tr>
                    <tr><td><strong>Growth Period (Direct Seeded):</strong></td><td>{{ $variety->growth_period_direct ?? $variety->growth_period ?? 'N/A' }} days</td></tr>
                    <tr><td><strong>Avg Yield (Transplanted):</strong></td><td>{{ $variety->avg_yield_transplanted ? number_format($variety->avg_yield_transplanted, 2) : 'N/A' }} t/ha</td></tr>
                    <tr><td><strong>Avg Yield (Direct Seeded):</strong></td><td>{{ $variety->avg_yield_direct ? number_format($variety->avg_yield_direct, 2) : 'N/A' }} t/ha</td></tr>
                    <tr><td><strong>Max Yield (Transplanted):</strong></td><td>{{ $variety->max_yield_transplanted ? number_format($variety->max_yield_transplanted, 2) : 'N/A' }} t/ha</td></tr>
                    <tr><td><strong>Max Yield (Direct Seeded):</strong></td><td>{{ $variety->max_yield_direct ? number_format($variety->max_yield_direct, 2) : 'N/A' }} t/ha</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Resilience & Temperature -->
<div class="row g-3 mt-2">
    <div class="col-md-6">
        <div class="card" style="background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200);">
            <div class="card-body">
                <h6><i class="bi bi-shield-check"></i> Resilience & Hardiness</h6>
                @php
                    $resilience = $variety->resilience;
                    if (is_string($resilience)) {
                        $resilience = json_decode($resilience, true) ?? [];
                    }
                    if (!is_array($resilience) || empty($resilience)) {
                        $resilience = ['Bacterial Blight', 'Tungro', 'Blast'];
                    }
                @endphp
                <div class="d-flex flex-wrap gap-1">
                    @foreach($resilience as $trait)
                        <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 500; background: var(--green-light); color: var(--green); padding: 2px 12px; border-radius: 6px;">
                            <i class="bi bi-shield-check" style="font-size: 11px;"></i>
                            {{ $trait }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card" style="background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200);">
            <div class="card-body">
                <h6><i class="bi bi-thermometer-half"></i> Optimal Temperature</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr><td><strong>Minimum:</strong></td><td>{{ $variety->optimal_temp_min ? $variety->optimal_temp_min . ' °C' : 'N/A' }}</td></tr>
                    <tr><td><strong>Maximum:</strong></td><td>{{ $variety->optimal_temp_max ? $variety->optimal_temp_max . ' °C' : 'N/A' }}</td></tr>
                </table>
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