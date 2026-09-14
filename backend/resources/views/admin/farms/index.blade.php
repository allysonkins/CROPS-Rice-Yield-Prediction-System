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
    </style>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-left: 4px solid var(--green);">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2" style="color: var(--green);"></i>
                <strong>Success!</strong> {{ session('success') }}
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-left: 4px solid var(--red);">
            <div class="d-flex align-items-center">
                <i class="bi bi-x-circle-fill me-2" style="color: var(--red);"></i>
                <strong>Error!</strong> {{ session('error') }}
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-end align-items-center mb-4">
        @if(auth()->user()->role === 'admin')
            <button type="button" class="btn btn-success" onclick="openFarmModal()">
                <i class="bi bi-plus-circle"></i> Add Farm
            </button>
        @endif
        <span class="badge bg-secondary ms-2">{{ $farms->count() }} Farms</span>
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

    <!-- Farm List Table -->
    <div class="card-custom">
        <div class="card-title"><i class="bi bi-table"></i> Farm List</div>
        <div class="table-responsive">
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
                            <th>Actions</th>
                        @else
                            <th>Access</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($farms as $farm)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $farm->name }}</strong></td>
                            <td>{{ $farm->barangay }}</td>
                            <td>{{ $farm->user->name ?? 'Unassigned' }}</td>
                            <td>{{ number_format($farm->land_area_ha, 2) }}</td>
                            <td>{{ $farm->soil_type }}</td>
                            <td>
                                @if($farm->latitude && $farm->longitude)
                                    <span class="badge bg-light text-muted" style="font-size: 10px;">
                                        {{ number_format($farm->latitude, 6) }}, {{ number_format($farm->longitude, 6) }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if(auth()->user()->role === 'admin')
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="editFarm({{ $farm->id }})">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('admin.farms.destroy', $farm->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this farm?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
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
                </tbody>
            </table>
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
    // MODAL MAP STATE (targets #farmModalMap, NOT #farmMap)
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

    // ============================================================
    // OPEN MODAL — ADD FARM
    // ============================================================
    function openFarmModal() {
        document.getElementById('modalTitle').textContent = 'Add Farm';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        const modalEl = document.getElementById('farmModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        // IMPORTANT: send AJAX header so controller returns the view, not a redirect
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

    // ============================================================
    // OPEN MODAL — EDIT FARM
    // ============================================================
    function editFarm(id) {
        document.getElementById('modalTitle').textContent = 'Edit Farm';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        const modalEl = document.getElementById('farmModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        // IMPORTANT: send AJAX header so controller returns the view, not a redirect
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

    // ============================================================
    // INIT MODAL MAP (targets #farmModalMap)
    // ============================================================
    function initModalMap() {
        const container = document.getElementById('farmModalMap');
        if (!container) {
            setTimeout(initModalMap, 200);
            return;
        }

        // Destroy previous instance if any
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

        // Layers
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

        // Toggle control
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

        // Existing marker
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

        // Click to place
        modalMap.on('click', function (e) {
            const cl = clampToSantiago(e.latlng.lat, e.latlng.lng);
            setModalMarker(cl.lat, cl.lng);
        });

        // Live sync when typing coordinates
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

    // Clear location
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

    // Cleanup when modal closes
    document.addEventListener('hidden.bs.modal', function (e) {
        if (e.target.id === 'farmModal') {
            if (modalMap) {
                try { modalMap.off(); modalMap.remove(); } catch (ex) {}
                modalMap = null;
                modalMarker = null;
            }
        }
    });

    // ============================================================
    // FORM SUBMIT (create + edit)
    // ============================================================
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
</script>
@endpush