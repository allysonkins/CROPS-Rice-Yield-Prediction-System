@extends('layouts.app')

@section('title', 'My Farms')

@php
    $isVerified = auth()->user()->verified_by_cao_at !== null;

    // Farms that have coordinates → can be placed on the map
    $mappedFarms = $farms->filter(fn($f) =>
        $f->latitude  !== null && $f->longitude !== null &&
        is_numeric($f->latitude)  && is_numeric($f->longitude) &&
        (float) $f->latitude  !== 0.0 && (float) $f->longitude !== 0.0
    )->values();

    $unmappedCount = $farms->count() - $mappedFarms->count();

    // Pre-compute the JSON payload OUTSIDE @json to avoid blade-parsing issues
    $mappedFarmsJson = $mappedFarms->map(function ($f) {
        return [
            'id'       => $f->id,
            'name'     => $f->name,
            'barangay' => $f->barangay,
            'area'     => $f->land_area_ha,
            'soil'     => $f->soil_type,
            'lat'      => (float) $f->latitude,
            'lng'      => (float) $f->longitude,
            'seasons'  => $f->farm_records_count,
            'showUrl'  => route('farmer.farms.show', $f->id),
        ];
    })->values();
@endphp

@section('content')

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
    #farmerFarmsMap {
        height: 420px;
        width: 100%;
        border-radius: var(--radius-lg);
        z-index: 1;
    }
    .leaflet-control-zoom { z-index: 1050 !important; }

    .farm-card {
        transition: var(--transition-smooth);
    }
    .farm-card:hover {
        transform: translateY(-2px);
    }

    /* Farm marker popup */
    .farm-marker-popup .leaflet-popup-content-wrapper {
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        border: none;
        padding: 0;
    }
    .farm-marker-popup .leaflet-popup-content {
        margin: 0;
        padding: 0;
        width: auto !important;
        min-width: 220px;
    }
    .farm-marker-popup .leaflet-popup-tip {
        background: white;
        box-shadow: 0 4px 20px rgba(0,0,0,0.12);
    }
</style>

