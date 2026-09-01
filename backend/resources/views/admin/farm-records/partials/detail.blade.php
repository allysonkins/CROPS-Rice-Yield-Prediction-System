<!-- ============================================================ -->
<!-- FARM RECORD DETAILS (loaded into modal)                      -->
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
                <h6><i class="bi bi-cpu" style="color: #4f46e5;"></i> Expected Yield</h6>
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

<!-- Close Button -->
<div class="row mt-3">
    <div class="col-12 text-end">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    </div>
</div>