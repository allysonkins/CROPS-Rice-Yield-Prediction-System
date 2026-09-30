@extends('layouts.app')

@section('title', $farm->name)

@php
    $isVerified = auth()->user()->verified_by_cao_at !== null;
@endphp

@section('content')

{{-- Leaflet CSS (needed for the edit-farm map) --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<style>
    #farmerFarmModalMap {
        height: 300px;
        width: 100%;
        border-radius: var(--radius-md);
        border: 1px solid var(--slate-200);
        background: var(--slate-100);
        margin-top: 8px;
        position: relative;
        overflow: hidden;
        z-index: 1;
    }
    .leaflet-control-zoom { z-index: 1050 !important; }

    .muted-text {
        color: var(--slate-500);
    }
    .muted-text-sm {
        color: var(--slate-400);
        font-size: 11px;
    }
</style>

{{-- ═══════════ BACK ═══════════ --}}
<div class="mb-3">
    <a href="{{ route('farmer.farms.index') }}" class="text-decoration-none"
       style="font-size: 13px; color: var(--slate-500); font-weight: 500;">
        <i class="bi bi-arrow-left"></i> Back to My Farms
    </a>
</div>

{{-- ═══════════ FARM HEADER ═══════════ --}}
<div class="card-custom mb-4" style="border-top: 4px solid var(--brand-green);">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div style="min-width: 0;">
            <h4 style="font-weight: 800; color: var(--slate-900); margin: 0 0 6px; letter-spacing: -0.5px;">
                {{ $farm->name }}
            </h4>
            <div style="font-size: 13px; color: var(--slate-500);">
                <i class="bi bi-geo-alt-fill"></i> {{ $farm->barangay }}, Santiago City
            </div>
        </div>
        @if($isVerified)
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editFarmerFarm({{ $farm->id }})">
                <i class="bi bi-pencil"></i> Edit Farm
            </button>
        @endif
    </div>

    <div class="row g-3 mt-3">
        <div class="col-6 col-md-3">
            <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.5px;">Area</div>
            <div style="font-size: 20px; font-weight: 800; color: var(--brand-green); line-height: 1.1; margin-top: 4px;">
                {{ number_format($farm->land_area_ha, 2) }}
                <small style="font-size: 12px;">ha</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.5px;">Soil Type</div>
            <div style="font-size: 15px; font-weight: 600; color: var(--slate-800); margin-top: 4px;">
                {{ $farm->soil_type ?? '—' }}
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.5px;">Seasons</div>
            <div style="font-size: 20px; font-weight: 800; color: var(--brand-green); line-height: 1.1; margin-top: 4px;">
                {{ $farm->farmRecords->count() }}
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.5px;">Location</div>
            <div style="font-size: 12px; font-weight: 600; font-family: monospace; color: var(--slate-700); margin-top: 4px;">
                @if($farm->latitude && $farm->longitude)
                    {{ number_format($farm->latitude, 4) }},<br>{{ number_format($farm->longitude, 4) }}
                @else
                    <span style="color: var(--slate-400);">— not set —</span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ═══════════ SEASON RECORDS ═══════════ --}}
