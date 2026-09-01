@extends('layouts.app')

@section('title', 'My Predictions')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <span class="badge bg-secondary">{{ $stats['total'] }} Predictions</span>
</div>

<!-- Stats Cards -->
<div class="row g-2 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Total</div>
                <div class="value">{{ $stats['total'] }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-bar-chart"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Average Yield</div>
                <div class="value">{{ $stats['avg_yield'] ? number_format($stats['avg_yield'], 2) : 'N/A' }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Highest Yield</div>
                <div class="value">{{ $stats['max_yield'] ? number_format($stats['max_yield'], 2) : 'N/A' }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-arrow-up"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Lowest Yield</div>
                <div class="value">{{ $stats['min_yield'] ? number_format($stats['min_yield'], 2) : 'N/A' }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-arrow-down"></i></div>
        </div>
    </div>
</div>

<!-- Predictions Table -->
<div class="card-custom">
    <div class="card-title"><i class="bi bi-table"></i> Prediction Records</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Farm</th>
                    <th>Variety</th>
                    <th>Season</th>
                    <th>RF Yield</th>
                    <th>Status</th>
                    <th>Crop Status</th>
                    <th>Updated</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($predictions as $index => $pred)
                    @php
                        $yield = $pred->predicted_yield_tons_ha;
                        $statusClass = $yield >= 4.5 ? 'high' : ($yield >= 3.5 ? 'medium' : 'low');
                        $statusText = $yield >= 4.5 ? 'High' : ($yield >= 3.5 ? 'Medium' : 'Low');
                        $farmRecord = $pred->farmRecord;
                        $cropStatus = $farmRecord->status ?? 'N/A';
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $farmRecord->farm->name ?? 'N/A' }}</strong>
                            <br><small class="text-muted">{{ $farmRecord->farm->barangay ?? '' }}</small>
                        </td>
                        <td>{{ $farmRecord->riceVariety->name ?? 'N/A' }}</td>
                        <td>{{ $farmRecord->season ?? 'N/A' }}</td>
                        <td><strong style="color: #0f4c2b;">{{ number_format($yield, 2) }}</strong></td>
                        <td>
                            <span class="badge-status {{ $statusClass }}">
                                <span class="dot"></span> {{ $statusText }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $farmRecord->status_badge_class ?? 'bg-secondary' }}">
                                <i class="bi {{ $farmRecord->status_icon ?? 'bi-question-circle' }}"></i>
                                {{ $cropStatus }}
                            </span>
                        </td>
                        <td>{{ $pred->updated_at->diffForHumans() }}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#predictionDetailModal" data-prediction-id="{{ $pred->id }}" title="View Details">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 28px;"></i>
                            <p class="mt-2 mb-0">No predictions for your farms yet.</p>
                            <small>CAO staff will generate predictions from your farm records.</small>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================================ -->
<!-- PREDICTION DETAIL MODAL -->
<!-- ============================================================ -->
<div class="modal fade" id="predictionDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--green); color: white;">
                <h5 class="modal-title"><i class="bi bi-eye"></i> Prediction Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="predictionDetailModalBody" style="overflow: hidden;">
                <div class="text-center py-4" id="predictionDetailLoading">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading prediction details...</p>
                </div>
                <div id="predictionDetailContent" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('predictionDetailModal');
        if (!modal) return;

        modal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const predictionId = button.getAttribute('data-prediction-id');

            document.getElementById('predictionDetailLoading').style.display = 'block';
            document.getElementById('predictionDetailContent').style.display = 'none';
            document.getElementById('predictionDetailContent').innerHTML = '';

            fetch('/farmer/predictions/' + predictionId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('predictionDetailLoading').style.display = 'none';
                    document.getElementById('predictionDetailContent').style.display = 'block';
                    document.getElementById('predictionDetailContent').innerHTML = html;
                })
                .catch(() => {
                    document.getElementById('predictionDetailLoading').style.display = 'none';
                    document.getElementById('predictionDetailContent').style.display = 'block';
                    document.getElementById('predictionDetailContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> Failed to load prediction details. Please try again.
                        </div>
                    `;
                });
        });
    });
</script>
@endpush