@php
    $expected = null;
    if ($farmRecord->riceVariety && $farmRecord->seeding_method) {
        $expected = $farmRecord->riceVariety->getYieldForMethod($farmRecord->seeding_method);
    }
    $latest = $farmRecord->predictions->sortByDesc('created_at')->first();
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <div class="card" style="background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200);">
            <div class="card-body">
                <h6 class="mb-3"><i class="bi bi-info-circle"></i> Season Info</h6>
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
        <div class="card" style="background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200);">
            <div class="card-body">
                <h6 class="mb-3"><i class="bi bi-clipboard-data"></i> Record Details</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr><td><strong>Fertilizer:</strong></td><td>{{ number_format($farmRecord->fertilizer_kg_ha, 2) }} kg/ha</td></tr>
                    <tr><td><strong>Last Yield:</strong></td><td>{{ $farmRecord->historical_yield_tons_ha ? number_format($farmRecord->historical_yield_tons_ha, 2) . ' t/ha' : '—' }}</td></tr>
                    <tr><td><strong>Actual Yield:</strong></td>
                        <td>
                            @if($farmRecord->status === 'Harvested' && $farmRecord->actual_yield_tons_ha)
                                <strong>{{ number_format($farmRecord->actual_yield_tons_ha, 2) }} t/ha</strong>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    <tr><td><strong>Status:</strong></td>
                        <td>
                            @if($farmRecord->status === 'Harvested')
                                <span class="badge bg-success">Harvested</span>
                            @else
                                <span class="badge bg-warning text-dark">Growing</span>
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
        <div class="card" style="background: #ecfdf5; border-radius: 10px; border: 1px solid #a7f3d0;">
            <div class="card-body">
                <h6 class="mb-2"><i class="bi bi-cpu" style="color: #4f46e5;"></i> Yield Estimates</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div style="font-size: 11px; text-transform: uppercase; color: var(--gray-500); font-weight: 600;">Expected (avg)</div>
                        <div style="font-size: 18px; font-weight: 700;">
                            @if($expected && $expected->avg !== null)
                                {{ number_format($expected->avg, 2) }} <small style="font-size: 12px;">t/ha</small>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div style="font-size: 11px; text-transform: uppercase; color: var(--gray-500); font-weight: 600;">Expected (max)</div>
                        <div style="font-size: 18px; font-weight: 700;">
                            @if($expected && $expected->max !== null)
                                {{ number_format($expected->max, 2) }} <small style="font-size: 12px;">t/ha</small>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div style="font-size: 11px; text-transform: uppercase; color: #047857; font-weight: 600;">Predicted (ML)</div>
                        <div style="font-size: 18px; font-weight: 700; color: #065f46;">
                            @if($latest)
                                {{ number_format($latest->predicted_yield_tons_ha, 2) }} <small style="font-size: 12px;">t/ha</small>
                                <div style="font-size: 11px; color: #047857; font-weight: 500;">
                                    {{ $latest->predicted_class ?? '' }} · {{ $latest->confidence ? number_format($latest->confidence * 100, 0) . '%' : '' }}
                                </div>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12 text-end">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    </div>
</div>