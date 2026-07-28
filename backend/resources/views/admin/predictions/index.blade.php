@extends('layouts.app')

@section('title', 'Yield Predictions')

@section('content')
<!-- ============================================================ -->
<!-- FLASH MESSAGES (Single source of truth)                       -->
<!-- ============================================================ -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-left: 4px solid var(--green);">
        <div class="d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-2" style="color: var(--green);"></i>
            <strong>Success!</strong> {{ session('success') }}
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert" style="border-left: 4px solid var(--gold);">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-2" style="color: var(--gold);"></i>
            <strong>Warning!</strong> {{ session('warning') }}
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

<!-- ============================================================ -->
<!-- HEADER: Button + Badge                                       -->
<!-- ============================================================ -->
<div class="d-flex justify-content-end align-items-center mb-4">
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#generatePredictionModal">
        <i class="bi bi-plus-circle"></i> Generate Prediction
    </button>
    <span class="badge bg-secondary ms-2">{{ $uniquePredictions->count() }} Predictions</span>
</div>

<!-- Stats Cards -->
@php
    $total = $uniquePredictions->count();
    $avgYield = $uniquePredictions->avg('predicted_yield_tons_ha');
    $maxYield = $uniquePredictions->max('predicted_yield_tons_ha');
    $minYield = $uniquePredictions->min('predicted_yield_tons_ha');
    $avgEnsemble = $uniquePredictions->avg('predicted_yield_tons_ha');
    $rfAvg = \App\Models\Prediction::where('model_type', 'RandomForest')->avg('predicted_yield_tons_ha');
    $xgbAvg = \App\Models\Prediction::where('model_type', 'XGBoost')->avg('predicted_yield_tons_ha');
@endphp

