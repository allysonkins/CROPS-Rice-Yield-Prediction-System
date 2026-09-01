@extends('layouts.app')

@section('title', 'Rice Predictions')

@section('content')
<!-- ============================================================ -->
<!-- MODEL OVERVIEW METRICS -->
<!-- ============================================================ -->
@php
    $hasPredictions = ($uniquePredictions->count() > 0);
    $cityAverage = $hasPredictions ? number_format($uniquePredictions->avg('predicted_yield_tons_ha'), 2) : null;
    $totalRecords = $uniquePredictions->count();
@endphp

<div class="row g-2 mb-4">
    <div class="col-12 col-sm-4">
        <div class="stat-card" style="border-left-color: var(--green);">
            <div class="stat-info">
                <div class="label" style="text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;">Overall City Average Forecast</div>
                <div class="value" style="font-size: 28px;">
                    @if($hasPredictions)
                        {{ $cityAverage }} <span style="font-size: 16px; color: var(--gray-400);">t/ha</span>
                    @else
                        <span style="font-size: 20px; color: var(--gray-400);">No data</span>
                    @endif
                </div>
                @if($hasPredictions)
                    <div class="text-muted small">Based on {{ $totalRecords }} farm predictions</div>
                @endif
            </div>
            <div class="stat-icon"><i class="bi bi-graph-up-arrow" style="color: var(--green);"></i></div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="stat-card" style="border-left-color: var(--gold);">
            <div class="stat-info">
                <div class="label" style="text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;">Total Predictions</div>
                <div class="value" style="font-size: 28px;">
                    {{ $totalRecords }}
                </div>
                <div class="text-muted small">Random Forest model</div>
            </div>
            <div class="stat-icon"><i class="bi bi-cpu" style="color: var(--gold);"></i></div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="stat-card" style="border-left-color: #4f46e5;">
            <div class="stat-info">
                <div class="label" style="text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;">Last Updated</div>
                <div class="value" style="font-size: 22px;">
                    @if($hasPredictions)
                        {{ $uniquePredictions->first()->updated_at->format('M d, Y') }}
                    @else
                        <span style="font-size: 18px; color: var(--gray-400);">N/A</span>
                    @endif
                </div>
                <div class="text-muted small">Latest prediction refresh</div>
            </div>
            <div class="stat-icon"><i class="bi bi-clock" style="color: #4f46e5;"></i></div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- GENERATE PREDICTION BUTTON -->
<!-- ============================================================ -->
<div class="d-flex justify-content-end align-items-center mb-3">
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#generatePredictionModal">
        <i class="bi bi-plus-circle"></i> Generate New Prediction
    </button>
</div>

<!-- ============================================================ -->
<!-- PREDICTIONS TABLE -->
<!-- ============================================================ -->
<div class="row g-3">
    <div class="col-12">
        <div class="card-custom">
            <div class="card-title"><i class="bi bi-table"></i> Prediction Records</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Farm</th>
                            <th>Barangay</th>
                            <th>Variety</th>
                            <th>Season</th>
                            <th>RF Yield</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            @if(auth()->user()->role !== 'farmer')
                                <th>Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($uniquePredictions as $prediction)
                            @php
                                $farmName = $prediction->farmRecord->farm->name ?? 'N/A';
                                $barangay = $prediction->farmRecord->farm->barangay ?? 'N/A';
                                $variety = $prediction->farmRecord->riceVariety->name ?? 'N/A';
                                $season = $prediction->farmRecord->season ?? 'N/A';
                                $yield = $prediction->predicted_yield_tons_ha;
                                $statusClass = $yield >= 4.5 ? 'high' : ($yield >= 3.5 ? 'medium' : 'low');
                                $statusText = $yield >= 4.5 ? 'High' : ($yield >= 3.5 ? 'Medium' : 'Low');
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><strong>{{ $farmName }}</strong></td>
                                <td>{{ $barangay }}</td>
                                <td>{{ $variety }}</td>
                                <td>{{ $season }}</td>
                                <td><strong style="color: #0f4c2b;">{{ number_format($yield, 2) }}</strong></td>
                                <td>
                                    <span class="badge-status {{ $statusClass }}">
                                        <span class="dot"></span> {{ $statusText }}
                                    </span>
                                </td>
                                <td>{{ $prediction->updated_at->diffForHumans() }}</td>
                                @if(auth()->user()->role !== 'farmer')
                                    <td>
                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#viewPredictionModal" data-farm-record-id="{{ $prediction->farm_record_id }}" title="View">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <form action="{{ route('admin.predictions.update', $prediction->farm_record_id) }}" method="POST" class="d-inline">
                                            @csrf @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-secondary" title="Regenerate">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->role !== 'farmer' ? 9 : 8 }}" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 28px;"></i>
                                    <p class="mt-2 mb-0">No predictions generated yet.</p>
                                    <small>Run the ML service to generate predictions.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- GENERATE PREDICTION MODAL -->
