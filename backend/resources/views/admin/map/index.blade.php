@extends('layouts.app')

@section('title', 'Farm Map')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">🗺️ Farm Map</h4>
    <div>
        <button id="toggleMap" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-repeat"></i> Switch to Satellite
        </button>
        <span class="badge bg-success ms-2">{{ $farmData->count() }} Farms</span>
    </div>
</div>

<!-- Leaflet.js CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    #farmMap { height: 600px; border-radius: 16px; z-index: 1; }
    .legend { background: white; padding: 10px 15px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .legend-item { display: flex; align-items: center; gap: 8px; margin: 4px 0; }
    .legend-color { width: 20px; height: 20px; border-radius: 50%; }
</style>

<div class="card-custom" style="padding: 0; overflow: hidden;">
    <div id="farmMap"></div>
</div>

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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize map centered on Santiago City
        var map = L.map('farmMap').setView([16.6889, 121.5484], 13);

        // ===== LAYER DEFINITIONS =====
        var streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        });

        var satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: '&copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
        });

        // Add street layer by default
        streetLayer.addTo(map);

        // Store current view state
        var isSatellite = false;

        // Toggle button
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

        // Farm data from PHP
        var farms = @json($farmData);

        // Filter farms with coordinates
        var hasCoords = farms.filter(function(f) {
            return f.lat !== null && f.lng !== null &&
                   !isNaN(f.lat) && !isNaN(f.lng) &&
                   f.lat !== 0 && f.lng !== 0;
        });

        console.log('Farms with coordinates:', hasCoords.length);

        // Define colors based on yield
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

        // Add markers
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
                    @if(auth()->user()->role !== 'farmer')
                        <a href="/admin/farms/${farm.id}/edit" class="btn btn-sm btn-outline-primary mt-2" style="font-size: 11px;">Edit</a>
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

        // Fit bounds to show all markers
        if (hasCoords.length > 1) {
            var group = L.featureGroup();
            hasCoords.forEach(function(f) {
                group.addLayer(L.circleMarker([f.lat, f.lng]));
            });
            map.fitBounds(group.getBounds().pad(0.2));
        } else if (hasCoords.length === 1) {
            map.setZoom(15);
        }

        // Add legend
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

        // Fallback message if no farms have coordinates
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

        // Resize fix
        setTimeout(function() { map.invalidateSize(); }, 400);
    });
</script>
@endpush
@endsection