{{-- ═══════════ HEADER ═══════════ --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 style="font-weight: 800; color: var(--slate-900); margin: 0; letter-spacing: -0.3px;">
            <i class="bi bi-geo-alt-fill" style="color: var(--brand-green);"></i>
            My Farms
        </h5>
        <p style="font-size: 13px; color: var(--slate-500); margin: 4px 0 0;">
            All farms registered under your name.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        @if($incompleteCount > 0)
            <span class="badge-status" style="background: var(--brand-gold-light); color: #92400e; border-color: #fde68a;">
                <span class="dot" style="background: var(--brand-gold);"></span>
                {{ $incompleteCount }} need{{ $incompleteCount === 1 ? 's' : '' }} details
            </span>
        @endif

        @if($isVerified)
            <button type="button" class="btn btn-success" onclick="openFarmerFarmModal()">
                <i class="bi bi-plus-circle"></i> Add Farm
            </button>
        @else
            <button class="btn btn-success" disabled title="Available after CAO verification">
                <i class="bi bi-lock"></i> Add Farm
            </button>
        @endif
    </div>
</div>

{{-- ═══════════ EMPTY STATE ═══════════ --}}
@if($farms->isEmpty())
    <div class="card-custom text-center py-5">
        <i class="bi bi-geo-alt" style="font-size: 48px; color: var(--slate-300);"></i>
        <h6 class="mt-3 mb-1" style="color: var(--slate-700); font-weight: 700;">No farms yet</h6>
        <p style="font-size: 13px; color: var(--slate-500); margin-bottom: 20px;">
            When the CAO imports the RSBSA database, your farms will appear here. You can also add a farm manually.
        </p>
        @if($isVerified)
            <button type="button" class="btn btn-success" onclick="openFarmerFarmModal()">
                <i class="bi bi-plus-circle"></i> Add My First Farm
            </button>
        @else
            <button class="btn btn-success" disabled title="Available after CAO verification">
                <i class="bi bi-lock"></i> Add My First Farm
            </button>
        @endif
    </div>
@else

    {{-- ═══════════ MAP ═══════════ --}}
    <div class="card-custom mb-4" style="padding: 0; overflow: hidden;">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2"
             style="padding: 14px 20px; border-bottom: 1px solid var(--slate-200);">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-map-fill" style="color: var(--brand-green); font-size: 18px;"></i>
                <strong style="color: var(--slate-800); font-size: 14px;">Farm Locations</strong>
                <span class="badge-status" style="background: var(--brand-green-light); color: var(--brand-green-dark); border-color: #c7e6d2; font-size: 11px;">
                    <span class="dot" style="background: var(--brand-green);"></span>
                    {{ $mappedFarms->count() }} mapped
                </span>
                @if($unmappedCount > 0)
                    <span class="badge-status" style="background: var(--brand-gold-light); color: #92400e; border-color: #fde68a; font-size: 11px;">
                        <span class="dot" style="background: var(--brand-gold);"></span>
                        {{ $unmappedCount }} without location
                    </span>
                @endif
            </div>
            <button id="farmerToggleMap" class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-repeat"></i> Switch to Satellite
            </button>
        </div>

        @if($mappedFarms->isEmpty())
            <div class="text-center py-5" style="background: var(--slate-50);">
                <i class="bi bi-pin-map" style="font-size: 42px; color: var(--slate-300);"></i>
                <h6 class="mt-3 mb-1" style="color: var(--slate-700); font-weight: 700;">No farm locations yet</h6>
                <p style="font-size: 13px; color: var(--slate-500); margin: 0;">
                    @if($isVerified)
                        Edit a farm and click on the map to set its exact location.
                    @else
                        Once the CAO verifies your account, you'll be able to set farm locations.
                    @endif
                </p>
            </div>
        @else
            <div id="farmerFarmsMap"></div>
        @endif
    </div>

    {{-- ═══════════ FARM GRID ═══════════ --}}
    <div class="row g-3">
        @foreach($farms as $farm)
            @php
                $isIncomplete = !$farm->soil_type || !$farm->latitude;
                $accent = $isIncomplete ? 'var(--brand-gold)' : 'var(--brand-green)';
            @endphp
            <div class="col-md-6 col-lg-4">
                <div class="card-custom farm-card h-100 mb-0 d-flex flex-column"
                     style="border-top: 4px solid {{ $accent }};">

                    {{-- Header --}}
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div style="min-width: 0; flex: 1;">
                            <h6 style="font-weight: 700; font-size: 15px; color: var(--slate-900); margin: 0 0 6px; letter-spacing: -0.2px;">
                                {{ $farm->name }}
                            </h6>
                            <div style="font-size: 12px; color: var(--slate-500);">
                                <i class="bi bi-geo-alt-fill"></i> {{ $farm->barangay }}
                            </div>
                        </div>
                        @if($isIncomplete)
                            <span class="badge-status" style="background: var(--brand-gold-light); color: #92400e; border-color: #fde68a; font-size: 10px;">
                                <span class="dot" style="background: var(--brand-gold);"></span>
                                Needs details
                            </span>
                        @endif
                    </div>

                    {{-- Stats --}}
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div style="background: var(--slate-50); padding: 10px 12px; border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
                                <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.4px;">Area</div>
                                <div style="font-size: 16px; font-weight: 800; color: var(--brand-green); line-height: 1.1;">
                                    {{ number_format($farm->land_area_ha, 2) }}
                                    <small style="font-size: 10px; font-weight: 600;">ha</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div style="background: var(--slate-50); padding: 10px 12px; border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
                                <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.4px;">Seasons</div>
                                <div style="font-size: 16px; font-weight: 800; color: var(--brand-green); line-height: 1.1;">
                                    {{ $farm->farm_records_count }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Details --}}
                    <div style="font-size: 12px; color: var(--slate-600); margin-bottom: 14px;">
                        <div style="display: flex; justify-content: space-between; padding: 4px 0;">
                            <span><i class="bi bi-layers"></i> Soil type</span>
                            <strong>{{ $farm->soil_type ?? '—' }}</strong>
                        </div>
                        @if($farm->latitude && $farm->longitude)
                            <div style="display: flex; justify-content: space-between; padding: 4px 0;">
                                <span><i class="bi bi-pin-map"></i> Location</span>
                                <strong style="font-family: monospace; font-size: 11px;">
                                    {{ number_format($farm->latitude, 3) }}, {{ number_format($farm->longitude, 3) }}
                                </strong>
                            </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="mt-auto d-flex gap-2 pt-3" style="border-top: 1px solid var(--slate-200);">
                        <a href="{{ route('farmer.farms.show', $farm->id) }}"
                           class="btn btn-sm btn-outline-success flex-fill">
                            <i class="bi bi-eye"></i> View Farm
                        </a>
                        @if($farm->latitude && $farm->longitude)
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                    onclick="focusFarmOnMap({{ $farm->id }})" title="Show on Map">
                                <i class="bi bi-pin-map"></i>
                            </button>
                        @endif
                        @if($isVerified)
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                    onclick="editFarmerFarm({{ $farm->id }})" title="Edit Farm">
                                <i class="bi bi-pencil"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

@endif

{{-- ═══════════ MODAL ═══════════ --}}
@if($isVerified)
<div class="modal fade" id="farmerFarmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--brand-green); color: white;">
                <h5 class="modal-title">
                    <i class="bi bi-geo-alt-fill"></i>
                    <span id="farmerFarmModalTitle">Add Farm</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="overflow: hidden;">
                <div class="text-center py-4" id="farmerFarmModalLoading">
                    <div class="spinner-border text-success" role="status"></div>
                    <p class="mt-2">Loading form...</p>
                </div>
                <div id="farmerFarmModalContent" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    // ═══════════════════════════════════════════════════════════
    // FARMER FARMS MAP
    // ═══════════════════════════════════════════════════════════
    var farmerMap = null;
    var farmerMarkers = {};   // id → marker

    const SANTIAGO_BOUNDS = { minLat: 16.60, maxLat: 16.75, minLng: 121.48, maxLng: 121.63 };

    function clampToSantiago(lat, lng) {
        return {
            lat: Math.min(Math.max(lat, SANTIAGO_BOUNDS.minLat), SANTIAGO_BOUNDS.maxLat),
            lng: Math.min(Math.max(lng, SANTIAGO_BOUNDS.minLng), SANTIAGO_BOUNDS.maxLng)
        };
    }

    @if($mappedFarms->isNotEmpty())
    document.addEventListener('DOMContentLoaded', function () {
        var container = document.getElementById('farmerFarmsMap');
        if (!container || typeof L === 'undefined') return;

        var farms = @json($mappedFarmsJson);

        // Center: first farm, or default Santiago
        var firstLat = farms[0].lat;
        var firstLng = farms[0].lng;

        farmerMap = L.map(container, {
            center: [firstLat, firstLng],
            zoom: 13,
            maxBounds: [
                [SANTIAGO_BOUNDS.minLat - 0.05, SANTIAGO_BOUNDS.minLng - 0.05],
                [SANTIAGO_BOUNDS.maxLat + 0.05, SANTIAGO_BOUNDS.maxLng + 0.05]
            ],
            maxBoundsViscosity: 0.6
        });

        // Tile layers
        var streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        });
        var satImg = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: '&copy; Esri' });
        var satLabels = L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', { attribution: '&copy; Esri' });
        var satelliteLayer = L.layerGroup([satImg, satLabels]);
        streetLayer.addTo(farmerMap);

        // ─── Street / Satellite toggle ───
        var isSat = false;
        var toggleBtn = document.getElementById('farmerToggleMap');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                if (isSat) {
                    farmerMap.removeLayer(satelliteLayer);
                    streetLayer.addTo(farmerMap);
                    this.innerHTML = '<i class="bi bi-arrow-repeat"></i> Switch to Satellite';
                    this.classList.remove('btn-primary');
                    this.classList.add('btn-secondary');
                    isSat = false;
                } else {
                    farmerMap.removeLayer(streetLayer);
                    satelliteLayer.addTo(farmerMap);
                    this.innerHTML = '<i class="bi bi-arrow-repeat"></i> Switch to Street';
                    this.classList.remove('btn-secondary');
                    this.classList.add('btn-primary');
                    isSat = true;
                }
            });
        }

        // ─── Place markers ───
        farms.forEach(function (farm, idx) {
            var marker = L.circleMarker([farm.lat, farm.lng], {
                radius: 11,
                fillColor: '#165b33',
                color: '#ffffff',
                weight: 2.5,
                opacity: 1,
                fillOpacity: 0.9
            }).addTo(farmerMap);

            var popupHtml = `
                <div style="padding: 14px 16px;">
                    <h6 style="margin: 0 0 6px 0; color: #0d381f; font-weight: 800; font-size: 14px; letter-spacing: -0.2px;">
                        ${farm.name}
                    </h6>
                    <div style="font-size: 11px; color: #64748b; margin-bottom: 8px;">
                        <i class="bi bi-geo-alt-fill" style="color: #165b33;"></i> ${farm.barangay}, Santiago City
                    </div>
                    <table style="font-size: 12px; width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="color: #64748b; padding: 2px 0;">Area</td>
                            <td style="text-align: right; font-weight: 700; color: #0d381f;">
                                ${parseFloat(farm.area).toFixed(2)} ha
                            </td>
                        </tr>
                        <tr>
                            <td style="color: #64748b; padding: 2px 0;">Soil type</td>
                            <td style="text-align: right; font-weight: 600; color: #334155;">
                                ${farm.soil ?? '—'}
                            </td>
                        </tr>
                        <tr>
                            <td style="color: #64748b; padding: 2px 0;">Seasons</td>
                            <td style="text-align: right; font-weight: 700; color: #0d381f;">
                                ${farm.seasons}
                            </td>
                        </tr>
                    </table>
                    <a href="${farm.showUrl}"
                       style="display: block; margin-top: 10px; padding: 6px 12px; background: #165b33; color: white; text-align: center; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none;">
                        <i class="bi bi-eye"></i> View Farm
                    </a>
                </div>
            `;

            marker.bindPopup(popupHtml, {
                className: 'farm-marker-popup',
                closeButton: true,
                maxWidth: 260
            });

            farmerMarkers[farm.id] = marker;
        });

        // ─── Fit bounds to all markers ───
        if (farms.length > 1) {
            var group = L.featureGroup();
            farms.forEach(function (f) {
                group.addLayer(L.circleMarker([f.lat, f.lng]));
            });
            farmerMap.fitBounds(group.getBounds().pad(0.25));
        } else {
            farmerMap.setZoom(15);
        }

        // Multiple invalidateSize passes for layout settling
        setTimeout(function () { if (farmerMap) farmerMap.invalidateSize(true); }, 300);
        setTimeout(function () { if (farmerMap) farmerMap.invalidateSize(true); }, 700);
    });

    // Called by the pin-map button on each farm card
    function focusFarmOnMap(id) {
        if (!farmerMap || !farmerMarkers[id]) return;

        var marker = farmerMarkers[id];
        var latlng = marker.getLatLng();

        farmerMap.setView(latlng, 16, { animate: true });
        setTimeout(function () {
            marker.openPopup();
        }, 250);

        // Scroll the map into view
        var mapEl = document.getElementById('farmerFarmsMap');
        if (mapEl) mapEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    @else
    function focusFarmOnMap() { /* no mapped farms */ }
    @endif

    // ═══════════════════════════════════════════════════════════
    // EDIT-FARM MODAL MAP (existing behaviour)
    // ═══════════════════════════════════════════════════════════
    @if($isVerified)
    let fModalMap = null;
    let fModalMarker = null;

    const FSANTIAGO_BOUNDS = { minLat: 16.60, maxLat: 16.75, minLng: 121.48, maxLng: 121.63 };

    function fClampToSantiago(lat, lng) {
        return {
            lat: Math.min(Math.max(lat, FSANTIAGO_BOUNDS.minLat), FSANTIAGO_BOUNDS.maxLat),
            lng: Math.min(Math.max(lng, FSANTIAGO_BOUNDS.minLng), FSANTIAGO_BOUNDS.maxLng)
        };
    }

    function openFarmerFarmModal() {
        document.getElementById('farmerFarmModalTitle').textContent = 'Add Farm';
        document.getElementById('farmerFarmModalLoading').style.display = 'block';
        document.getElementById('farmerFarmModalContent').style.display = 'none';
        document.getElementById('farmerFarmModalContent').innerHTML = '';

        new bootstrap.Modal(document.getElementById('farmerFarmModal')).show();

        fetch('{{ route("farmer.farms.create") }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => {
                document.getElementById('farmerFarmModalLoading').style.display = 'none';
                document.getElementById('farmerFarmModalContent').style.display = 'block';
                document.getElementById('farmerFarmModalContent').innerHTML = html;
                const form = document.getElementById('farmerFarmForm');
                if (form) form.addEventListener('submit', handleFarmerFarmSubmit);
                setTimeout(initFarmerFarmModalMap, 300);
            })
            .catch(() => {
                document.getElementById('farmerFarmModalLoading').innerHTML = '<p class="text-danger">Failed to load form.</p>';
            });
    }

    function editFarmerFarm(id) {
        document.getElementById('farmerFarmModalTitle').textContent = 'Edit Farm';
        document.getElementById('farmerFarmModalLoading').style.display = 'block';
        document.getElementById('farmerFarmModalContent').style.display = 'none';
        document.getElementById('farmerFarmModalContent').innerHTML = '';

        new bootstrap.Modal(document.getElementById('farmerFarmModal')).show();

        fetch('/farmer/farms/' + id + '/edit', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => {
                document.getElementById('farmerFarmModalLoading').style.display = 'none';
                document.getElementById('farmerFarmModalContent').style.display = 'block';
                document.getElementById('farmerFarmModalContent').innerHTML = html;
                const form = document.getElementById('farmerFarmForm');
                if (form) form.addEventListener('submit', handleFarmerFarmSubmit);
                setTimeout(initFarmerFarmModalMap, 300);
            })
            .catch(() => {
                document.getElementById('farmerFarmModalLoading').innerHTML = '<p class="text-danger">Failed to load form.</p>';
            });
    }

    function initFarmerFarmModalMap() {
        const container = document.getElementById('farmerFarmModalMap');
        if (!container) { setTimeout(initFarmerFarmModalMap, 200); return; }

        if (fModalMap) { try { fModalMap.off(); fModalMap.remove(); } catch (e) {} fModalMap = null; fModalMarker = null; }

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

        fModalMap.on('click', function (e) {
            const cl = fClampToSantiago(e.latlng.lat, e.latlng.lng);
            fSetModalMarker(cl.lat, cl.lng);
        });

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

    document.addEventListener('click', function (e) {
        if (e.target.id === 'farmer_clearLocation' || e.target.closest('#farmer_clearLocation')) {
            const latInput = document.getElementById('farmer_latitude');
            const lngInput = document.getElementById('farmer_longitude');
            if (latInput) latInput.value = '';
            if (lngInput) lngInput.value = '';
            if (fModalMarker && fModalMap) { fModalMap.removeLayer(fModalMarker); fModalMarker = null; }
        }
    });

    document.addEventListener('hidden.bs.modal', function (e) {
        if (e.target.id === 'farmerFarmModal') {
            if (fModalMap) { try { fModalMap.off(); fModalMap.remove(); } catch (ex) {} fModalMap = null; fModalMarker = null; }
        }
    });

    function handleFarmerFarmSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
        submitBtn.disabled = true;

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
                bootstrap.Modal.getInstance(document.getElementById('farmerFarmModal')).hide();
                location.reload();
            } else {
                const div = document.getElementById('formErrors') || document.createElement('div');
                div.id = 'formErrors';
                div.className = 'alert alert-danger mb-3';
                let msg = data.error || 'An error occurred.';
                if (data.errors) msg = Object.values(data.errors).flat().join('<br>');
                div.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + msg;
                form.prepend(div);
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            alert('An error occurred. Please try again.');
        });
    }
    @endif
</script>
@endpush