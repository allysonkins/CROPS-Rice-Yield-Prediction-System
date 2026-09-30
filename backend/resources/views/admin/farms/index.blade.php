@extends('layouts.app')

@section('title', 'Manage Farms')

@section('content')
    <!-- Leaflet CSS (needed for the map inside the modal) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        #farmModalMap {
            height: 300px;
            width: 100%;
            border-radius: 12px;
            border: 1px solid #ddd;
            background: #e8ecf1;
            margin-top: 8px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        .leaflet-control-zoom {
            z-index: 1050 !important;
        }

        .farm-list-scroll {
            max-height: 560px;
            overflow-y: auto;
        }
        .farm-list-scroll thead th {
            position: sticky;
            top: 0;
            background: white;
            z-index: 2;
            box-shadow: inset 0 -1px 0 var(--gray-200);
        }

        .farms-filter-btn {
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
        .farms-filter-btn.active {
            background: white;
            color: var(--gray-900);
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        #farmsPaginationList button:not(:disabled):hover {
            background: var(--brand-green-light) !important;
            border-color: var(--brand-green) !important;
            color: var(--brand-green-dark) !important;
        }
    </style>

    <div class="page-header">
        <div>
            <h4 class="page-header-title">
                
            </h4>
            <p class="page-header-desc">
               
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @if(auth()->user()->role === 'admin')
                <button type="button" class="btn btn-success" onclick="openFarmModal()">
                    <i class="bi bi-plus-circle"></i> Add Farm
                </button>
            @endif
            <span class="badge-count">{{ $farms->count() }} Farms</span>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-2 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-info">
                    <div class="label">Total Farms</div>
                    <div class="value">{{ $farms->count() }}</div>
                </div>
                <div class="stat-icon"><i class="bi bi-geo-alt-fill"></i></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-info">
                    <div class="label">Barangays</div>
                    <div class="value">{{ $farms->pluck('barangay')->unique()->count() }}</div>
                </div>
                <div class="stat-icon"><i class="bi bi-geo"></i></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-info">
                    <div class="label">Farmers Assigned</div>
                    <div class="value">{{ $farms->whereNotNull('user_id')->count() }}</div>
                </div>
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-info">
                    <div class="label">Total Area (ha)</div>
                    <div class="value">{{ number_format($farms->sum('land_area_ha'), 1) }}</div>
                </div>
                <div class="stat-icon"><i class="bi bi-rulers"></i></div>
            </div>
        </div>
    </div>

    <!-- Farm List Card -->
    <div class="card-custom">

        <div class="card-title d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>
                <i class="bi bi-table"></i> Farm List
                <span class="badge bg-light text-muted ms-1" id="farmsVisibleCount"
                      style="font-weight: 400; font-size: 10px;">
                    {{ $farms->count() }} total
                </span>
            </span>
        </div>

        {{-- FILTER BAR (single row) --}}
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">

            {{-- SEARCH --}}
            <div style="flex: 1; min-width: 200px;">
                <div class="input-group" style="border-radius: 10px; overflow: hidden; border: 1px solid var(--gray-200);">
                    <span class="input-group-text" style="background: var(--gray-50); border: none; color: var(--gray-400); padding: 0.35rem 0.7rem;">
                        <i class="bi bi-search" style="font-size: 12px;"></i>
                    </span>
                    <input type="text" id="farmsSearch" class="form-control"
                           placeholder="Search farm, farmer, or barangay..."
                           style="border: none; background: var(--gray-50); font-size: 12px; padding: 0.4rem 0.7rem;">
                </div>
            </div>

            {{-- BARANGAY --}}
            <select id="farmsFilterBarangay" class="form-select"
                    style="border-radius: 10px; border: 1px solid var(--gray-200); background: var(--gray-50); font-size: 12px; padding: 0.4rem 2rem 0.4rem 0.85rem; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.65rem center; background-size: 0.65rem; appearance: none; cursor: pointer; width: auto; min-width: 160px;">
                <option value="all">All Barangays</option>
                @foreach($farms->pluck('barangay')->unique()->filter()->sort()->values() as $barangay)
                    <option value="{{ $barangay }}">{{ $barangay }}</option>
                @endforeach
            </select>

            {{-- ASSIGNMENT PILLS --}}
            <div class="d-flex" style="background: var(--gray-100); border-radius: 10px; padding: 3px; gap: 2px;">
                <button class="farms-filter-btn active" data-assign="all">All</button>
                <button class="farms-filter-btn" data-assign="assigned">Assigned</button>
                <button class="farms-filter-btn" data-assign="unassigned">Unassigned</button>
            </div>

            {{-- GEO PILLS --}}
            <div class="d-flex" style="background: var(--gray-100); border-radius: 10px; padding: 3px; gap: 2px;">
                <button class="farms-filter-btn active" data-geo="all">Any</button>
                <button class="farms-filter-btn" data-geo="with">Located</button>
                <button class="farms-filter-btn" data-geo="without">No Pin</button>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive farm-list-scroll">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Farm Name</th>
                        <th>Barangay</th>
                        <th>Farmer</th>
                        <th>Area (ha)</th>
                        <th>Soil Type</th>
                        <th>Coordinates</th>
                        @if(auth()->user()->role === 'admin')
                            <th style="width: 90px;">Actions</th>
                        @else
                            <th>Access</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="farmsListBody">
                    @forelse($farms as $farm)
                        @php
                            $hasCoords = $farm->latitude && $farm->longitude;
                            $isAssigned = $farm->user_id !== null;
                            $searchText = strtolower(
                                ($farm->name ?? '') . ' ' .
                                ($farm->user->name ?? '') . ' ' .
                                ($farm->barangay ?? '')
                            );
                        @endphp
                        <tr class="farms-row"
                            data-search="{{ $searchText }}"
                            data-barangay="{{ $farm->barangay ?? '' }}"
                            data-assign="{{ $isAssigned ? 'assigned' : 'unassigned' }}"
                            data-geo="{{ $hasCoords ? 'with' : 'without' }}">
                            <td class="farms-row-index">{{ $loop->iteration }}</td>
                            <td><strong>{{ $farm->name }}</strong></td>
                            <td>{{ $farm->barangay }}</td>
                            <td>{{ $farm->user->name ?? 'Unassigned' }}</td>
                            <td>{{ number_format($farm->land_area_ha, 2) }}</td>
                            <td>{{ $farm->soil_type }}</td>
                            <td>
                                @if($hasCoords)
                                    <span class="badge bg-light text-muted" style="font-size: 10px;">
                                        {{ number_format($farm->latitude, 6) }}, {{ number_format($farm->longitude, 6) }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if(auth()->user()->role === 'admin')
                                    <div class="d-flex align-items-center gap-1">
                                        <button type="button" class="btn-action btn-action-edit" onclick="editFarm({{ $farm->id }})" title="Edit Farm">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('admin.farms.destroy', $farm->id) }}" method="POST" class="d-inline m-0" onsubmit="return confirm('Delete this farm?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-action btn-action-danger" title="Delete Farm">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-muted small">View Only</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox" style="font-size: 28px;"></i>
                                <p class="mt-2 mb-0">No farms yet.</p>
                                @if(auth()->user()->role === 'admin')
                                    <button type="button" class="btn btn-sm btn-success mt-2" onclick="openFarmModal()">
                                        <i class="bi bi-plus-circle"></i> Add Farm
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforelse

                    <tr id="farmsNoResults" style="display: none;">
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-search" style="font-size: 28px;"></i>
                            <p class="mt-2 mb-0">No farms match your filters.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        <div id="farmsPaginationBar" class="d-none"
             style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding-top: 12px; margin-top: 8px; border-top: 1px solid var(--gray-200);">

            <div class="d-flex align-items-center gap-2" style="font-size: 11px; color: var(--gray-500);">
                <span>Show</span>
                <select id="farmsPerPage" class="form-select form-select-sm"
                        style="width: auto; border-radius: 7px; border: 1px solid var(--gray-200); font-size: 11px; padding: 2px 22px 2px 8px; appearance: none; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 6px center; background-size: 9px; cursor: pointer;">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>entries</span>
                <span id="farmsPaginationInfo" style="margin-left: 4px;"></span>
            </div>

            <nav aria-label="Farm pagination">
                <ul id="farmsPaginationList" class="pagination mb-0"
                    style="display: flex; align-items: center; gap: 3px; list-style: none; padding: 0; margin: 0;"></ul>
            </nav>
        </div>

    </div>

    <!-- Modal shell -->
    @if(auth()->user()->role === 'admin')
        @include('admin.farms.partials.modal')
    @endif
@endsection

@push('scripts')
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    @if(auth()->user()->role === 'admin')
    // ============================================================
    // MODAL MAP STATE
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
        })
        .catch(() => {
            document.getElementById('modalLoading').innerHTML =
                '<p class="text-danger">Failed to load form.</p>';
        });
    }

    function editFarm(id) {
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
        })
        .catch(() => {
            document.getElementById('modalLoading').innerHTML =
                '<p class="text-danger">Failed to load form.</p>';
        });
    }

    function initModalMap() {
        const container = document.getElementById('farmModalMap');
        if (!container) {
            setTimeout(initModalMap, 200);
            return;
        }

        if (modalMap) {
            try { modalMap.off(); modalMap.remove(); } catch (e) {}
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
        const satImg = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: '&copy; Esri'
        });
        const satLabels = L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
            attribution: '&copy; Esri'
        });
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
        modalMap.addControl(new ToggleControl());

        if (latInput && latInput.value && lngInput && lngInput.value) {
            modalMarker = L.marker([lat, lng], { draggable: true }).addTo(modalMap);
            modalMarker.on('dragend', function () {
                const pos = modalMarker.getLatLng();
                const cl = clampToSantiago(pos.lat, pos.lng);
                modalMarker.setLatLng(cl);
                latInput.value = cl.lat.toFixed(8);
                lngInput.value = cl.lng.toFixed(8);
            });
        }

        modalMap.on('click', function (e) {
            const cl = clampToSantiago(e.latlng.lat, e.latlng.lng);
            setModalMarker(cl.lat, cl.lng);
        });

        if (latInput && lngInput) {
            const sync = () => {
                const tLat = parseFloat(latInput.value);
                const tLng = parseFloat(lngInput.value);
                if (!isNaN(tLat) && !isNaN(tLng)) {
                    const cl = clampToSantiago(tLat, tLng);
                    setModalMarker(cl.lat, cl.lng, false);
                }
            };
            latInput.addEventListener('input', sync);
            lngInput.addEventListener('input', sync);
        }

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
            modalMarker.on('dragend', function () {
                const pos = modalMarker.getLatLng();
                const cl2 = clampToSantiago(pos.lat, pos.lng);
                modalMarker.setLatLng(cl2);
                latInput.value = cl2.lat.toFixed(8);
                lngInput.value = cl2.lng.toFixed(8);
            });
        }
        if (modalMap && pan) modalMap.setView([lat, lng], modalMap.getZoom());
    }

    document.addEventListener('click', function (e) {
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

    document.addEventListener('hidden.bs.modal', function (e) {
        if (e.target.id === 'farmModal') {
            if (modalMap) {
                try { modalMap.off(); modalMap.remove(); } catch (ex) {}
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
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Saving...';
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
    // FARM LIST — FILTERING + PAGINATION
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput    = document.getElementById('farmsSearch');
        const barangaySelect = document.getElementById('farmsFilterBarangay');
        const assignBtns     = document.querySelectorAll('.farms-filter-btn[data-assign]');
        const geoBtns        = document.querySelectorAll('.farms-filter-btn[data-geo]');
        const allRows        = Array.from(document.querySelectorAll('#farmsListBody .farms-row'));
        const noResults      = document.getElementById('farmsNoResults');
        const visibleCount   = document.getElementById('farmsVisibleCount');
        const paginationBar  = document.getElementById('farmsPaginationBar');
        const paginationList = document.getElementById('farmsPaginationList');
        const paginationInfo = document.getElementById('farmsPaginationInfo');
        const perPageSelect  = document.getElementById('farmsPerPage');

        if (!searchInput || allRows.length === 0) return;

        let currentPage = 1;
        let perPage = parseInt(perPageSelect?.value || '25', 10);

        function getMatchingRows() {
            const search     = searchInput.value.toLowerCase().trim();
            const barangay   = barangaySelect.value;
            const activeA    = document.querySelector('.farms-filter-btn[data-assign].active');
            const assignFilter = activeA ? activeA.dataset.assign : 'all';
            const activeG    = document.querySelector('.farms-filter-btn[data-geo].active');
            const geoFilter  = activeG ? activeG.dataset.geo : 'all';

            return allRows.filter(row => {
                const sd = row.dataset.search || '';
                const rb = row.dataset.barangay || '';
                const ra = row.dataset.assign || '';
                const rg = row.dataset.geo || '';

                const matchesSearch   = sd.includes(search);
                const matchesBarangay = (barangay === 'all' || rb === barangay);
                const matchesAssign   = (assignFilter === 'all' || ra === assignFilter);
                const matchesGeo      = (geoFilter === 'all' || rg === geoFilter);

                return matchesSearch && matchesBarangay && matchesAssign && matchesGeo;
            });
        }

        function renderPagination() {
            const matching = getMatchingRows();
            const total = matching.length;
            const totalPages = Math.max(1, Math.ceil(total / perPage));

            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            allRows.forEach(r => r.style.display = 'none');

            const start = (currentPage - 1) * perPage;
            const end = start + perPage;
            matching.slice(start, end).forEach((row, i) => {
                row.style.display = '';
                const c = row.querySelector('.farms-row-index');
                if (c) c.textContent = start + i + 1;
            });

            if (noResults) noResults.style.display = (total === 0 && allRows.length > 0) ? '' : 'none';
            if (visibleCount) visibleCount.textContent = total + ' visible';

            if (paginationInfo) {
                paginationInfo.textContent = total === 0
                    ? '— no entries'
                    : '— ' + (start + 1) + ' to ' + Math.min(end, total) + ' of ' + total;
            }

            if (paginationBar) paginationBar.classList.toggle('d-none', totalPages <= 1);
            if (!paginationList) return;

            paginationList.innerHTML = '';
            const btnStyle = 'display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 8px;border-radius:7px;border:1px solid var(--gray-200);background:#fff;color:var(--gray-700);font-size:12px;font-weight:600;cursor:pointer;';
            const activeStyle = 'display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 8px;border-radius:7px;border:1px solid var(--brand-green);background:var(--brand-green);color:#fff;font-size:12px;font-weight:700;';
            const disabledStyle = 'display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 8px;border-radius:7px;border:1px solid var(--gray-200);background:var(--gray-50);color:var(--gray-300);font-size:12px;font-weight:600;cursor:not-allowed;';

            const addBtn = (label, page, opts = {}) => {
                const li = document.createElement('li');
                const a = document.createElement('button');
                a.type = 'button';
                a.innerHTML = label;
                a.setAttribute('style', opts.active ? activeStyle : (opts.disabled ? disabledStyle : btnStyle));
                if (!opts.active && !opts.disabled && page !== null) {
                    a.addEventListener('click', () => {
                        currentPage = page;
                        renderPagination();
                    });
                } else a.disabled = true;
                li.appendChild(a);
                paginationList.appendChild(li);
            };

            addBtn('<i class="bi bi-chevron-left"></i>', currentPage - 1, { disabled: currentPage === 1 });

            const pages = [];
            if (totalPages <= 7) {
                for (let i = 1; i <= totalPages; i++) pages.push(i);
            } else {
                pages.push(1);
                if (currentPage > 3) pages.push('...');
                const s = Math.max(2, currentPage - 1);
                const e = Math.min(totalPages - 1, currentPage + 1);
                for (let i = s; i <= e; i++) pages.push(i);
                if (currentPage < totalPages - 2) pages.push('...');
                pages.push(totalPages);
            }

            pages.forEach(p => {
                if (p === '...') {
                    const li = document.createElement('li');
                    const span = document.createElement('span');
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

        function resetAndRender() { currentPage = 1; renderPagination(); }

        searchInput.addEventListener('input', resetAndRender);
        barangaySelect.addEventListener('change', resetAndRender);

        assignBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                assignBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                resetAndRender();
            });
        });

        geoBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                geoBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                resetAndRender();
            });
        });

        perPageSelect?.addEventListener('change', function() {
            perPage = parseInt(this.value, 10) || 25;
            resetAndRender();
        });

        renderPagination();
    });
</script>
@endpush