<div class="card-custom">
    <div class="card-title d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-clipboard-data-fill"></i> Planting Seasons</span>
        @if($isVerified)
            <button type="button" class="btn btn-sm btn-success" onclick="openFarmerFarmRecordModal({{ $farm->id }})">
                <i class="bi bi-plus-circle"></i> Add This Season
            </button>
        @else
            <button class="btn btn-sm btn-success" disabled title="Available after CAO verification">
                <i class="bi bi-lock"></i> Add This Season
            </button>
        @endif
    </div>

    @if($farm->farmRecords->isEmpty())
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 42px; color: var(--slate-300);"></i>
            <h6 class="mt-3 mb-1" style="color: var(--slate-700); font-weight: 700;">No seasons recorded yet</h6>
            <p style="font-size: 13px; color: var(--slate-500); margin: 0;">
                Record a season to get a yield prediction for this farm.
            </p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Season</th>
                        <th>Variety</th>
                        <th>Method</th>
                        <th>Fertilizer</th>
                        <th>Predicted</th>
                        <th>Actual</th>
                        <th>Status</th>
                        @if($isVerified)<th style="min-width: 160px;">Actions</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($farm->farmRecords as $record)
                        @php $latest = $record->predictions->sortByDesc('created_at')->first(); @endphp
                        <tr>
                            <td>
                                <strong style="color: var(--slate-800);">{{ $record->season }}</strong><br>
                                <span class="muted-text-sm">{{ $record->year }}</span>
                            </td>
                            <td>
                                <span style="color: var(--slate-700);">{{ $record->riceVariety->name ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span style="color: var(--slate-600); font-size: 12px;">{{ $record->seeding_method ?? '—' }}</span>
                            </td>
                            <td>
                                <span style="color: var(--slate-600);">{{ number_format($record->fertilizer_kg_ha, 0) }} kg/ha</span>
                            </td>
                            <td>
                                @if($latest)
                                    <span class="fw-bold" style="color: var(--brand-green);">
                                        {{ number_format($latest->predicted_yield_tons_ha, 2) }}
                                    </span>
                                    <small class="muted-text">t/ha</small>
                                @else
                                    <span style="color: var(--slate-400);">—</span>
                                @endif
                            </td>
                            <td>
                                @if($record->actual_yield_tons_ha)
                                    <span class="fw-bold" style="color: var(--slate-800);">
                                        {{ number_format($record->actual_yield_tons_ha, 2) }}
                                    </span>
                                    <small class="muted-text">t/ha</small>
                                @else
                                    <span style="color: var(--slate-400);">—</span>
                                @endif
                            </td>
                            <td>
                                @if($record->status === 'Harvested')
                                    <span class="badge-status"
                                          style="background: var(--brand-green-light); color: var(--brand-green-dark); border-color: #c7e6d2;">
                                        <span class="dot" style="background: var(--brand-green);"></span>
                                        Harvested
                                    </span>
                                @else
                                    <span class="badge-status"
                                          style="background: var(--brand-gold-light); color: #92400e; border-color: #fde68a;">
                                        <span class="dot" style="background: var(--brand-gold);"></span>
                                        Growing
                                    </span>
                                @endif
                            </td>
                            @if($isVerified)
                                <td style="white-space: nowrap;">
                                    <button type="button" class="btn btn-sm btn-secondary"
                                            onclick="viewFarmerFarmRecord({{ $record->id }})" title="View">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary"
                                            onclick="editFarmerFarmRecord({{ $record->id }})" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    @if($record->status === 'Vegetative')
                                        <button type="button"
                                                class="btn btn-sm btn-success harvest-btn"
                                                data-record-id="{{ $record->id }}"
                                                data-farm="{{ $farm->name }}"
                                                data-variety="{{ $record->riceVariety->name ?? 'N/A' }}"
                                                data-season="{{ $record->season }} {{ $record->year }}"
                                                data-predicted="{{ $latest ? number_format($latest->predicted_yield_tons_ha, 2, '.', '') : '' }}"
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

{{-- ═══════════ MODALS ═══════════ --}}
@if($isVerified)

{{-- Farm Record modal --}}
<div class="modal fade" id="farmerFarmRecordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--brand-green); color: white;">
                <h5 class="modal-title">
                    <i class="bi bi-clipboard-data-fill"></i>
                    <span id="farmerFarmRecordModalTitle">Add Season</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="overflow: hidden;">
                <div class="text-center py-4" id="farmerFarmRecordModalLoading">
                    <div class="spinner-border text-success" role="status"></div>
                    <p class="mt-2" style="color: var(--slate-500);">Loading form...</p>
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
            <div class="modal-header" style="background: var(--brand-green); color: white;">
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
            <div class="modal-header" style="background: var(--brand-green); color: white;">
                <h5 class="modal-title"><i class="bi bi-basket-fill"></i> Mark as Harvested</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="farmerHarvestForm">
                @csrf
                <div class="modal-body">
                    <div id="farmerHarvestError"></div>

                    <div class="p-3 mb-3" style="background: var(--slate-50); border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
                        <div style="font-size: 11px; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.4px;">
                            Season
                        </div>
                        <div id="farmerHarvestFarm" style="font-size: 16px; font-weight: 700; color: var(--brand-green-dark); margin-top: 2px;"></div>
                        <div id="farmerHarvestMeta" style="font-size: 13px; color: var(--slate-500); margin-top: 2px;"></div>
                    </div>

                    <div id="farmerHarvestSuggestion"></div>

                    <div>
                        <label class="form-label fw-semibold">Actual Yield (t/ha) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" max="20"
                               name="actual_yield_tons_ha" id="farmerActualYieldInput"
                               class="form-control form-control-lg" placeholder="e.g., 4.80" required>
                        <small style="color: var(--slate-500);">How much did you actually harvest?</small>
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

{{-- Farm edit modal --}}
<div class="modal fade" id="farmerFarmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--brand-green); color: white;">
                <h5 class="modal-title"><i class="bi bi-geo-alt-fill"></i> <span id="farmerFarmModalTitle">Edit Farm</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="overflow: hidden;">
                <div class="text-center py-4" id="farmerFarmModalLoading">
                    <div class="spinner-border text-success" role="status"></div>
                    <p class="mt-2" style="color: var(--slate-500);">Loading form...</p>
                </div>
                <div id="farmerFarmModalContent" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

@endif

@endsection

@push('scripts')
@if($isVerified)
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // ═══════════════════════════════════════════════════════════
    // MAP STATE (used only inside the Edit Farm modal)
    // ═══════════════════════════════════════════════════════════
    let fModalMap = null;
    let fModalMarker = null;

    const FSANTIAGO_BOUNDS = { minLat: 16.60, maxLat: 16.75, minLng: 121.48, maxLng: 121.63 };

    function fClampToSantiago(lat, lng) {
        return {
            lat: Math.min(Math.max(lat, FSANTIAGO_BOUNDS.minLat), FSANTIAGO_BOUNDS.maxLat),
            lng: Math.min(Math.max(lng, FSANTIAGO_BOUNDS.minLng), FSANTIAGO_BOUNDS.maxLng)
        };
    }

    function initFarmerFarmModalMap() {
        const container = document.getElementById('farmerFarmModalMap');
        if (!container) { setTimeout(initFarmerFarmModalMap, 200); return; }

        // Destroy any previous instance
        if (fModalMap) {
            try { fModalMap.off(); fModalMap.remove(); } catch (e) {}
            fModalMap = null;
            fModalMarker = null;
        }

        const latInput = document.getElementById('farmer_latitude');
        const lngInput = document.getElementById('farmer_longitude');
        let lat = latInput && latInput.value ? parseFloat(latInput.value) : 16.6870;
        let lng = lngInput && lngInput.value ? parseFloat(lngInput.value) : 121.5480;
        const c = fClampToSantiago(lat, lng);
        lat = c.lat; lng = c.lng;

        fModalMap = L.map(container, {
            center: [lat, lng],
            zoom: 13,
            maxBounds: [
                [FSANTIAGO_BOUNDS.minLat - 0.02, FSANTIAGO_BOUNDS.minLng - 0.02],
                [FSANTIAGO_BOUNDS.maxLat + 0.02, FSANTIAGO_BOUNDS.maxLng + 0.02]
            ],
            maxBoundsViscosity: 0.8
        });

        const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' });
        const satImg = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: '&copy; Esri' });
        const satLabels = L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', { attribution: '&copy; Esri' });
        const satelliteLayer = L.layerGroup([satImg, satLabels]);
        streetLayer.addTo(fModalMap);

        // Street/Satellite toggle button
        const ToggleControl = L.Control.extend({
            options: { position: 'topright' },
            onAdd: function (map) {
                const btn = L.DomUtil.create('button', 'btn btn-sm btn-secondary shadow-sm');
                btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Satellite';
                btn.style.margin = '10px';
                btn.style.pointerEvents = 'auto';
                L.DomEvent.disableClickPropagation(btn);

                let isSat = false;
                L.DomEvent.on(btn, 'click', function (e) {
                    L.DomEvent.preventDefault(e);
                    if (isSat) {
                        map.removeLayer(satelliteLayer);
                        streetLayer.addTo(map);
                        btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Satellite';
                        btn.classList.replace('btn-primary', 'btn-secondary');
                        isSat = false;
                    } else {
                        map.removeLayer(streetLayer);
                        satelliteLayer.addTo(map);
                        btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Street';
                        btn.classList.replace('btn-secondary', 'btn-primary');
                        isSat = true;
                    }
                });
                return btn;
            }
        });
        fModalMap.addControl(new ToggleControl());

        // Existing marker
        if (latInput && latInput.value && lngInput && lngInput.value) {
            fModalMarker = L.marker([lat, lng], { draggable: true }).addTo(fModalMap);
            fModalMarker.on('dragend', function () {
                const pos = fModalMarker.getLatLng();
                const cl = fClampToSantiago(pos.lat, pos.lng);
                fModalMarker.setLatLng(cl);
                latInput.value = cl.lat.toFixed(8);
                lngInput.value = cl.lng.toFixed(8);
            });
        }

        // Click to place
        fModalMap.on('click', function (e) {
            const cl = fClampToSantiago(e.latlng.lat, e.latlng.lng);
            fSetModalMarker(cl.lat, cl.lng);
        });

        // Live sync on typing
        if (latInput && lngInput) {
            const sync = () => {
                const tLat = parseFloat(latInput.value);
                const tLng = parseFloat(lngInput.value);
                if (!isNaN(tLat) && !isNaN(tLng)) {
                    const cl = fClampToSantiago(tLat, tLng);
                    fSetModalMarker(cl.lat, cl.lng, false);
                }
            };
            latInput.addEventListener('input', sync);
            lngInput.addEventListener('input', sync);
        }

        setTimeout(() => { if (fModalMap) fModalMap.invalidateSize(true); }, 300);
        setTimeout(() => { if (fModalMap) fModalMap.invalidateSize(true); }, 600);
    }

    function fSetModalMarker(lat, lng, pan = true) {
        const cl = fClampToSantiago(lat, lng);
        lat = cl.lat; lng = cl.lng;

        const latInput = document.getElementById('farmer_latitude');
        const lngInput = document.getElementById('farmer_longitude');
        if (!latInput || !lngInput) return;

        latInput.value = lat.toFixed(8);
        lngInput.value = lng.toFixed(8);

        if (fModalMarker) {
            fModalMarker.setLatLng([lat, lng]);
        } else if (fModalMap) {
            fModalMarker = L.marker([lat, lng], { draggable: true }).addTo(fModalMap);
            fModalMarker.on('dragend', function () {
                const pos = fModalMarker.getLatLng();
                const cl2 = fClampToSantiago(pos.lat, pos.lng);
                fModalMarker.setLatLng(cl2);
                latInput.value = cl2.lat.toFixed(8);
                lngInput.value = cl2.lng.toFixed(8);
            });
        }
        if (fModalMap && pan) fModalMap.setView([lat, lng], fModalMap.getZoom());
    }

    // Clear-location handler (delegated)
    document.addEventListener('click', function (e) {
        if (e.target.id === 'farmer_clearLocation' || e.target.closest('#farmer_clearLocation')) {
            const latInput = document.getElementById('farmer_latitude');
            const lngInput = document.getElementById('farmer_longitude');
            if (latInput) latInput.value = '';
            if (lngInput) lngInput.value = '';
            if (fModalMarker && fModalMap) {
                fModalMap.removeLayer(fModalMarker);
                fModalMarker = null;
            }
        }
    });

    // Cleanup when the farm modal closes
    document.addEventListener('hidden.bs.modal', function (e) {
        if (e.target.id === 'farmerFarmModal') {
            if (fModalMap) {
                try { fModalMap.off(); fModalMap.remove(); } catch (ex) {}
                fModalMap = null;
                fModalMarker = null;
            }
        }
    });

    // ═══════════════════════════════════════════════════════════
    // ADD SEASON MODAL
    // ═══════════════════════════════════════════════════════════
    function openFarmerFarmRecordModal(farmId) {
        const titleEl   = document.getElementById('farmerFarmRecordModalTitle');
        const loadingEl = document.getElementById('farmerFarmRecordModalLoading');
        const contentEl = document.getElementById('farmerFarmRecordModalContent');

        titleEl.textContent = 'Add Season';
        loadingEl.style.display = 'block';
        contentEl.style.display = 'none';
        contentEl.innerHTML = '';

        const modal = new bootstrap.Modal(document.getElementById('farmerFarmRecordModal'));
        modal.show();

        const url = '{{ route("farmer.farm-records.create") }}' + (farmId ? '?farm_id=' + farmId : '');

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status + ' — ' + r.statusText);
                return r.text();
            })
            .then(html => {
                loadingEl.style.display = 'none';
                contentEl.style.display = 'block';
                contentEl.innerHTML = html;

                const form = document.getElementById('farmerFarmRecordForm');
                if (form) {
                    form.addEventListener('submit', submitFarmerFarmRecord);
                } else {
                    // Fail loudly if the form ID changed
                    contentEl.insertAdjacentHTML('afterbegin',
                        '<div class="alert-custom alert-custom-danger mb-3">' +
                        '<i class="bi bi-exclamation-triangle-fill alert-icon"></i>' +
                        '<div class="alert-content">Form failed to load. Please refresh the page and try again.</div>' +
                        '</div>');
                }
            })
            .catch(err => {
                loadingEl.innerHTML =
                    '<div class="text-center py-4">' +
                    '<i class="bi bi-exclamation-triangle-fill" style="font-size: 32px; color: var(--brand-danger);"></i>' +
                    '<p class="mt-2" style="color: var(--brand-danger); font-size: 13px;">Failed to load form: ' + err.message + '</p>' +
                    '</div>';
            });
    }

    // ═══════════════════════════════════════════════════════════
    // EDIT SEASON MODAL
    // ═══════════════════════════════════════════════════════════
    function editFarmerFarmRecord(id) {
        const titleEl   = document.getElementById('farmerFarmRecordModalTitle');
        const loadingEl = document.getElementById('farmerFarmRecordModalLoading');
        const contentEl = document.getElementById('farmerFarmRecordModalContent');

        titleEl.textContent = 'Edit Season';
        loadingEl.style.display = 'block';
        contentEl.style.display = 'none';
        contentEl.innerHTML = '';

        const modal = new bootstrap.Modal(document.getElementById('farmerFarmRecordModal'));
        modal.show();

        fetch('/farmer/farm-records/' + id + '/edit', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(html => {
                loadingEl.style.display = 'none';
                contentEl.style.display = 'block';
                contentEl.innerHTML = html;

                const form = document.getElementById('farmerFarmRecordForm');
                if (form) form.addEventListener('submit', submitFarmerFarmRecord);
            })
            .catch(err => {
                loadingEl.innerHTML =
                    '<div class="text-center py-4">' +
                    '<p style="color: var(--brand-danger); font-size: 13px;">Failed to load form: ' + err.message + '</p>' +
                    '</div>';
            });
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
                let msg = data.error || 'An error occurred.';
                if (data.errors) msg = Object.values(data.errors).flat().join('<br>');
                const div = document.getElementById('formErrors') || document.createElement('div');
                div.id = 'formErrors';
                div.className = 'alert-custom alert-custom-danger mb-3';
                div.innerHTML = '<i class="bi bi-exclamation-triangle-fill alert-icon"></i>' +
                                '<div class="alert-content">' + msg + '</div>';
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
    // VIEW SEASON
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
            });

        new bootstrap.Modal(document.getElementById('farmerFarmRecordViewModal')).show();
    }

    // ═══════════════════════════════════════════════════════════
    // HARVEST
    // ═══════════════════════════════════════════════════════════
    function openFarmerHarvestModal(btn) {
        const id        = btn.dataset.recordId;
        const farm      = btn.dataset.farm;
        const variety   = btn.dataset.variety;
        const season    = btn.dataset.season;
        const predicted = btn.dataset.predicted;

        const form = document.getElementById('farmerHarvestForm');
        form.action = '/farmer/farm-records/' + id + '/mark-harvested';

        document.getElementById('farmerHarvestError').innerHTML = '';
        document.getElementById('farmerHarvestFarm').textContent = farm;
        document.getElementById('farmerHarvestMeta').textContent = variety + ' · ' + season;

        const suggestion = document.getElementById('farmerHarvestSuggestion');
        if (predicted) {
            suggestion.innerHTML = `
                <div class="p-3 mb-3" style="background: var(--brand-green-light); border: 1px solid #a7f3d0; border-radius: var(--radius-md);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div style="font-size: 11px; text-transform: uppercase; color: var(--brand-green-dark); font-weight: 700;">
                                <i class="bi bi-cpu"></i> System Predicted
                            </div>
                            <div style="font-size: 22px; font-weight: 800; color: var(--brand-green-dark);">
                                ${predicted} <span style="font-size: 14px;">t/ha</span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-success" onclick="useFarmerSuggestedYield('${predicted}')">
                            <i class="bi bi-arrow-down-circle"></i> Use this
                        </button>
                    </div>
                </div>`;
        } else {
            suggestion.innerHTML = '';
        }

        document.getElementById('farmerActualYieldInput').value = '';
        new bootstrap.Modal(document.getElementById('farmerHarvestModal')).show();
    }

    function useFarmerSuggestedYield(value) {
        const input = document.getElementById('farmerActualYieldInput');
        if (input) { input.value = value; input.focus(); }
    }

    document.getElementById('farmerHarvestForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const btn = document.getElementById('farmerHarvestBtn');
        const original = btn.innerHTML;
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
                    '<div class="alert-custom alert-custom-danger mb-3">' +
                    '<i class="bi bi-exclamation-triangle-fill alert-icon"></i>' +
                    '<div class="alert-content">' + msg + '</div></div>';
                btn.disabled = false;
                btn.innerHTML = original;
            }
        })
        .catch(() => {
            document.getElementById('farmerHarvestError').innerHTML =
                '<div class="alert-custom alert-custom-danger mb-3">' +
                '<i class="bi bi-x-circle-fill alert-icon"></i>' +
                '<div class="alert-content">Network error.</div></div>';
            btn.disabled = false;
            btn.innerHTML = original;
        });
    });

    // ═══════════════════════════════════════════════════════════
    // EDIT FARM — WITH MAP
    // ═══════════════════════════════════════════════════════════
    function editFarmerFarm(id) {
        const titleEl   = document.getElementById('farmerFarmModalTitle');
        const loadingEl = document.getElementById('farmerFarmModalLoading');
        const contentEl = document.getElementById('farmerFarmModalContent');

        titleEl.textContent = 'Edit Farm';
        loadingEl.style.display = 'block';
        contentEl.style.display = 'none';
        contentEl.innerHTML = '';

        const modal = new bootstrap.Modal(document.getElementById('farmerFarmModal'));
        modal.show();

        fetch('/farmer/farms/' + id + '/edit', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(html => {
                loadingEl.style.display = 'none';
                contentEl.style.display = 'block';
                contentEl.innerHTML = html;

                const form = document.getElementById('farmerFarmForm');
                if (form) {
                    form.addEventListener('submit', function (e) {
                        e.preventDefault();
                        const submitBtn = form.querySelector('button[type="submit"]');
                        const orig = submitBtn.innerHTML;
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

                        const existing = form.querySelector('#formErrors');
                        if (existing) existing.innerHTML = '';

                        fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                bootstrap.Modal.getInstance(document.getElementById('farmerFarmModal')).hide();
                                location.reload();
                            } else {
                                let msg = data.error || 'An error occurred.';
                                if (data.errors) msg = Object.values(data.errors).flat().join('<br>');
                                const div = document.getElementById('formErrors') || document.createElement('div');
                                div.id = 'formErrors';
                                div.className = 'alert-custom alert-custom-danger mb-3';
                                div.innerHTML = '<i class="bi bi-exclamation-triangle-fill alert-icon"></i>' +
                                                '<div class="alert-content">' + msg + '</div>';
                                form.prepend(div);
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = orig;
                            }
                        })
                        .catch(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = orig;
                        });
                    });
                }

                // ⬇️ THIS is the missing piece — initialize the map AFTER the form is in the DOM
                setTimeout(initFarmerFarmModalMap, 300);
            })
            .catch(err => {
                loadingEl.innerHTML =
                    '<div class="text-center py-4">' +
                    '<p style="color: var(--brand-danger); font-size: 13px;">Failed to load farm: ' + err.message + '</p>' +
                    '</div>';
            });
    }
</script>
@endif
@endpush