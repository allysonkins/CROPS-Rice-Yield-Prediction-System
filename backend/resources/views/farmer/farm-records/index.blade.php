@extends('layouts.app')

@section('title', 'My Seasons')

@php
    $isVerified = auth()->user()->verified_by_cao_at !== null;
@endphp

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <p class="text-muted mb-0" style="font-size: 13px;">
            Every planting season you have recorded, across all your farms.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        @if($isVerified)
            <button type="button" class="btn btn-success" onclick="openFarmerFarmRecordModal()">
                <i class="bi bi-plus-circle"></i> Add Season
            </button>
        @else
            <button class="btn btn-success" disabled title="Available after CAO verification">
                <i class="bi bi-lock"></i> Add Season
            </button>
        @endif
        <span class="badge bg-secondary">{{ $farmRecords->count() }} Season{{ $farmRecords->count() === 1 ? '' : 's' }}</span>
    </div>
</div>

{{-- Stats --}}
<div class="row g-2 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Total Seasons</div>
                <div class="value">{{ $farmRecords->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-clipboard-data-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Growing</div>
                <div class="value">{{ $farmRecords->where('status', 'Vegetative')->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-flower1"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Harvested</div>
                <div class="value">{{ $farmRecords->where('status', 'Harvested')->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-basket-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Farms</div>
                <div class="value">{{ $farmRecords->pluck('farm_id')->unique()->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-geo-alt-fill"></i></div>
        </div>
    </div>
</div>

{{-- INFO BANNER --}}
<div class="card-custom mb-3" style="padding: 14px 20px;">
    <div class="d-flex align-items-start gap-3 flex-wrap">
        <i class="bi bi-info-circle-fill" style="color: var(--brand-green); font-size: 18px; flex-shrink: 0; margin-top: 2px;"></i>
        <div style="flex: 1; min-width: 260px;">
            <div style="font-size: 13px; font-weight: 700; color: var(--slate-800); margin-bottom: 4px;">
                How to read your yields
            </div>
            <div style="font-size: 12px; color: var(--slate-600); line-height: 1.6;">
                <strong>Predicted</strong> is the estimated yield from the system.
                <strong>Actual</strong> is the real yield you recorded after harvest.
                All yields are shown in <strong>cavan per hectare</strong> (1 cavan ≈ 50 kg).
            </div>
        </div>
    </div>
</div>

{{-- Seasons Table --}}
<div class="card-custom">
    <div class="card-title">
        <i class="bi bi-table"></i> Planting Seasons
    </div>

    @if($farmRecords->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox" style="font-size: 42px; color: var(--gray-300);"></i>
            <h5 class="mt-3 mb-2">No seasons recorded yet</h5>
            <p class="mb-4" style="font-size: 14px;">
                Record your first planting season to get a yield prediction.
            </p>
            @if($isVerified)
                <button type="button" class="btn btn-success" onclick="openFarmerFarmRecordModal()">
                    <i class="bi bi-plus-circle"></i> Add My First Season
                </button>
            @else
                <button class="btn btn-success" disabled title="Available after CAO verification">
                    <i class="bi bi-lock"></i> Add My First Season
                </button>
            @endif
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Farm</th>
                        <th>Season / Year</th>
                        <th>Variety</th>
                        <th>Predicted</th>
                        <th>Actual</th>
                        <th>Status</th>
                        @if($isVerified)<th style="min-width: 150px;">Actions</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($farmRecords as $record)
                        @php
                            $latest = $record->predictions->sortByDesc('created_at')->first();
                            $predictedCavan = $latest && $latest->predicted_yield_tons_ha
                                ? t_ha_to_cavan_ha((float) $latest->predicted_yield_tons_ha)
                                : null;
                            $actualCavan = $record->actual_yield_tons_ha
                                ? t_ha_to_cavan_ha((float) $record->actual_yield_tons_ha)
                                : null;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $record->farm->name ?? 'N/A' }}</strong>
                                <br><small class="text-muted">{{ $record->farm->barangay ?? '' }}</small>
                            </td>
                            <td>
                                {{ $record->season }}<br>
                                <span class="badge bg-light text-dark" style="font-size: 10px;">{{ $record->year }}</span>
                            </td>
                            <td>{{ $record->riceVariety->name ?? 'N/A' }}</td>
                            <td>
                                @if($predictedCavan !== null)
                                    <span class="fw-bold text-success">
                                        {{ number_format($predictedCavan, 0) }}
                                    </span>
                                    <small class="text-muted">cavan/ha</small>
                                    @if($latest->predicted_class)
                                        <br><small class="text-muted" style="font-size: 10px;">
                                            {{ $latest->predicted_class }}
                                            @if($latest->confidence)
                                                · {{ number_format($latest->confidence * 100, 0) }}% sure
                                            @endif
                                        </small>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($actualCavan !== null)
                                    <span class="fw-bold">{{ number_format($actualCavan, 0) }}</span>
                                    <small class="text-muted">cavan/ha</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($record->status === 'Harvested')
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle-fill"></i> Harvested
                                    </span>
                                @else
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-flower1"></i> Growing
                                    </span>
                                @endif
                            </td>
                            @if($isVerified)
                                <td style="white-space: nowrap;">
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="viewFarmerFarmRecord({{ $record->id }})" title="View">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="editFarmerFarmRecord({{ $record->id }})" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    @if($record->status === 'Vegetative')
                                        <button type="button"
                                                class="btn btn-sm btn-success harvest-btn"
                                                data-record-id="{{ $record->id }}"
                                                data-farm="{{ $record->farm->name ?? 'N/A' }}"
                                                data-variety="{{ $record->riceVariety->name ?? 'N/A' }}"
                                                data-season="{{ $record->season }} {{ $record->year }}"
                                                data-predicted="{{ $latest ? number_format($latest->predicted_yield_tons_ha, 2, '.', '') : '' }}"
                                                data-predicted-cavan="{{ $predictedCavan !== null ? number_format($predictedCavan, 0, '.', '') : '' }}"
                                                onclick="openFarmerHarvestModal(this)"
                                                title="Mark Harvested">
                                            <i class="bi bi-basket-fill"></i>
                                        </button>
                                    @endif
                                    <form action="{{ route('farmer.farm-records.destroy', $record->id) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this season record? This cannot be undone.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ══════════ MODALS ══════════ --}}
@if($isVerified)

{{-- Farm Record modal --}}
<div class="modal fade" id="farmerFarmRecordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--green); color: white;">
                <h5 class="modal-title">
                    <i class="bi bi-clipboard-data-fill"></i>
                    <span id="farmerFarmRecordModalTitle">Add Season</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="overflow: hidden;">
                <div class="text-center py-4" id="farmerFarmRecordModalLoading">
                    <div class="spinner-border text-success" role="status"></div>
                    <p class="mt-2">Loading form...</p>
                </div>
                <div id="farmerFarmRecordModalContent" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

{{-- View details modal --}}
<div class="modal fade" id="farmerFarmRecordViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--green); color: white;">
                <h5 class="modal-title"><i class="bi bi-eye"></i> Season Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-4" id="farmerViewLoading">
                    <div class="spinner-border text-success" role="status"></div>
                </div>
                <div id="farmerViewContent" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

{{-- Harvest modal --}}
<div class="modal fade" id="farmerHarvestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--green); color: white;">
                <h5 class="modal-title"><i class="bi bi-basket-fill"></i> Mark as Harvested</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="farmerHarvestForm">
                @csrf
                <div class="modal-body">
                    <div id="farmerHarvestError"></div>

                    <div class="card mb-3" style="background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200);">
                        <div class="card-body py-3">
                            <div style="font-size: 11px; text-transform: uppercase; color: var(--gray-500); font-weight: 700;">Season</div>
                            <div id="farmerHarvestFarm" style="font-size: 16px; font-weight: 700; color: var(--green-dark);"></div>
                            <div id="farmerHarvestMeta" class="text-muted" style="font-size: 13px;"></div>
                        </div>
                    </div>

                    <div id="farmerHarvestSuggestion"></div>

                    <div>
                        <label class="form-label fw-semibold">
                            Actual Yield (cavan/ha) <span class="text-danger">*</span>
                        </label>
                        <input type="number" step="0.1" min="0" max="400"
                               name="actual_yield_cavan_ha" id="farmerActualYieldInput"
                               class="form-control form-control-lg" placeholder="e.g., 90" required>
                        <small class="text-muted">
                            Enter the total cavan you harvested, divided by your farm's hectares.
                            Example: 45 cavan on 0.5 ha = <strong>90 cavan/ha</strong>.
                        </small>
                        <input type="hidden" name="actual_yield_tons_ha" id="farmerActualYieldTons">
                        <div id="farmerCavanPreview" style="font-size: 12px; color: var(--slate-500); margin-top: 6px;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="farmerHarvestBtn">
                        <i class="bi bi-basket-fill"></i> Confirm Harvest
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endif

@endsection

@push('scripts')
@if($isVerified)
<script>
    // ═══════════════════════════════════════════════════════════
    // CAVAN → TONS CONVERSION HELPERS
    // 1 cavan = 50 kg = 0.05 tons
    // ═══════════════════════════════════════════════════════════
    const CAVAN_TO_TONS = 0.05;

    function cavanHaToTonsHa(cavanHa) {
        return cavanHa * CAVAN_TO_TONS;
    }

    // ═══════════════════════════════════════════════════════════
    // FARM RECORD MODAL — ADD / EDIT
    // ═══════════════════════════════════════════════════════════
    function openFarmerFarmRecordModal(farmId) {
        document.getElementById('farmerFarmRecordModalTitle').textContent = 'Add Season';
        document.getElementById('farmerFarmRecordModalLoading').style.display = 'block';
        document.getElementById('farmerFarmRecordModalContent').style.display = 'none';
        document.getElementById('farmerFarmRecordModalContent').innerHTML = '';

        const url = '{{ route("farmer.farm-records.create") }}' + (farmId ? '?farm_id=' + farmId : '');

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => {
                document.getElementById('farmerFarmRecordModalLoading').style.display = 'none';
                document.getElementById('farmerFarmRecordModalContent').style.display = 'block';
                document.getElementById('farmerFarmRecordModalContent').innerHTML = html;
                const form = document.getElementById('farmerFarmRecordForm');
                if (form) form.addEventListener('submit', submitFarmerFarmRecord);
            })
            .catch(() => {
                document.getElementById('farmerFarmRecordModalLoading').innerHTML =
                    '<p class="text-danger">Failed to load form.</p>';
            });

        new bootstrap.Modal(document.getElementById('farmerFarmRecordModal')).show();
    }

    function editFarmerFarmRecord(id) {
        document.getElementById('farmerFarmRecordModalTitle').textContent = 'Edit Season';
        document.getElementById('farmerFarmRecordModalLoading').style.display = 'block';
        document.getElementById('farmerFarmRecordModalContent').style.display = 'none';
        document.getElementById('farmerFarmRecordModalContent').innerHTML = '';

        fetch('/farmer/farm-records/' + id + '/edit', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => {
                document.getElementById('farmerFarmRecordModalLoading').style.display = 'none';
                document.getElementById('farmerFarmRecordModalContent').style.display = 'block';
                document.getElementById('farmerFarmRecordModalContent').innerHTML = html;
                const form = document.getElementById('farmerFarmRecordForm');
                if (form) form.addEventListener('submit', submitFarmerFarmRecord);
            })
            .catch(() => {
                document.getElementById('farmerFarmRecordModalLoading').innerHTML =
                    '<p class="text-danger">Failed to load form.</p>';
            });

        new bootstrap.Modal(document.getElementById('farmerFarmRecordModal')).show();
    }

    function submitFarmerFarmRecord(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

        const existing = form.querySelector('#formErrors');
        if (existing) existing.innerHTML = '';

        fetch(form.action, {
            method: form.method || 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('farmerFarmRecordModal')).hide();
                location.reload();
            } else {
                const div = document.getElementById('formErrors') || document.createElement('div');
                div.id = 'formErrors';
                div.className = 'alert alert-danger mb-3';
                let msg = data.error || 'An error occurred.';
                if (data.errors) msg = Object.values(data.errors).flat().join('<br>');
                div.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + msg;
                form.prepend(div);
                btn.disabled = false;
                btn.innerHTML = original;
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = original;
            alert('Network error. Please try again.');
        });
    }

    // ═══════════════════════════════════════════════════════════
    // VIEW SEASON DETAILS
    // ═══════════════════════════════════════════════════════════
    function viewFarmerFarmRecord(id) {
        document.getElementById('farmerViewLoading').style.display = 'block';
        document.getElementById('farmerViewContent').style.display = 'none';
        document.getElementById('farmerViewContent').innerHTML = '';

        fetch('/farmer/farm-records/' + id, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => {
                document.getElementById('farmerViewLoading').style.display = 'none';
                document.getElementById('farmerViewContent').style.display = 'block';
                document.getElementById('farmerViewContent').innerHTML = html;
            })
            .catch(() => {
                document.getElementById('farmerViewLoading').innerHTML = '<p class="text-danger">Failed to load.</p>';
            });

        new bootstrap.Modal(document.getElementById('farmerFarmRecordViewModal')).show();
    }

    // ═══════════════════════════════════════════════════════════
    // MARK AS HARVESTED
    // ═══════════════════════════════════════════════════════════
    function openFarmerHarvestModal(btn) {
        const id              = btn.dataset.recordId;
        const farm            = btn.dataset.farm;
        const variety         = btn.dataset.variety;
        const season          = btn.dataset.season;
        const predictedTons   = btn.dataset.predicted;
        const predictedCavan  = btn.dataset.predictedCavan;

        const form = document.getElementById('farmerHarvestForm');
        form.action = '/farmer/farm-records/' + id + '/mark-harvested';

        document.getElementById('farmerHarvestError').innerHTML = '';
        document.getElementById('farmerHarvestFarm').textContent = farm;
        document.getElementById('farmerHarvestMeta').textContent = variety + ' · ' + season;

        const suggestion = document.getElementById('farmerHarvestSuggestion');
        if (predictedCavan && predictedCavan !== '') {
            suggestion.innerHTML = `
                <div class="card mb-3" style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px;">
                    <div class="card-body py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div style="font-size: 11px; text-transform: uppercase; color: #047857; font-weight: 700;">
                                <i class="bi bi-cpu"></i> System Predicted
                            </div>
                            <div style="font-size: 22px; font-weight: 800; color: #065f46;">
                                ${predictedCavan} <span style="font-size: 14px;">cavan/ha</span>
                            </div>
                            <div style="font-size: 11px; color: #047857; opacity: 0.8;">
                                ≈ ${predictedTons} t/ha
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-success" onclick="useFarmerSuggestedYield('${predictedCavan}')">
                            <i class="bi bi-arrow-down-circle"></i> Use this
                        </button>
                    </div>
                </div>`;
        } else {
            suggestion.innerHTML = '';
        }

        document.getElementById('farmerActualYieldInput').value = '';
        document.getElementById('farmerActualYieldTons').value = '';
        document.getElementById('farmerCavanPreview').innerHTML = '';

        new bootstrap.Modal(document.getElementById('farmerHarvestModal')).show();
    }

    function useFarmerSuggestedYield(cavanHa) {
        const input = document.getElementById('farmerActualYieldInput');
        if (input) {
            input.value = cavanHa;
            input.dispatchEvent(new Event('input'));
            input.focus();
        }
    }

    // ═══════════════════════════════════════════════════════════
    // LIVE CONVERSION PREVIEW — cavan/ha → t/ha
    // ═══════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', function() {
        const cavanInput = document.getElementById('farmerActualYieldInput');
        const tonsHidden = document.getElementById('farmerActualYieldTons');
        const preview    = document.getElementById('farmerCavanPreview');

        if (cavanInput) {
            cavanInput.addEventListener('input', function() {
                const cavanHa = parseFloat(this.value);
                if (!isNaN(cavanHa) && cavanHa >= 0) {
                    const tonsHa = cavanHaToTonsHa(cavanHa);
                    tonsHidden.value = tonsHa.toFixed(2);
                    preview.innerHTML = `
                        <i class="bi bi-arrow-right-circle"></i>
                        Equivalent to <strong>${tonsHa.toFixed(2)} t/ha</strong>
                    `;
                } else {
                    tonsHidden.value = '';
                    preview.innerHTML = '';
                }
            });
        }
    });

    document.getElementById('farmerHarvestForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const btn = document.getElementById('farmerHarvestBtn');
        const original = btn.innerHTML;

        // Ensure tons conversion is set before submitting
        const cavanInput = document.getElementById('farmerActualYieldInput');
        const tonsHidden = document.getElementById('farmerActualYieldTons');
        const cavanVal = parseFloat(cavanInput?.value);
        if (!isNaN(cavanVal) && cavanVal >= 0) {
            tonsHidden.value = cavanHaToTonsHa(cavanVal).toFixed(2);
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
        document.getElementById('farmerHarvestError').innerHTML = '';

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('farmerHarvestModal')).hide();
                location.reload();
            } else {
                let msg = data.error || 'Failed to save.';
                if (data.errors) msg = Object.values(data.errors).flat().join('<br>');
                document.getElementById('farmerHarvestError').innerHTML =
                    '<div class="alert alert-danger">' + msg + '</div>';
                btn.disabled = false;
                btn.innerHTML = original;
            }
        })
        .catch(() => {
            document.getElementById('farmerHarvestError').innerHTML =
                '<div class="alert alert-danger">Network error. Please try again.</div>';
            btn.disabled = false;
            btn.innerHTML = original;
        });
    });
</script>
@endif
@endpush