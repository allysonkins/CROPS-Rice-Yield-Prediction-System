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

        /* Empty state popup styling */
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

    <!-- Farm list table -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card-custom">
                <h6 class="mb-2">Farm List</h6>
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Farm</th>
                                <th>Barangay</th>
                                <th>Farmer</th>
                                <th>Area (ha)</th>
                                <th>Yield (t/ha)</th>
                                <th>Status</th>
                                @if(auth()->user()->role === 'admin')
                                    <th>Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($farmData as $farm)
                                <tr>
                                    <td><strong>{{ $farm['name'] }}</strong></td>
                                    <td>{{ $farm['barangay'] }}</td>
                                    <td>{{ $farm['farmer'] }}</td>
                                    <td>{{ $farm['land_area'] ?? 'N/A' }}</td>
                                    <td>{{ $farm['yield'] ?? 'N/A' }}</td>
                                    <td>
                                        @if($farm['yield'] && $farm['yield'] >= 4.5)
                                            <span class="badge bg-success">High</span>
                                        @elseif($farm['yield'] && $farm['yield'] >= 3.5)
                                            <span class="badge bg-warning text-dark">Medium</span>
                                        @elseif($farm['yield'])
                                            <span class="badge bg-danger">Low</span>
                                        @else
                                            <span class="badge bg-info">No Prediction</span>
                                        @endif
                                    </td>
                                    @if(auth()->user()->role === 'admin')
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="editFarmFromMap({{ $farm['id'] }})">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()->role === 'admin' ? 7 : 6 }}" class="text-center py-4 text-muted">
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
                        </tbody>
                    </table>
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

    // ============================================================
    // OPEN MODAL FOR ADD
    // ============================================================
    function openFarmModal() {
        document.getElementById('modalTitle').textContent = 'Add Farm';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        const modalEl = document.getElementById('farmModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

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

    // ============================================================
    // OPEN MODAL FOR EDIT
    // ============================================================
    function editFarmFromMap(id) {
        document.getElementById('modalTitle').textContent = 'Edit Farm';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        const modalEl = document.getElementById('farmModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

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

    // ============================================================
    // INIT MODAL MAP (targets #farmModalMap)
    // ============================================================
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

    // Clear location
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

    // Cleanup on modal close
    document.addEventListener('hidden.bs.modal', function(e) {
        if (e.target.id === 'farmModal') {
            if (modalMap) {
                try { modalMap.off(); modalMap.remove(); } catch(ex) {}
                modalMap = null;
                modalMarker = null;
            }
        }
    });

    // Form submission
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
    // MAIN PAGE MAP (targets #farmMap — separate from modal)
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

        function getColor(y) {
            if (!y) return '#6b7280';
            if (y >= 4.5) return '#27ae60';
            if (y >= 3.5) return '#f39c12';
            return '#e74c3c';
        }
        function getStatus(y) {
            if (!y) return 'No Data';
            if (y >= 4.5) return 'High Yield';
            if (y >= 3.5) return 'Medium Yield';
            return 'Low Yield';
        }

        @php $isAdmin = auth()->user()->role === 'admin'; @endphp

        hasCoords.forEach(function(farm) {
            var color = getColor(farm.yield);
            var popup = `
                <div style="min-width: 200px;">
                    <h6 style="margin: 0 0 4px 0; color: #0f4c2b;"><strong>${farm.name}</strong></h6>
                    <hr style="margin: 4px 0;">
                    <p style="margin: 2px 0;"><strong>Barangay:</strong> ${farm.barangay}</p>
                    <p style="margin: 2px 0;"><strong>Farmer:</strong> ${farm.farmer}</p>
                    <p style="margin: 2px 0;"><strong>Land Area:</strong> ${farm.land_area ?? 'N/A'} ha</p>
                    <p style="margin: 2px 0;"><strong>Predicted Yield:</strong> ${farm.yield ? farm.yield + ' t/ha' : 'No data'}</p>
                    <div style="margin-top: 6px;">
                        <span style="background: ${color}; color: white; padding: 2px 12px; border-radius: 20px; font-size: 11px;">
                            ${getStatus(farm.yield)}
                        </span>
                    </div>
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
                    <strong>Yield Legend</strong>
                    <div class="legend-item"><span class="legend-color" style="background: #27ae60;"></span> High (>4.5 t/ha)</div>
                    <div class="legend-item"><span class="legend-color" style="background: #f39c12;"></span> Medium (3.5-4.5 t/ha)</div>
                    <div class="legend-item"><span class="legend-color" style="background: #e74c3c;"></span> Low (<3.5 t/ha)</div>
                    <div class="legend-item"><span class="legend-color" style="background: #6b7280;"></span> No Data</div>
                </div>
            `;
            return div;
        };
        legend.addTo(map);

        // ============================================================
        // EMPTY STATE — Show "Add Farm" card in the middle if no farms
        // ============================================================
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
    });
</script>
@endpush