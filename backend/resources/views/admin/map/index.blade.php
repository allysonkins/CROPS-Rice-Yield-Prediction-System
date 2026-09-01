@extends('layouts.app')

@section('title', 'Farm Map')

@section('content')
    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        #farmMap {
            height: 600px;
            border-radius: 16px;
            z-index: 1;
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
        /* Modal styles for edit */
        #farmEditModal .modal-body {
            overflow: hidden !important;
        }
        #editModalContent #farmMap {
            width: 100%;
            height: 350px;
            min-height: 350px;
            border-radius: 6px;
            background: #e8ecf1;
        }
        .leaflet-control-zoom {
            z-index: 1050 !important;
        }
        .map-controls {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 10px;
        }
        .map-controls .badge {
            margin-right: 12px;
        }
    </style>

    <!-- Controls row: Satellite toggle button on the right, above the map -->
    <div class="map-controls">
        <span class="badge bg-success">{{ $farmData->count() }} Farms</span>
        <button id="toggleMap" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-repeat"></i> Switch to Satellite
        </button>
    </div>

    <!-- Map container -->
    <div class="card-custom" style="padding: 0; overflow: hidden;">
        <div id="farmMap"></div>
    </div>

    <!-- Farm list table (unchanged) -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card-custom">
                <h6 class="mb-2">📋 Farm List</h6>
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
                                        @if($farm['lat'] && $farm['lng'])
                                            @if($farm['yield'] && $farm['yield'] >= 4.5)
                                                <span class="badge bg-success">High</span>
                                            @elseif($farm['yield'] && $farm['yield'] >= 3.5)
                                                <span class="badge bg-warning text-dark">Medium</span>
                                            @elseif($farm['yield'])
                                                <span class="badge bg-danger">Low</span>
                                            @else
                                                <span class="badge bg-info">No Prediction</span>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary">No Location</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        @if(auth()->user()->role === 'farmer')
                                            <i class="bi bi-geo-alt" style="font-size: 36px; color: var(--gray-400);"></i>
                                            <p class="mt-3 mb-1" style="font-size: 16px;">No farms assigned to you yet.</p>
                                            <p class="text-muted small">Please contact the City Agriculture Office to register your farms.</p>
                                        @else
                                            <i class="bi bi-inbox" style="font-size: 36px; color: var(--gray-400);"></i>
                                            <p class="mt-3 mb-1" style="font-size: 16px;">No farms registered yet.</p>
                                            <p class="text-muted small">Add farms to see them on the map.</p>
                                            @if(auth()->user()->role !== 'farmer')
                                                <a href="/admin/farms/create" class="btn btn-sm btn-success mt-2">Add Farm</a>
                                            @endif
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

    <!-- ===== EDIT FARM MODAL (Admin only) ===== -->
    @if(auth()->user()->role === 'admin')
        <div class="modal fade" id="farmEditModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header" style="background: var(--green); color: white;">
                        <h5 class="modal-title"><i class="bi bi-geo-alt-fill"></i> <span id="editModalTitle">Edit Farm</span></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="editModalBody" style="overflow: hidden;">
                        <div class="text-center py-4" id="editModalLoading">
                            <div class="spinner-border text-success" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading form...</p>
                        </div>
                        <div id="editModalContent" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
    // ============================================================
    // GLOBAL EDIT MAP VARIABLES & FUNCTIONS (Admin only)
    // ============================================================
    @if(auth()->user()->role === 'admin')
    window.editMapInstance = null;
    window.editMarkerInstance = null;

    window.editFarmFromMap = function(id) {
        document.getElementById('editModalTitle').textContent = 'Edit Farm';
        document.getElementById('editModalLoading').style.display = 'block';
        document.getElementById('editModalContent').style.display = 'none';
        document.getElementById('editModalContent').innerHTML = '';

        fetch('/admin/farms/' + id + '/edit')
            .then(response => response.text())
            .then(html => {
                document.getElementById('editModalLoading').style.display = 'none';
                document.getElementById('editModalContent').style.display = 'block';
                document.getElementById('editModalContent').innerHTML = html;

                setTimeout(function() {
                    initEditMap();
                }, 500);

                const form = document.getElementById('farmForm');
                if (form) {
                    form.addEventListener('submit', window.handleEditFormSubmit);
                }
            })
            .catch(() => {
                document.getElementById('editModalLoading').style.display = 'none';
                document.getElementById('editModalContent').style.display = 'block';
                document.getElementById('editModalContent').innerHTML = `
                    <div class="alert alert-danger">Failed to load form.</div>
                `;
            });

        var modal = new bootstrap.Modal(document.getElementById('farmEditModal'));
        modal.show();
    };

    window.handleEditFormSubmit = function(e) {
        e.preventDefault();
        var form = e.target;
        var formData = new FormData(form);

        var submitBtn = form.querySelector('button[type="submit"]');
        var originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Saving...';
        submitBtn.disabled = true;

        var existingErrors = form.querySelector('#formErrors');
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
                var modal = bootstrap.Modal.getInstance(document.getElementById('farmEditModal'));
                modal.hide();
                location.reload();
            } else {
                var errorDiv = document.createElement('div');
                errorDiv.id = 'formErrors';
                errorDiv.className = 'alert alert-danger mt-3';
                var errorMsg = data.error || 'An error occurred.';
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
    };

    window.initEditMap = function() {
        var container = document.querySelector('#editModalContent #farmMap');
        if (!container) {
            console.warn('Map container not found in modal, retrying...');
            setTimeout(window.initEditMap, 300);
            return;
        }

        if (window.editMapInstance) {
            window.editMapInstance.off();
            window.editMapInstance.remove();
            window.editMapInstance = null;
            window.editMarkerInstance = null;
        }

        var latInput = document.getElementById('latitude');
        var lngInput = document.getElementById('longitude');
        var lat = latInput && latInput.value ? parseFloat(latInput.value) : 16.6889;
        var lng = lngInput && lngInput.value ? parseFloat(lngInput.value) : 121.5484;

        window.editMapInstance = L.map(container).setView([lat, lng], 14);

        var editStreetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        });

        var editSatImg = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: '&copy; Esri'
        });

        var editSatLabels = L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
            attribution: '&copy; Esri'
        });

        var editSatelliteLayer = L.layerGroup([editSatImg, editSatLabels]);
        editStreetLayer.addTo(window.editMapInstance);

        const EditToggleControl = L.Control.extend({
            options: { position: 'topright' },
            onAdd: function (map) {
                const btn = L.DomUtil.create('button', 'btn btn-sm btn-secondary shadow-sm');
                btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Switch to Satellite';
                btn.style.margin = '10px';
                btn.style.pointerEvents = 'auto';
                
                L.DomEvent.disableClickPropagation(btn);
                let isSat = false;
                
                L.DomEvent.on(btn, 'click', function(e) {
                    L.DomEvent.preventDefault(e);
                    if (isSat) {
                        map.removeLayer(editSatelliteLayer);
                        editStreetLayer.addTo(map);
                        btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Switch to Satellite';
                        btn.classList.replace('btn-primary', 'btn-secondary');
                        isSat = false;
                    } else {
                        map.removeLayer(editStreetLayer);
                        editSatelliteLayer.addTo(map);
                        btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Switch to Street';
                        btn.classList.replace('btn-secondary', 'btn-primary');
                        isSat = true;
                    }
                });
                return btn;
            }
        });
        window.editMapInstance.addControl(new EditToggleControl());

        if (latInput && latInput.value && lngInput && lngInput.value) {
            window.editMarkerInstance = L.marker([lat, lng], { draggable: true }).addTo(window.editMapInstance);
            window.editMarkerInstance.on('dragend', function() {
                var pos = window.editMarkerInstance.getLatLng();
                latInput.value = pos.lat.toFixed(8);
                lngInput.value = pos.lng.toFixed(8);
            });
        }

        window.editMapInstance.on('click', function(e) {
            window.setEditMarker(e.latlng.lat, e.latlng.lng);
        });

        setTimeout(function() {
            if (window.editMapInstance) window.editMapInstance.invalidateSize(true);
        }, 300);
        setTimeout(function() {
            if (window.editMapInstance) window.editMapInstance.invalidateSize(true);
        }, 600);

        if (latInput && lngInput) {
            const updateFromInputs = () => {
                const typedLat = parseFloat(latInput.value);
                const typedLng = parseFloat(lngInput.value);
                if (!isNaN(typedLat) && !isNaN(typedLng)) {
                    window.setEditMarker(typedLat, typedLng, false);
                }
            };
            latInput.addEventListener('input', updateFromInputs);
            lngInput.addEventListener('input', updateFromInputs);
        }
    };

    window.setEditMarker = function(lat, lng, pan = true) {
        var latInput = document.getElementById('latitude');
        var lngInput = document.getElementById('longitude');
        if (!latInput || !lngInput) return;
        latInput.value = lat.toFixed(8);
        lngInput.value = lng.toFixed(8);

        if (window.editMarkerInstance) {
            window.editMarkerInstance.setLatLng([lat, lng]);
        } else if (window.editMapInstance) {
            window.editMarkerInstance = L.marker([lat, lng], { draggable: true }).addTo(window.editMapInstance);
            window.editMarkerInstance.on('dragend', function() {
                var pos = window.editMarkerInstance.getLatLng();
                latInput.value = pos.lat.toFixed(8);
                lngInput.value = pos.lng.toFixed(8);
            });
        }
        if (window.editMapInstance && pan) {
            window.editMapInstance.setView([lat, lng], window.editMapInstance.getZoom());
        }
    };

    document.addEventListener('click', function(e) {
        if (e.target.id === 'clearLocation' || e.target.closest('#clearLocation')) {
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            if (latInput) latInput.value = '';
            if (lngInput) lngInput.value = '';
            if (window.editMarkerInstance && window.editMapInstance) {
                window.editMapInstance.removeLayer(window.editMarkerInstance);
                window.editMarkerInstance = null;
            }
            if (window.editMapInstance) {
                window.editMapInstance.setView([16.6889, 121.5484], 13);
            }
        }
    });

    document.addEventListener('hidden.bs.modal', function(e) {
        if (e.target.id === 'farmEditModal') {
            if (window.editMapInstance) {
                try {
                    window.editMapInstance.off();
                    window.editMapInstance.remove();
                } catch (ex) {}
                window.editMapInstance = null;
                window.editMarkerInstance = null;
            }
        }
    });
    @endif

    // ============================================================
    // MAIN MAP (for all roles)
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        var map = L.map('farmMap').setView([16.6889, 121.5484], 13);

        var streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        });

        var satelliteImg = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: '&copy; Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
        });

        var satelliteLabels = L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
            attribution: '&copy; Esri'
        });

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

        function getColor(yieldValue) {
            if (!yieldValue) return '#6b7280';
            if (yieldValue >= 4.5) return '#27ae60';
            if (yieldValue >= 3.5) return '#f39c12';
            return '#e74c3c';
        }

        function getStatus(yieldValue) {
            if (!yieldValue) return 'No Data';
            if (yieldValue >= 4.5) return 'High Yield';
            if (yieldValue >= 3.5) return 'Medium Yield';
            return 'Low Yield';
        }

        @php
            $isAdmin = auth()->user()->role === 'admin';
        @endphp

        hasCoords.forEach(function(farm) {
            var color = getColor(farm.yield);

            var popupContent = `
                <div style="min-width: 200px;">
                    <h6 style="margin: 0 0 4px 0; color: #0f4c2b;"><strong>${farm.name}</strong></h6>
                    <hr style="margin: 4px 0;">
                    <p style="margin: 2px 0;"><strong>Barangay:</strong> ${farm.barangay}</p>
                    <p style="margin: 2px 0;"><strong>Farmer:</strong> ${farm.farmer}</p>
                    <p style="margin: 2px 0;"><strong>Land Area:</strong> ${farm.land_area ?? 'N/A'} ha</p>
                    <p style="margin: 2px 0;"><strong>Soil Type:</strong> ${farm.soil_type ?? 'N/A'}</p>
                    <p style="margin: 2px 0;"><strong>Predicted Yield:</strong> ${farm.yield ? farm.yield + ' t/ha' : 'No data'}</p>
                    <div style="margin-top: 6px;">
                        <span style="background: ${color}; color: white; padding: 2px 12px; border-radius: 20px; font-size: 11px;">
                            ${getStatus(farm.yield)}
                        </span>
                    </div>
                    @if($isAdmin)
                        <button class="btn btn-sm btn-outline-primary mt-2" style="font-size: 11px;" onclick="window.editFarmFromMap(${farm.id})">
                            Edit
                        </button>
                    @endif
                </div>
            `;

            L.circleMarker([farm.lat, farm.lng], {
                radius: 10,
                fillColor: color,
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(map).bindPopup(popupContent);
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

        if (hasCoords.length === 0) {
            var message = @if(auth()->user()->role === 'farmer') 
                'No farms assigned to you yet. Please contact the City Agriculture Office.'
            @else 
                'No farm locations available. Add coordinates when creating farms to see them here.'
            @endif;

            L.popup()
                .setLatLng([16.6889, 121.5484])
                .setContent(`
                    <div style="text-align: center; padding: 15px;">
                        <p style="color: #6b7280; font-size: 14px; margin: 0;">${message}</p>
                        @if(auth()->user()->role !== 'farmer')
                            <a href="/admin/farms/create" class="btn btn-sm btn-success mt-2">Add Farm</a>
                        @endif
                    </div>
                `)
                .openOn(map);
        }

        setTimeout(function() { map.invalidateSize(); }, 400);
    });
</script>
@endpush