<!-- ============================================================ -->
@if(auth()->user()->role !== 'farmer')
    <div class="modal fade" id="generatePredictionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--green); color: white;">
                    <h5 class="modal-title"><i class="bi bi-graph-up"></i> Generate Yield Prediction</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="generatePredictionModalBody">
                    <!-- Content will be loaded via AJAX -->
                    <div class="text-center py-4" id="modalLoading">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading prediction form...</p>
                    </div>
                    <div id="modalContent" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- ============================================================ -->
<!-- VIEW PREDICTION MODAL -->
<!-- ============================================================ -->
@if(auth()->user()->role !== 'farmer')
    <div class="modal fade" id="viewPredictionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: #4f46e5; color: white;">
                    <h5 class="modal-title"><i class="bi bi-eye"></i> Prediction Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewPredictionModalBody">
                    <div class="text-center py-4" id="viewModalLoading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading prediction details...</p>
                    </div>
                    <div id="viewModalContent" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>
@endif

@endsection

@push('scripts')
@if(auth()->user()->role !== 'farmer')
<script>
    // ============================================================
    // LOAD GENERATE PREDICTION MODAL
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        const generateModal = document.getElementById('generatePredictionModal');
        if (generateModal) {
            generateModal.addEventListener('show.bs.modal', function() {
                document.getElementById('modalLoading').style.display = 'block';
                document.getElementById('modalContent').style.display = 'none';
                document.getElementById('modalContent').innerHTML = '';

                fetch('{{ route("admin.predictions.create") }}')
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('modalLoading').style.display = 'none';
                        document.getElementById('modalContent').style.display = 'block';
                        document.getElementById('modalContent').innerHTML = html;
                        
                        const form = document.getElementById('generatePredictionForm');
                        if (form) {
                            form.addEventListener('submit', function(e) {
                                e.preventDefault();
                                const submitBtn = form.querySelector('button[type="submit"]');
                                const originalText = submitBtn.innerHTML;
                                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Generating...';
                                submitBtn.disabled = true;

                                const formData = new FormData(form);
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
                                        var modal = bootstrap.Modal.getInstance(generateModal);
                                        modal.hide();
                                        location.reload();
                                    } else {
                                        const errorDiv = document.createElement('div');
                                        errorDiv.id = 'generatePredictionError';
                                        errorDiv.className = 'alert alert-danger mt-3';
                                        errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + (data.error || 'An error occurred.');
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
                            });
                        }
                    })
                    .catch(() => {
                        document.getElementById('modalLoading').style.display = 'none';
                        document.getElementById('modalContent').style.display = 'block';
                        document.getElementById('modalContent').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> Failed to load form. Please try again.
                            </div>
                        `;
                    });
            });
        }
    });

    // ============================================================
    // LOAD VIEW PREDICTION MODAL
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        const viewModal = document.getElementById('viewPredictionModal');
        if (viewModal) {
            viewModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const farmRecordId = button.getAttribute('data-farm-record-id');
                
                document.getElementById('viewModalLoading').style.display = 'block';
                document.getElementById('viewModalContent').style.display = 'none';
                document.getElementById('viewModalContent').innerHTML = '';

                fetch('/admin/predictions/' + farmRecordId)
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('viewModalLoading').style.display = 'none';
                        document.getElementById('viewModalContent').style.display = 'block';
                        document.getElementById('viewModalContent').innerHTML = html;
                    })
                    .catch(() => {
                        document.getElementById('viewModalLoading').style.display = 'none';
                        document.getElementById('viewModalContent').style.display = 'block';
                        document.getElementById('viewModalContent').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> Failed to load prediction details. Please try again.
                            </div>
                        `;
                    });
            });
        }
    });
</script>
@endif
@endpush