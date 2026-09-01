@extends('layouts.app')

@section('title', 'Manage Farms')

@section('content')
    <!-- GUARANTEE LEAFLET CSS LOADS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #farmMap {
            height: 350px;
            width: 100%;
            border-radius: 12px;
            border: 1px solid #ddd;
            background: #e8ecf1;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        .leaflet-control-zoom {
            z-index: 1050 !important;
        }
        .map-toggle-btn {
            margin-bottom: 8px;
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
                                    <button type="button" class="btn btn-sm btn-primary mt-2" onclick="openFarmModal()">
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

    <!-- Include the Modal -->
    @if(auth()->user()->role === 'admin')
        @include('admin.farms.partials.modal')
    @endif

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        @if(auth()->user()->role === 'admin')
        let mapInstance = null;
        let markerInstance = null;

        // ============================================================
        // SANTIAGO CITY BOUNDS (approximate)
        // ============================================================
        const SANTIAGO_BOUNDS = {
            minLat: 16.65,
            maxLat: 16.73,
            minLng: 121.52,
            maxLng: 121.58
        };

        function clampToSantiago(lat, lng) {
            return {
                lat: Math.min(Math.max(lat, SANTIAGO_BOUNDS.minLat), SANTIAGO_BOUNDS.maxLat),
                lng: Math.min(Math.max(lng, SANTIAGO_BOUNDS.minLng), SANTIAGO_BOUNDS.maxLng)
            };
        }

        function initMap() {
            const container = document.getElementById('farmMap');
            if (!container) return;

            if (mapInstance) {
                mapInstance.off();
                mapInstance.remove();
                mapInstance = null;
                markerInstance = null;
            }

            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            let lat = latInput && latInput.value ? parseFloat(latInput.value) : 16.6889;
            let lng = lngInput && lngInput.value ? parseFloat(lngInput.value) : 121.5484;
            // Clamp initial coordinates to Santiago
            const clamped = clampToSantiago(lat, lng);
            lat = clamped.lat;
            lng = clamped.lng;

            mapInstance = L.map(container, {
                center: [lat, lng],
                zoom: 14,
                maxBounds: [
                    [SANTIAGO_BOUNDS.minLat - 0.02, SANTIAGO_BOUNDS.minLng - 0.02],
                    [SANTIAGO_BOUNDS.maxLat + 0.02, SANTIAGO_BOUNDS.maxLng + 0.02]
                ],
                maxBoundsViscosity: 0.8
            });

            // ===== LAYER DEFINITIONS WITH LABELS =====
            const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            });

            const satelliteImg = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: '&copy; Esri'
            });

            const satelliteLabels = L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
                attribution: '&copy; Esri'
            });

            const satelliteLayer = L.layerGroup([satelliteImg, satelliteLabels]);

            // Add default street layer
            streetLayer.addTo(mapInstance);

            // ===== CUSTOM TOGGLE BUTTON ON THE MAP =====
            const ToggleControl = L.Control.extend({
                options: { position: 'topright' },
                onAdd: function (map) {
                    const btn = L.DomUtil.create('button', 'btn btn-sm btn-secondary shadow-sm');
                    btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Switch to Satellite';
                    btn.style.margin = '10px';
                    btn.style.pointerEvents = 'auto';
                    
                    L.DomEvent.disableClickPropagation(btn);
                    
                    let isSatellite = false;
                    
                    L.DomEvent.on(btn, 'click', function(e) {
                        L.DomEvent.preventDefault(e);
                        if (isSatellite) {
                            map.removeLayer(satelliteLayer);
                            streetLayer.addTo(map);
                            btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Switch to Satellite';
                            btn.classList.replace('btn-primary', 'btn-secondary');
                            isSatellite = false;
                        } else {
                            map.removeLayer(streetLayer);
                            satelliteLayer.addTo(map);
                            btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Switch to Street';
                            btn.classList.replace('btn-secondary', 'btn-primary');
                            isSatellite = true;
                        }
                    });
                    
                    return btn;
                }
            });
            
            mapInstance.addControl(new ToggleControl());

            // ===== MARKER LOGIC WITH CLAMPING =====
            if (latInput && latInput.value && lngInput && lngInput.value) {
                markerInstance = L.marker([lat, lng], { draggable: true }).addTo(mapInstance);
                markerInstance.on('dragend', function() {
                    const pos = markerInstance.getLatLng();
                    const clampedPos = clampToSantiago(pos.lat, pos.lng);
                    markerInstance.setLatLng(clampedPos);
                    latInput.value = clampedPos.lat.toFixed(8);
                    lngInput.value = clampedPos.lng.toFixed(8);
                });
            }

            mapInstance.on('click', function(e) {
                const clampedPos = clampToSantiago(e.latlng.lat, e.latlng.lng);
                setMarker(clampedPos.lat, clampedPos.lng);
            });

            setTimeout(() => {
                if (mapInstance) {
                    mapInstance.invalidateSize();
                }
            }, 300);

            if (latInput && lngInput) {
                const updateFromInputs = () => {
                    const typedLat = parseFloat(latInput.value);
                    const typedLng = parseFloat(lngInput.value);
                    if (!isNaN(typedLat) && !isNaN(typedLng)) {
                        const clamped = clampToSantiago(typedLat, typedLng);
                        setMarker(clamped.lat, clamped.lng, false);
                    }
                };
                latInput.addEventListener('input', updateFromInputs);
                lngInput.addEventListener('input', updateFromInputs);
            }
        }

        function setMarker(lat, lng, pan = true) {
            const clamped = clampToSantiago(lat, lng);
            lat = clamped.lat;
            lng = clamped.lng;

            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            if (!latInput || !lngInput) return;
            
            latInput.value = lat.toFixed(8);
            lngInput.value = lng.toFixed(8);

            if (markerInstance) {
                markerInstance.setLatLng([lat, lng]);
            } else if (mapInstance) {
                markerInstance = L.marker([lat, lng], { draggable: true }).addTo(mapInstance);
                markerInstance.on('dragend', function() {
                    const pos = markerInstance.getLatLng();
                    const clampedPos = clampToSantiago(pos.lat, pos.lng);
                    markerInstance.setLatLng(clampedPos);
                    latInput.value = clampedPos.lat.toFixed(8);
                    lngInput.value = clampedPos.lng.toFixed(8);
                });
            }

            if (mapInstance && pan) {
                mapInstance.setView([lat, lng], mapInstance.getZoom());
            }
        }

        function openFarmModal() {
            document.getElementById('modalTitle').textContent = 'Add Farm';
            document.getElementById('modalLoading').style.display = 'block';
            document.getElementById('modalContent').style.display = 'none';
            document.getElementById('modalContent').innerHTML = '';

            const modalEl = document.getElementById('farmModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show(); 

            fetch('{{ route("admin.farms.create") }}')
                .then(response => response.text())
                .then(html => {
                    document.getElementById('modalLoading').style.display = 'none';
                    document.getElementById('modalContent').style.display = 'block';
                    document.getElementById('modalContent').innerHTML = html;

                    const form = document.getElementById('farmForm');
                    if (form) {
                        form.addEventListener('submit', handleSubmit);
                    }

                    setTimeout(() => {
                        initMap();
                    }, 300);
                });
        }

        function editFarm(id) {
            document.getElementById('modalTitle').textContent = 'Edit Farm';
            document.getElementById('modalLoading').style.display = 'block';
            document.getElementById('modalContent').style.display = 'none';
            document.getElementById('modalContent').innerHTML = '';

            const modalEl = document.getElementById('farmModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show(); 

            fetch('/admin/farms/' + id + '/edit')
                .then(response => response.text())
                .then(html => {
                    document.getElementById('modalLoading').style.display = 'none';
                    document.getElementById('modalContent').style.display = 'block';
                    document.getElementById('modalContent').innerHTML = html;

                    const form = document.getElementById('farmForm');
                    if (form) {
                        form.addEventListener('submit', handleSubmit);
                    }

                    setTimeout(() => {
                        initMap();
                    }, 300);
                });
        }

        function handleSubmit(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Saving...';
            submitBtn.disabled = true;

            const existingErrors = form.querySelector('#formErrors');
            if (existingErrors) existingErrors.remove();

            fetch(form.action, {
                method: form.method || 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('farmModal'));
                    modal.hide();
                    location.reload();
                } else {
                    const errorDiv = document.createElement('div');
                    errorDiv.id = 'formErrors';
                    errorDiv.className = 'alert alert-danger mt-3';
                    let errorMsg = data.error || 'An error occurred.';
                    if (data.errors) {
                        errorMsg = Object.values(data.errors).flat().join('<br>');
                    }
                    errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + errorMsg;
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

        document.addEventListener('click', function(e) {
            if (e.target.id === 'clearLocation' || e.target.closest('#clearLocation')) {
                const latInput = document.getElementById('latitude');
                const lngInput = document.getElementById('longitude');
                if (latInput) latInput.value = '';
                if (lngInput) lngInput.value = '';
                if (markerInstance) {
                    mapInstance.removeLayer(markerInstance);
                    markerInstance = null;
                }
                if (mapInstance) {
                    mapInstance.setView([16.6889, 121.5484], 13);
                }
            }
        });

        document.addEventListener('hidden.bs.modal', function(e) {
            if (e.target.id === 'farmModal') {
                if (mapInstance) {
                    try {
                        mapInstance.off();
                        mapInstance.remove();
                    } catch (ex) {}
                    mapInstance = null;
                    markerInstance = null;
                }
            }
        });
        @endif
    </script>
@endsection