<div class="row g-2 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Total Predictions</div>
                <div class="value">{{ $total }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-bar-chart"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Average Yield</div>
                <div class="value">{{ $avgYield ? number_format($avgYield, 2) : 'N/A' }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Highest Yield</div>
                <div class="value">{{ $maxYield ? number_format($maxYield, 2) : 'N/A' }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-arrow-up"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Lowest Yield</div>
                <div class="value">{{ $minYield ? number_format($minYield, 2) : 'N/A' }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-arrow-down"></i></div>
        </div>
    </div>
</div>

<!-- Model Comparison Section -->
<div class="row g-2 mb-4">
    <div class="col-12">
        <div class="card-custom">
            <div class="card-title"><i class="bi bi-cpu"></i> AI Model Comparison Report</div>
            <div class="row g-2">
                <div class="col-12 col-md-4">
                    <div class="card p-3 text-center" style="border: none; background: var(--gray-50); border-radius: 10px;">
                        <strong>Random Forest</strong>
                        <h3 class="mt-2" style="color: #0f4c2b;">{{ $rfAvg ? number_format($rfAvg, 2) : 'N/A' }}</h3>
                        <small class="text-muted">Average Predicted Yield</small>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card p-3 text-center" style="border: none; background: var(--gray-50); border-radius: 10px;">
                        <strong>XGBoost</strong>
                        <h3 class="mt-2" style="color: #b8860b;">{{ $xgbAvg ? number_format($xgbAvg, 2) : 'N/A' }}</h3>
                        <small class="text-muted">Average Predicted Yield</small>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card p-3 text-center" style="border: 2px solid #4f46e5; background: white; border-radius: 10px;">
                        <strong>Ensemble (RF + XGB)</strong>
                        <h3 class="mt-2" style="color: #4f46e5;">{{ $avgEnsemble ? number_format($avgEnsemble, 2) : 'N/A' }}</h3>
                        <small class="text-muted">Average Predicted Yield</small>
                    </div>
                </div>
            </div>
            <div class="mt-2 text-muted small">The Ensemble model averages Random Forest and XGBoost predictions for improved accuracy.</div>
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
                    <th>Random Forest</th>
                    <th>XGBoost</th>
                    <th>Ensemble</th>
                    <th>Status</th>
                    <th>Last Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($uniquePredictions as $prediction)
                    @php
                        $farmName = $prediction->farmRecord->farm->name ?? 'N/A';
                        $barangay = $prediction->farmRecord->farm->barangay ?? '';
                        $variety = $prediction->farmRecord->riceVariety->name ?? 'N/A';
                        $season = $prediction->farmRecord->season ?? 'N/A';
                        $yield = $prediction->predicted_yield_tons_ha;
                        
                        $rf = \App\Models\Prediction::where('farm_record_id', $prediction->farm_record_id)
                            ->where('model_type', 'RandomForest')
                            ->first();
                        $xgb = \App\Models\Prediction::where('farm_record_id', $prediction->farm_record_id)
                            ->where('model_type', 'XGBoost')
                            ->first();
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $farmName }}</strong>
                            <br><small class="text-muted">{{ $barangay }}</small>
                        </td>
                        <td>{{ $variety }}</td>
                        <td>{{ $season }}</td>
                        <td>{{ $rf ? number_format($rf->predicted_yield_tons_ha, 2) : 'N/A' }}</td>
                        <td>{{ $xgb ? number_format($xgb->predicted_yield_tons_ha, 2) : 'N/A' }}</td>
                        <td><strong style="color: #4f46e5;">{{ number_format($yield, 2) }}</strong></td>
                        <td>
                            @php
                                $statusClass = $yield >= 4.5 ? 'high' : ($yield >= 3.5 ? 'medium' : 'low');
                                $statusText = $yield >= 4.5 ? 'High' : ($yield >= 3.5 ? 'Medium' : 'Low');
                            @endphp
                            <span class="badge-status {{ $statusClass }}"><span class="dot"></span> {{ $statusText }}</span>
                        </td>
                        <td>{{ $prediction->updated_at->diffForHumans() }}</td>
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
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 28px;"></i>
                            <p class="mt-2 mb-0">No predictions yet. Generate one!</p>
                            <button type="button" class="btn btn-sm btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#generatePredictionModal">
                                <i class="bi bi-plus-circle"></i> Generate Prediction
                            </button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 text-center text-muted small">
    <i class="bi bi-clock-history"></i> Showing latest Ensemble prediction per farm record.
</div>


<!-- ============= GENERATE PREDICTION MODAL ==================== -->
<div class="modal fade" id="generatePredictionModal" tabindex="-1" aria-labelledby="generatePredictionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--green); color: white;">
                <h5 class="modal-title" id="generatePredictionModalLabel"><i class="bi bi-cpu"></i> Generate New Prediction</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="generatePredictionModalBody">
                <div class="text-center py-4" id="generateLoading">
                    <div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div>
                    <p class="mt-2">Loading farm records...</p>
                </div>
                <div id="generatePredictionContent" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>


<!-- ============= VIEW PREDICTION MODAL ======================== -->
<div class="modal fade" id="viewPredictionModal" tabindex="-1" aria-labelledby="viewPredictionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--green); color: white;">
                <h5 class="modal-title" id="viewPredictionModalLabel"><i class="bi bi-eye"></i> Prediction Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewPredictionModalBody">
                <div class="text-center py-4" id="viewLoading">
                    <div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div>
                    <p class="mt-2">Loading prediction details...</p>
                </div>
                <div id="viewPredictionContent" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Generate Prediction Modal
    document.getElementById('generatePredictionModal').addEventListener('show.bs.modal', function() {
        document.getElementById('generateLoading').style.display = 'block';
        document.getElementById('generatePredictionContent').style.display = 'none';
        document.getElementById('generatePredictionContent').innerHTML = '';

        fetch('{{ route("admin.predictions.create") }}')
            .then(response => response.text())
            .then(html => {
                document.getElementById('generateLoading').style.display = 'none';
                document.getElementById('generatePredictionContent').style.display = 'block';
                document.getElementById('generatePredictionContent').innerHTML = html;
            })
            .catch(() => {
                document.getElementById('generateLoading').style.display = 'none';
                document.getElementById('generatePredictionContent').style.display = 'block';
                document.getElementById('generatePredictionContent').innerHTML = `
                    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Failed to load form. Please try again.</div>
                `;
            });
    });

    // View Prediction Modal
    document.getElementById('viewPredictionModal').addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        var farmRecordId = button.getAttribute('data-farm-record-id');

        document.getElementById('viewLoading').style.display = 'block';
        document.getElementById('viewPredictionContent').style.display = 'none';
        document.getElementById('viewPredictionContent').innerHTML = '';

        fetch('/admin/predictions/' + farmRecordId)
            .then(response => response.text())
            .then(html => {
                document.getElementById('viewLoading').style.display = 'none';
                document.getElementById('viewPredictionContent').style.display = 'block';
                document.getElementById('viewPredictionContent').innerHTML = html;
            })
            .catch(() => {
                document.getElementById('viewLoading').style.display = 'none';
                document.getElementById('viewPredictionContent').style.display = 'block';
                document.getElementById('viewPredictionContent').innerHTML = `
                    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Failed to load prediction details.</div>
                `;
            });
    });

    // Handle form submission inside modal
    document.addEventListener('submit', function(e) {
        if (e.target && e.target.id === 'generatePredictionForm') {
            e.preventDefault();
            var form = e.target;
            var formData = new FormData(form);
            var submitBtn = form.querySelector('button[type="submit"]');
            var originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Generating...';
            submitBtn.disabled = true;

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    var modal = bootstrap.Modal.getInstance(document.getElementById('generatePredictionModal'));
                    modal.hide();
                    location.reload();
                } else {
                    var errorDiv = document.getElementById('generatePredictionError') || document.createElement('div');
                    errorDiv.id = 'generatePredictionError';
                    errorDiv.className = 'alert alert-danger mt-3';
                    errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + data.error;
                    var existingError = document.getElementById('generatePredictionError');
                    if (existingError) existingError.remove();
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
    });
</script>
@endpush