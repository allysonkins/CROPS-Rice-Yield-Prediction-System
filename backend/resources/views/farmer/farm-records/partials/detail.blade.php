@php
    // ── Actual yield (t/ha → cavan/ha) ──
    $actualTons  = $farmRecord->actual_yield_tons_ha;
    $actualCavan = $actualTons ? t_ha_to_cavan_ha((float) $actualTons) : null;

    // ── Historical yield (t/ha → cavan/ha) ──
    $historicalTons  = $farmRecord->historical_yield_tons_ha;
    $historicalCavan = $historicalTons ? t_ha_to_cavan_ha((float) $historicalTons) : null;
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <div class="card" style="background: var(--slate-50); border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
            <div class="card-body">
                <h6 class="mb-3" style="color: var(--slate-800); font-weight: 700;">
                    <i class="bi bi-info-circle"></i> Season Info
                </h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr><td><strong>Farm:</strong></td><td>{{ $farmRecord->farm->name ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Barangay:</strong></td><td>{{ $farmRecord->farm->barangay ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Variety:</strong></td><td>{{ $farmRecord->riceVariety->name ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Season:</strong></td><td>{{ $farmRecord->season ?? 'N/A' }} {{ $farmRecord->year ?? '' }}</td></tr>
                    <tr><td><strong>Method:</strong></td><td>{{ $farmRecord->seeding_method ?? '—' }}</td></tr>
                    <tr>
                        <td><strong>Water:</strong></td>
                        <td>
                            @if($farmRecord->water_source === 'Rainfed')
                                <span class="badge-status" style="background: var(--brand-gold-light); color: #92400e; border-color: #fde68a; font-size: 11px;">
                                    <span class="dot" style="background: var(--brand-gold);"></span> Rainfed
                                </span>
                            @elseif($farmRecord->water_source === 'Irrigated')
                                <span class="badge-status" style="background: #dbeafe; color: #1e40af; border-color: #bfdbfe; font-size: 11px;">
                                    <span class="dot" style="background: #1d4ed8;"></span> Irrigated
                                </span>
                            @elseif($farmRecord->water_source === 'Both')
                                <span class="badge-status" style="background: var(--brand-green-light); color: var(--brand-green-dark); border-color: #c7e6d2; font-size: 11px;">
                                    <span class="dot" style="background: var(--brand-green);"></span> Both
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card" style="background: var(--slate-50); border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
            <div class="card-body">
                <h6 class="mb-3" style="color: var(--slate-800); font-weight: 700;">
                    <i class="bi bi-clipboard-data"></i> Record Details
                </h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td><strong>Fertilizer:</strong></td>
                        <td>{{ number_format($farmRecord->fertilizer_kg_ha, 2) }} kg/ha</td>
                    </tr>
                    <tr>
                        <td><strong>Last Yield:</strong></td>
                        <td>
                            @if($historicalCavan !== null)
                                <strong>{{ number_format($historicalCavan, 0) }} cavan/ha</strong>
                                <br><small class="text-muted">({{ number_format($historicalTons, 2) }} t/ha)</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Actual Yield:</strong></td>
                        <td>
                            @if($farmRecord->status === 'Harvested' && $actualCavan !== null)
                                <strong>{{ number_format($actualCavan, 0) }} cavan/ha</strong>
                                <br><small class="text-muted">({{ number_format($actualTons, 2) }} t/ha)</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            @if($farmRecord->status === 'Harvested')
                                <span class="badge-status" style="background: var(--brand-green-light); color: var(--brand-green-dark); border-color: #c7e6d2;">
                                    <span class="dot" style="background: var(--brand-green);"></span> Harvested
                                </span>
                            @else
                                <span class="badge-status" style="background: var(--brand-gold-light); color: #92400e; border-color: #fde68a;">
                                    <span class="dot" style="background: var(--brand-gold);"></span> Growing
                                </span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12 text-end">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    </div>
</div>