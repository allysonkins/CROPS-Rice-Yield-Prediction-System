@extends('layouts.app')

@section('title', 'Farm Map')

@section('content')
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        #farmMap {
            height: 600px;
            border-radius: 16px;
            z-index: 1;
        }
        #farmModalMap {
            height: 300px;
            width: 100%;
            border-radius: 12px;
            border: 1px solid #ddd;
            background: #e8ecf1;
        }
        .legend {
            background: white;
            padding: 10px 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 4px 0;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
        }
        .leaflet-control-zoom {
            z-index: 1050 !important;
        }
        .map-controls {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .empty-farms-popup .leaflet-popup-content-wrapper {
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
            border: none;
            padding: 0;
        }
        .empty-farms-popup .leaflet-popup-content {
            margin: 0;
            padding: 0;
            width: auto !important;
        }
        .empty-farms-popup .leaflet-popup-tip {
            background: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        }

        /* Compact farm list scroll area */
        .farm-list-scroll {
            max-height: 340px;
            overflow-y: auto;
        }
        .farm-list-scroll thead th {
            position: sticky;
            top: 0;
            background: white;
            z-index: 2;
            box-shadow: inset 0 -1px 0 var(--gray-200);
        }

        /* Compact class pills */
        .map-class-btn {
            padding: 5px 12px;
            border-radius: 7px;
            border: none;
            font-weight: 600;
            font-size: 11px;
            background: transparent;
            color: var(--gray-600);
            white-space: nowrap;
            transition: all 0.15s;
        }
        .map-class-btn.active {
            background: white;
            color: var(--gray-900);
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        /* Compact pagination */
        #mapPaginationList button:not(:disabled):hover {
            background: var(--brand-green-light) !important;
            border-color: var(--brand-green) !important;
            color: var(--brand-green-dark) !important;
        }
    </style>

    <!-- Controls row -->
    <div class="map-controls">
        <span class="badge bg-success">{{ $farmData->count() }} Farms</span>
        @if(auth()->user()->role === 'admin')
            <button type="button" class="btn btn-sm btn-success" onclick="openFarmModal()">
                <i class="bi bi-plus-circle"></i> Add Farm
            </button>
        @endif
        <button id="toggleMap" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-repeat"></i> Switch to Satellite
        </button>
    </div>

    <!-- Map container -->
    <div class="card-custom" style="padding: 0; overflow: hidden;">
        <div id="farmMap"></div>
    </div>

    <!-- Farm list -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card-custom" style="padding: 16px 18px;">

                {{-- HEADER ROW --}}
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <h6 class="mb-0" style="font-size: 14px;">
                        <i class="bi bi-list-ul"></i> Farm List
                        <span class="badge bg-light text-muted ms-1" id="mapVisibleCount"
                              style="font-weight: 400; font-size: 10px;">
                            {{ $farmData->count() }} total
                        </span>
                    </h6>
                </div>

                {{-- COMPACT FILTER BAR (single row) --}}
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">

                    {{-- SEARCH --}}
                    <div style="flex: 1; min-width: 180px;">
                        <div class="input-group" style="border-radius: 10px; overflow: hidden; border: 1px solid var(--gray-200);">
                            <span class="input-group-text" style="background: var(--gray-50); border: none; color: var(--gray-400); padding: 0.35rem 0.7rem;">
                                <i class="bi bi-search" style="font-size: 12px;"></i>
                            </span>
                            <input type="text" id="mapSearchFarm" class="form-control"
                                   placeholder="Search farm or farmer..."
                                   style="border: none; background: var(--gray-50); font-size: 12px; padding: 0.4rem 0.7rem;">
                        </div>
                    </div>

                    {{-- BARANGAY --}}
                    <select id="mapFilterBarangay" class="form-select"
                            style="border-radius: 10px; border: 1px solid var(--gray-200); background: var(--gray-50); font-size: 12px; padding: 0.4rem 2rem 0.4rem 0.85rem; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.65rem center; background-size: 0.65rem; appearance: none; cursor: pointer; width: auto; min-width: 150px;">
                        <option value="all">All Barangays</option>
                        @foreach($farmData->pluck('barangay')->unique()->filter()->sort()->values() as $barangay)
                            <option value="{{ $barangay }}">{{ $barangay }}</option>
                        @endforeach
                    </select>

                    {{-- CLASS PILLS --}}
                    <div class="d-flex" style="background: var(--gray-100); border-radius: 10px; padding: 3px; gap: 2px;">
                        <button class="map-class-btn active" data-class="all">All</button>
                        <button class="map-class-btn" data-class="High">High</button>
                        <button class="map-class-btn" data-class="Medium">Medium</button>
                        <button class="map-class-btn" data-class="Low">Low</button>
                        <button class="map-class-btn" data-class="none">No Data</button>
                    </div>
                </div>

                {{-- TABLE --}}
                <div class="table-responsive farm-list-scroll">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Farm</th>
                                <th>Barangay</th>
                                <th>Farmer</th>
                                <th>Area (ha)</th>
                                <th>Predicted Yield</th>
                                <th>
                                    Yield Class
                                    <i class="bi bi-question-circle-fill"
                                       style="font-size: 11px; color: var(--gray-400); cursor: help;"
                                       data-bs-toggle="tooltip"
                                       data-bs-placement="top"
                                       data-bs-html="true"
                                       title="<div style='text-align:left; font-size:12px; line-height:1.5;'>
                                           <strong>Variety-relative class</strong><br>
                                           Compared against the variety's own average yield for the chosen seeding method.<br><br>
                                           • <strong>High</strong> — ≥ 112.5% of variety average<br>
                                           • <strong>Medium</strong> — 87.5% – 112.5%<br>
                                           • <strong>Low</strong> — below 87.5%<br><br>
                                           <em>Derived in the application — not a model output.</em>
                                       </div>"></i>
                                </th>
                                @if(auth()->user()->role === 'admin')
                                    <th style="width: 90px;">Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody id="mapFarmListBody">
                            @forelse($farmData as $farm)
                                @php
                                    $c = $farm['class'];
                                    if ($c === 'High')         $statusClass = 'high';
                                    elseif ($c === 'Medium')   $statusClass = 'medium';
                                    elseif ($c === 'Low')      $statusClass = 'low';
                                    else                       $statusClass = '';
                                    $classKey = $c ?? 'none';
                                    $searchText = strtolower(($farm['name'] ?? '') . ' ' . ($farm['farmer'] ?? ''));
                                @endphp
                                <tr class="map-farm-row"
                                    data-search="{{ $searchText }}"
                                    data-barangay="{{ $farm['barangay'] ?? '' }}"
                                    data-class="{{ $classKey }}">
                                    <td class="map-row-index">{{ $loop->iteration }}</td>
                                    <td><strong>{{ $farm['name'] }}</strong></td>
                                    <td>{{ $farm['barangay'] }}</td>
                                    <td>{{ $farm['farmer'] }}</td>
                                    <td>{{ $farm['land_area'] ?? 'N/A' }}</td>
                                    <td>
                                        @if($farm['yield'] !== null)
                                            <strong style="color: var(--green-dark);">{{ number_format($farm['yield'], 2) }}</strong>
                                            @if($farm['confidence'] !== null)
                                                <br><small class="text-muted" style="font-size: 10px;">
                                                    {{ number_format($farm['confidence'] * 100, 1) }}% conf.
                                                </small>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($c)
                                            <span class="badge-status {{ $statusClass }}"
                                                  style="cursor: help;"
                                                  data-bs-toggle="tooltip"
                                                  data-bs-placement="top"
                                                  data-bs-html="true"
                                                  title="<div style='text-align:left; font-size:12px; line-height:1.5;'>
                                                      <strong>Variety-relative class</strong><br>
                                                      Predicted yield: <strong>{{ number_format($farm['yield'], 2) }} t/ha</strong><br>
                                                      Variety average ({{ $farm['seeding_method'] ?? 'Transplanted' }}): <strong>{{ number_format($farm['variety_avg'], 2) }} t/ha</strong><br>
                                                      Ratio: <strong>{{ number_format($farm['ratio'] * 100, 1) }}%</strong> of average
                                                  </div>">
                                                <span class="dot"></span> {{ $c }}
                                            </span>
                                        @else
                                            <span class="badge-status" style="background: var(--gray-100); color: var(--gray-600); border-color: var(--gray-200);">
                                                <span class="dot" style="background: var(--gray-400);"></span> No Data
                                            </span>
                                        @endif
                                    </td>
                                    @if(auth()->user()->role === 'admin')
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" style="font-size: 11px; padding: 2px 10px;"
                                                    onclick="editFarmFromMap({{ $farm['id'] }})">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()->role === 'admin' ? 8 : 7 }}" class="text-center py-4 text-muted">
                                        @if(auth()->user()->role === 'farmer')
                                            <i class="bi bi-geo-alt" style="font-size: 36px; color: var(--gray-400);"></i>
                                            <p class="mt-3 mb-1">No farms assigned to you yet.</p>
                                            <p class="text-muted small">Please contact the City Agriculture Office.</p>
                                        @else
                                            <i class="bi bi-inbox" style="font-size: 36px; color: var(--gray-400);"></i>
                                            <p class="mt-3 mb-1">No farms registered yet.</p>
                                            <button type="button" class="btn btn-sm btn-success mt-2" onclick="openFarmModal()">
                                                <i class="bi bi-plus-circle"></i> Add Farm
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse

                            <tr id="mapNoResults" style="display: none;">
                                <td colspan="{{ auth()->user()->role === 'admin' ? 8 : 7 }}" class="text-center py-4 text-muted">
                                    <i class="bi bi-search" style="font-size: 28px;"></i>
                                    <p class="mt-2 mb-0">No farms match your filters.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- COMPACT PAGINATION --}}
                <div id="mapPaginationBar" class="d-none"
                     style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding-top: 10px; margin-top: 8px; border-top: 1px solid var(--gray-200);">

                    <div class="d-flex align-items-center gap-2" style="font-size: 11px; color: var(--gray-500);">
                        <span>Show</span>
                        <select id="mapPerPageSelect" class="form-select form-select-sm"
                                style="width: auto; border-radius: 7px; border: 1px solid var(--gray-200); font-size: 11px; padding: 2px 22px 2px 8px; appearance: none; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 6px center; background-size: 9px; cursor: pointer;">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span>entries</span>
                        <span id="mapPaginationInfo" style="margin-left: 4px;"></span>
                    </div>

                    <nav aria-label="Farm list pagination">
                        <ul id="mapPaginationList" class="pagination mb-0"
                            style="display: flex; align-items: center; gap: 3px; list-style: none; padding: 0; margin: 0;"></ul>
                    </nav>
                </div>

            </div>
        </div>
    </div>

    <!-- ===== FARM MODAL (create + edit) ===== -->
    @if(auth()->user()->role === 'admin')
        <div class="modal fade" id="farmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header" style="background: var(--green); color: white;">
                        <h5 class="modal-title"><i class="bi bi-geo-alt-fill"></i> <span id="modalTitle">Add Farm</span></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="overflow: hidden;">
                        <div class="text-center py-4" id="modalLoading">
                            <div class="spinner-border text-success" role="status"></div>
                            <p class="mt-2">Loading form...</p>
                        </div>
                        <div id="modalContent" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    // ─── Tooltips ──────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el, { container: 'body' });
        });
    });

    @if(auth()->user()->role === 'admin')
    // ============================================================
    // MODAL MAP VARIABLES
    // ============================================================
    let modalMap = null;
    let modalMarker = null;

    const SANTIAGO_BOUNDS = {
        minLat: 16.65, maxLat: 16.73,
        minLng: 121.52, maxLng: 121.58
    };

    function clampToSantiago(lat, lng) {
        return {
            lat: Math.min(Math.max(lat, SANTIAGO_BOUNDS.minLat), SANTIAGO_BOUNDS.maxLat),
            lng: Math.min(Math.max(lng, SANTIAGO_BOUNDS.minLng), SANTIAGO_BOUNDS.maxLng)
        };
    }

    function openFarmModal() {
        document.getElementById('modalTitle').textContent = 'Add Farm';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        const modalEl = document.getElementById('farmModal');
        new bootstrap.Modal(modalEl).show();

        fetch('{{ route("admin.farms.create") }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.text())
        .then(html => {
            document.getElementById('modalLoading').style.display = 'none';
            document.getElementById('modalContent').style.display = 'block';
            document.getElementById('modalContent').innerHTML = html;

            const form = document.getElementById('farmForm');
            if (form) form.addEventListener('submit', handleSubmit);

            setTimeout(initModalMap, 300);
        });
    }

    function editFarmFromMap(id) {
        document.getElementById('modalTitle').textContent = 'Edit Farm';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        const modalEl = document.getElementById('farmModal');
        new bootstrap.Modal(modalEl).show();

        fetch('/admin/farms/' + id + '/edit', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.text())
        .then(html => {
            document.getElementById('modalLoading').style.display = 'none';
            document.getElementById('modalContent').style.display = 'block';
            document.getElementById('modalContent').innerHTML = html;

            const form = document.getElementById('farmForm');
            if (form) form.addEventListener('submit', handleSubmit);

            setTimeout(initModalMap, 300);
        });
    }

    function initModalMap() {
        const container = document.getElementById('farmModalMap');
        if (!container) {
            setTimeout(initModalMap, 200);
            return;
        }

        if (modalMap) {
            try { modalMap.off(); modalMap.remove(); } catch(e) {}
            modalMap = null;
            modalMarker = null;
        }

        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        let lat = latInput && latInput.value ? parseFloat(latInput.value) : 16.6889;
        let lng = lngInput && lngInput.value ? parseFloat(lngInput.value) : 121.5484;
        const c = clampToSantiago(lat, lng);
        lat = c.lat;
        lng = c.lng;

        modalMap = L.map(container, {
            center: [lat, lng],
            zoom: 14,
            maxBounds: [
                [SANTIAGO_BOUNDS.minLat - 0.02, SANTIAGO_BOUNDS.minLng - 0.02],
                [SANTIAGO_BOUNDS.maxLat + 0.02, SANTIAGO_BOUNDS.maxLng + 0.02]
            ],
            maxBoundsViscosity: 0.8
        });

        const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        });
        const satImg = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: '&copy; Esri' });
        const satLabels = L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', { attribution: '&copy; Esri' });
        const satelliteLayer = L.layerGroup([satImg, satLabels]);
        streetLayer.addTo(modalMap);

        const ToggleControl = L.Control.extend({
            options: { position: 'topright' },
            onAdd: function (map) {
                const btn = L.DomUtil.create('button', 'btn btn-sm btn-secondary shadow-sm');
                btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Satellite';
                btn.style.margin = '10px';
                btn.style.pointerEvents = 'auto';
                L.DomEvent.disableClickPropagation(btn);
                let isSat = false;
                L.DomEvent.on(btn, 'click', function(e) {
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
        modalMap.addControl(new ToggleControl());

        if (latInput && latInput.value && lngInput && lngInput.value) {
            modalMarker = L.marker([lat, lng], { draggable: true }).addTo(modalMap);
            modalMarker.on('dragend', function() {
                const pos = modalMarker.getLatLng();
                const cl = clampToSantiago(pos.lat, pos.lng);
                modalMarker.setLatLng(cl);
                latInput.value = cl.lat.toFixed(8);
                lngInput.value = cl.lng.toFixed(8);
            });
        }

        modalMap.on('click', function(e) {
            const cl = clampToSantiago(e.latlng.lat, e.latlng.lng);
            setModalMarker(cl.lat, cl.lng);
        });

        setTimeout(() => { if (modalMap) modalMap.invalidateSize(true); }, 300);
        setTimeout(() => { if (modalMap) modalMap.invalidateSize(true); }, 600);
    }

    function setModalMarker(lat, lng, pan = true) {
        const cl = clampToSantiago(lat, lng);
        lat = cl.lat;
        lng = cl.lng;

        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        if (!latInput || !lngInput) return;

        latInput.value = lat.toFixed(8);
        lngInput.value = lng.toFixed(8);

        if (modalMarker) {
            modalMarker.setLatLng([lat, lng]);
        } else if (modalMap) {
            modalMarker = L.marker([lat, lng], { draggable: true }).addTo(modalMap);
            modalMarker.on('dragend', function() {
                const pos = modalMarker.getLatLng();
                const cl = clampToSantiago(pos.lat, pos.lng);
                modalMarker.setLatLng(cl);
                latInput.value = cl.lat.toFixed(8);
                lngInput.value = cl.lng.toFixed(8);
            });
        }
        if (modalMap && pan) modalMap.setView([lat, lng], modalMap.getZoom());
    }

    document.addEventListener('click', function(e) {
        if (e.target.id === 'clearLocation' || e.target.closest('#clearLocation')) {
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            if (latInput) latInput.value = '';
            if (lngInput) lngInput.value = '';
            if (modalMarker && modalMap) {
                modalMap.removeLayer(modalMarker);
                modalMarker = null;
            }
        }
    });

    document.addEventListener('hidden.bs.modal', function(e) {
        if (e.target.id === 'farmModal') {
            if (modalMap) {
                try { modalMap.off(); modalMap.remove(); } catch(ex) {}
                modalMap = null;
                modalMarker = null;
            }
        }
    });

    function handleSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
        submitBtn.disabled = true;

        const existingErrors = form.querySelector('#formErrors');
        if (existingErrors) existingErrors.innerHTML = '';

        fetch(form.action, {
            method: form.method || 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('farmModal')).hide();
                location.reload();
            } else {
                const errorDiv = document.getElementById('formErrors') || document.createElement('div');
                errorDiv.id = 'formErrors';
                errorDiv.className = 'alert alert-danger mb-3';
                let msg = data.error || 'An error occurred.';
                if (data.errors) msg = Object.values(data.errors).flat().join('<br>');
                errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + msg;
                form.prepend(errorDiv);
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

    // ============================================================
    // MAIN PAGE MAP
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        var map = L.map('farmMap').setView([16.6889, 121.5484], 13);

        var streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        });
        var satelliteImg = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: '&copy; Esri' });
        var satelliteLabels = L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', { attribution: '&copy; Esri' });
        var satelliteLayer = L.layerGroup([satelliteImg, satelliteLabels]);
        streetLayer.addTo(map);

        var isSatellite = false;
        document.getElementById('toggleMap').addEventListener('click', function() {
            if (isSatellite) {
                map.removeLayer(satelliteLayer);
                streetLayer.addTo(map);
                this.innerHTML = '<i class="bi bi-arrow-repeat"></i> Switch to Satellite';
                this.classList.remove('btn-primary');
                this.classList.add('btn-secondary');
                isSatellite = false;
            } else {
                map.removeLayer(streetLayer);
                satelliteLayer.addTo(map);
                this.innerHTML = '<i class="bi bi-arrow-repeat"></i> Switch to Street';
                this.classList.remove('btn-secondary');
                this.classList.add('btn-primary');
                isSatellite = true;
            }
        });

        var farms = @json($farmData);
        var hasCoords = farms.filter(function(f) {
            return f.lat !== null && f.lng !== null &&
                   !isNaN(f.lat) && !isNaN(f.lng) &&
                   f.lat !== 0 && f.lng !== 0;
        });

        function getColor(farm) {
            switch (farm.class) {
                case 'High':   return '#27ae60';
                case 'Medium': return '#f39c12';
                case 'Low':    return '#e74c3c';
                default:       return '#6b7280';
            }
        }

        @php $isAdmin = auth()->user()->role === 'admin'; @endphp

        hasCoords.forEach(function(farm) {
            var color = getColor(farm);

            var classBadge = farm.class
                ? `<div style="margin-top: 6px;">
                       <span style="background: ${color}; color: white; padding: 2px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;">
                           ${farm.class} Yield
                       </span>
                   </div>`
                : `<div style="margin-top: 6px;">
                       <span style="background: #6b7280; color: white; padding: 2px 12px; border-radius: 20px; font-size: 11px;">
                           No Prediction
                       </span>
                   </div>`;

            var intervalLine = (farm.yield_lower !== null && farm.yield_upper !== null)
                ? `<p style="margin: 2px 0; font-size: 11px; color: #6b7280;">80% interval: ${farm.yield_lower} – ${farm.yield_upper} t/ha</p>`
                : '';

            var confidenceLine = farm.confidence !== null
                ? `<p style="margin: 2px 0; font-size: 11px; color: #6b7280;">Confidence: ${Math.round(farm.confidence * 100)}%</p>`
                : '';

            var popup = `
                <div style="min-width: 220px;">
                    <h6 style="margin: 0 0 4px 0; color: #0f4c2b;"><strong>${farm.name}</strong></h6>
                    <hr style="margin: 4px 0;">
                    <p style="margin: 2px 0;"><strong>Barangay:</strong> ${farm.barangay}</p>
                    <p style="margin: 2px 0;"><strong>Farmer:</strong> ${farm.farmer}</p>
                    <p style="margin: 2px 0;"><strong>Land Area:</strong> ${farm.land_area ?? 'N/A'} ha</p>
                    <p style="margin: 2px 0;"><strong>Predicted Yield:</strong> ${farm.yield ? farm.yield + ' t/ha' : 'No data'}</p>
                    ${intervalLine}
                    ${confidenceLine}
                    ${classBadge}
                    @if($isAdmin)
                        <button class="btn btn-sm btn-outline-primary mt-2" style="font-size: 11px;" onclick="editFarmFromMap(${farm.id})">
                            Edit
                        </button>
                    @endif
                </div>
            `;

            L.circleMarker([farm.lat, farm.lng], {
                radius: 10, fillColor: color, color: '#fff',
                weight: 2, opacity: 1, fillOpacity: 0.8
            }).addTo(map).bindPopup(popup);
        });

        if (hasCoords.length > 1) {
            var group = L.featureGroup();
            hasCoords.forEach(function(f) {
                group.addLayer(L.circleMarker([f.lat, f.lng]));
            });
            map.fitBounds(group.getBounds().pad(0.2));
        } else if (hasCoords.length === 1) {
            map.setZoom(15);
        }

        var legend = L.control({ position: 'bottomright' });
        legend.onAdd = function() {
            var div = L.DomUtil.create('div', 'legend');
            div.innerHTML = `
                <div style="background: white; padding: 12px 16px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                    <strong>Yield Class</strong>
                    <div style="font-size: 10px; color: #6b7280; margin-top: 2px;">Relative to variety average</div>
                    <div class="legend-item"><span class="legend-color" style="background: #27ae60;"></span> High</div>
                    <div class="legend-item"><span class="legend-color" style="background: #f39c12;"></span> Medium</div>
                    <div class="legend-item"><span class="legend-color" style="background: #e74c3c;"></span> Low</div>
                    <div class="legend-item"><span class="legend-color" style="background: #6b7280;"></span> No Prediction</div>
                </div>
            `;
            return div;
        };
        legend.addTo(map);

        if (hasCoords.length === 0) {
            var emptyMessage = @if(auth()->user()->role === 'farmer')
                'No farms assigned to you yet. Please contact the City Agriculture Office.'
            @else
                'No farms registered yet. Click Add Farm to get started.'
            @endif;

            L.popup({
                closeButton: false,
                closeOnClick: false,
                autoClose: false,
                className: 'empty-farms-popup'
            })
                .setLatLng([16.6889, 121.5484])
                .setContent(`
                    <div style="text-align: center; padding: 20px; min-width: 260px;">
                        <i class="bi bi-geo-alt" style="font-size: 32px; color: #0f4c2b;"></i>
                        <p style="color: #4b5563; font-size: 14px; margin: 10px 0 0 0; font-weight: 500;">
                            ${emptyMessage}
                        </p>
                        @if(auth()->user()->role !== 'farmer')
                            <button type="button"
                                    onclick="openFarmModal()"
                                    class="btn btn-sm btn-success mt-3">
                                <i class="bi bi-plus-circle"></i> Add First Farm
                            </button>
                        @endif
                    </div>
                `)
                .openOn(map);
        }

        setTimeout(function() { map.invalidateSize(); }, 400);

        // ============================================================
        // FARM LIST — FILTERING + PAGINATION
        // ============================================================
        initFarmList();
    });

    function initFarmList() {
        var searchInput     = document.getElementById('mapSearchFarm');
        var barangaySelect  = document.getElementById('mapFilterBarangay');
        var classBtns       = document.querySelectorAll('.map-class-btn');
        var allRows         = Array.from(document.querySelectorAll('#mapFarmListBody .map-farm-row'));
        var noResults       = document.getElementById('mapNoResults');
        var visibleCount    = document.getElementById('mapVisibleCount');
        var paginationBar   = document.getElementById('mapPaginationBar');
        var paginationList  = document.getElementById('mapPaginationList');
        var paginationInfo  = document.getElementById('mapPaginationInfo');
        var perPageSelect   = document.getElementById('mapPerPageSelect');

        if (!searchInput || allRows.length === 0) return;

        var currentPage = 1;
        var perPage = parseInt(perPageSelect?.value || '25', 10);

        function getMatchingRows() {
            var search = searchInput.value.toLowerCase().trim();
            var barangay = barangaySelect.value;
            var activeClass = document.querySelector('.map-class-btn.active');
            var classFilter = activeClass ? activeClass.dataset.class : 'all';

            return allRows.filter(function(row) {
                var sd = row.dataset.search || '';
                var rb = row.dataset.barangay || '';
                var rc = row.dataset.class || 'none';

                var matchesSearch   = sd.includes(search);
                var matchesBarangay = (barangay === 'all' || rb === barangay);
                var matchesClass    = (classFilter === 'all' || rc === classFilter);

                return matchesSearch && matchesBarangay && matchesClass;
            });
        }

        function renderPagination() {
            var matching = getMatchingRows();
            var total = matching.length;
            var totalPages = Math.max(1, Math.ceil(total / perPage));

            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            // Hide all rows, then show only the page slice
            allRows.forEach(function(r) { r.style.display = 'none'; });

            var start = (currentPage - 1) * perPage;
            var end = start + perPage;
            matching.slice(start, end).forEach(function(row, i) {
                row.style.display = '';
                var c = row.querySelector('.map-row-index');
                if (c) c.textContent = start + i + 1;
            });

            if (noResults) noResults.style.display = (total === 0 && allRows.length > 0) ? '' : 'none';
            if (visibleCount) visibleCount.textContent = total + ' visible';

            if (paginationInfo) {
                paginationInfo.textContent = total === 0
                    ? '— no entries'
                    : '— ' + (start + 1) + ' to ' + Math.min(end, total) + ' of ' + total;
            }

            if (paginationBar) {
                paginationBar.classList.toggle('d-none', totalPages <= 1);
            }

            if (!paginationList) return;
            paginationList.innerHTML = '';

            var btnStyle = 'display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 8px;border-radius:7px;border:1px solid var(--gray-200);background:#fff;color:var(--gray-700);font-size:12px;font-weight:600;cursor:pointer;';
            var activeStyle = 'display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 8px;border-radius:7px;border:1px solid var(--brand-green);background:var(--brand-green);color:#fff;font-size:12px;font-weight:700;';
            var disabledStyle = 'display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 8px;border-radius:7px;border:1px solid var(--gray-200);background:var(--gray-50);color:var(--gray-300);font-size:12px;font-weight:600;cursor:not-allowed;';

            function addBtn(label, page, opts) {
                opts = opts || {};
                var li = document.createElement('li');
                var a = document.createElement('button');
                a.type = 'button';
                a.innerHTML = label;
                a.setAttribute('style', opts.active ? activeStyle : (opts.disabled ? disabledStyle : btnStyle));
                if (!opts.active && !opts.disabled && page !== null) {
                    a.addEventListener('click', function() {
                        currentPage = page;
                        renderPagination();
                    });
                } else {
                    a.disabled = true;
                }
                li.appendChild(a);
                paginationList.appendChild(li);
            }

            addBtn('<i class="bi bi-chevron-left"></i>', currentPage - 1, { disabled: currentPage === 1 });

            var pages = [];
            if (totalPages <= 7) {
                for (var i = 1; i <= totalPages; i++) pages.push(i);
            } else {
                pages.push(1);
                if (currentPage > 3) pages.push('...');
                var s = Math.max(2, currentPage - 1);
                var e = Math.min(totalPages - 1, currentPage + 1);
                for (var j = s; j <= e; j++) pages.push(j);
                if (currentPage < totalPages - 2) pages.push('...');
                pages.push(totalPages);
            }

            pages.forEach(function(p) {
                if (p === '...') {
                    var li = document.createElement('li');
                    var span = document.createElement('span');
                    span.textContent = '…';
                    span.setAttribute('style', 'display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;color:var(--gray-400);font-weight:700;font-size:12px;');
                    li.appendChild(span);
                    paginationList.appendChild(li);
                } else {
                    addBtn(String(p), p, { active: p === currentPage });
                }
            });

            addBtn('<i class="bi bi-chevron-right"></i>', currentPage + 1, { disabled: currentPage === totalPages });
        }

        function resetAndRender() {
            currentPage = 1;
            renderPagination();
        }

        searchInput.addEventListener('input', resetAndRender);
        barangaySelect.addEventListener('change', resetAndRender);

        classBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                classBtns.forEach(function(b) { b.classList.remove('active'); });
                this.classList.add('active');
                resetAndRender();
            });
        });

        perPageSelect?.addEventListener('change', function() {
            perPage = parseInt(this.value, 10) || 25;
            resetAndRender();
        });

        renderPagination();
    }
</script>
@endpush