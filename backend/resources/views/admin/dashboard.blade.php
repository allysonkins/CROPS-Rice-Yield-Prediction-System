@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <!-- STATS ROW -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="icon"><i class="bi bi-people"></i></div>
                <div class="label">Total Farmers</div>
                <div class="value">{{ $farmerCount ?? 0 }}</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="icon"><i class="bi bi-geo-alt"></i></div>
                <div class="label">Total Farms</div>
                <div class="value">{{ $farmCount ?? 0 }}</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="icon"><i class="bi bi-seedling"></i></div>
                <div class="label">Rice Varieties</div>
                <div class="value">{{ $varietyCount ?? 0 }}</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card" style="border-left-color: #f59e0b;">
                <div class="icon"><i class="bi bi-graph-up-arrow"></i></div>
                <div class="label">Avg Yield (Tons/ha)</div>
                <div class="value">{{ $avgYield ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <!-- CHART + LOW YIELD ROW -->
    <div class="row g-4">
        <!-- Chart -->
        <div class="col-12 col-lg-8">
            <div class="card-custom">
                <div class="card-title">📊 Yield Prediction Comparison</div>
                <canvas id="yieldChart" height="250"></canvas>
                <div class="mt-3 text-muted small">Comparison of Random Forest, XGBoost, and Ensemble predictions across farms.</div>
            </div>
        </div>

        <!-- Low Yield Areas -->
        <div class="col-12 col-lg-4">
            <div class="card-custom">
                <div class="card-title">⚠️ Low Yield Areas</div>
                @forelse($lowYieldFarms ?? [] as $farm)
                    <div class="list-group-item-custom">
                        <div>
                            <strong>{{ $farm->name }}</strong><br>
                            <small class="text-muted">{{ $farm->barangay }}</small>
                        </div>
                        <span class="badge-low">
                            {{ $farm->predicted_yield ?? 'N/A' }} t/ha
                        </span>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 28px;"></i>
                        <p class="mt-2">No low yield areas detected.</p>
                        <small>All farms are performing well.</small>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- RECENT PREDICTIONS TABLE (Optional extra) -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card-custom">
                <div class="card-title">📋 Recent Farm Records</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Farm</th>
                                <th>Variety</th>
                                <th>Season</th>
                                <th>Ensemble Yield</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox"></i> No predictions generated yet. Run the ML service to see results!
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Dummy data for the chart – We'll replace this with real API data soon!
    const ctx = document.getElementById('yieldChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Farm A', 'Farm B', 'Farm C', 'Farm D'],
            datasets: [
                { 
                    label: 'Random Forest', 
                    data: [4.2, 3.8, 5.1, 4.0], 
                    backgroundColor: '#3b82f6', 
                    borderRadius: 6 
                },
                { 
                    label: 'XGBoost', 
                    data: [4.5, 3.9, 4.8, 4.2], 
                    backgroundColor: '#f59e0b', 
                    borderRadius: 6 
                },
                { 
                    label: 'Ensemble', 
                    data: [4.3, 3.8, 4.9, 4.1], 
                    backgroundColor: '#22c55e', 
                    borderRadius: 6 
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { beginAtZero: true, max: 6 }
            }
        }
    });
</script>
@endpush