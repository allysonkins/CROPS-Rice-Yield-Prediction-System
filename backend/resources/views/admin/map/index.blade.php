@extends('layouts.app')

@section('title', 'Farm Map')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">🗺️ Farm Map</h4>
    <span class="badge bg-success">{{ $farmData->count() }} Farms</span>
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
                                <td>{{ $farm['yield'] ?? 'N/A' }}</td>
                                <td>
                                    @if($farm['yield'] && $farm['yield'] >= 4.5)
                                        <span class="badge bg-success">High</span>
                                    @elseif($farm['yield'] && $farm['yield'] >= 3.5)
                                        <span class="badge bg-warning text-dark">Medium</span>
                                    @elseif($farm['yield'])
                                        <span class="badge bg-danger">Low</span>
                                    @else
                                        <span class="badge bg-secondary">No Data</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No farms registered yet.</td></tr>
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

        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Farm data from PHP
        var farms = @json($farmData);

        // Define colors based on yield
        function getColor(yieldValue) {
            if (!yieldValue) return '#95a5a6'; // Gray - No data
            if (yieldValue >= 4.5) return '#27ae60'; // Green - High
            if (yieldValue >= 3.5) return '#f39c12'; // Orange - Medium
            return '#e74c3c'; // Red - Low
        }

        // Add markers for each farm
        farms.forEach(function(farm) {
            if (!farm.lat || !farm.lng) return;

            var color = getColor(farm.yield);
            var popupContent = `
                <div style="min-width: 200px;">
                    <h6><strong>${farm.name}</strong></h6>
                    <hr style="margin: 5px 0;">
                    <p style="margin: 2px 0;"><strong>Barangay:</strong> ${farm.barangay}</p>
                    <p style="margin: 2px 0;"><strong>Farmer:</strong> ${farm.farmer}</p>
                    <p style="margin: 2px 0;"><strong>Land Area:</strong> ${farm.land_area} ha</p>
                    <p style="margin: 2px 0;"><strong>Soil Type:</strong> ${farm.soil_type}</p>
                    <p style="margin: 2px 0;"><strong>Predicted Yield:</strong> ${farm.yield ? farm.yield + ' t/ha' : 'N/A'}</p>
                    <div style="margin-top: 8px;">
                        <span style="background: ${color}; color: white; padding: 2px 12px; border-radius: 20px; font-size: 12px;">
                            ${!farm.yield ? 'No Data' : farm.yield >= 4.5 ? 'High Yield' : farm.yield >= 3.5 ? 'Medium Yield' : 'Low Yield'}
                        </span>
                    </div>
                </div>
            `;

            // Custom marker with colored circle
            var marker = L.circleMarker([farm.lat, farm.lng], {
                radius: 12,
                fillColor: color,
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(map);

            marker.bindPopup(popupContent);

            // Add click to zoom
            marker.on('click', function() {
                map.setView([farm.lat, farm.lng], 16);
            });
        });

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
                    <div class="legend-item"><span class="legend-color" style="background: #95a5a6;"></span> No Data</div>
                </div>
            `;
            return div;
        };
        legend.addTo(map);
    });
</script>
@endpush
@endsection