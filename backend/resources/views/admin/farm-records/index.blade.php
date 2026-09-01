@extends('layouts.app')

@section('title', 'Farm Records')

@section('content')
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
    @if(auth()->user()->role !== 'farmer')
        <button type="button" class="btn btn-success" onclick="openFarmRecordModal()">
            <i class="bi bi-plus-circle"></i> Add Farm Record
        </button>
    @endif
    <span class="badge bg-secondary ms-2">{{ $farmRecords->count() }} Records</span>
</div>

<!-- Stats Cards -->
<div class="row g-2 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Total Records</div>
                <div class="value">{{ $farmRecords->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-clipboard-data-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Farms</div>
                <div class="value">{{ $farmRecords->pluck('farm_id')->unique()->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-geo-alt-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Varieties</div>
                <div class="value">{{ $farmRecords->pluck('rice_variety_id')->unique()->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-flower1"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Avg Fertilizer</div>
                <div class="value">{{ number_format($farmRecords->avg('fertilizer_kg_ha'), 1) }} kg/ha</div>
            </div>
            <div class="stat-icon"><i class="bi bi-droplet"></i></div>
        </div>
    </div>
</div>

<!-- Farm Records Table -->
<div class="card-custom">
    <div class="card-title"><i class="bi bi-table"></i> Farm Records</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Farm</th>
                    <th>Variety</th>
                    <th>Season</th>
                    <th>Fertilizer (kg/ha)</th>
                    <th>Historical Yield</th>
                    <th>Expected Yield (t/ha)</th>   <!-- NEW COLUMN -->
                    <th>Seeding Method</th>
                    <th>Created</th>
                    @if(auth()->user()->role !== 'farmer')
                        <th>Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($farmRecords as $record)
                    @php
                        // Compute expected yield based on the variety and seeding method
                        $expected = null;
                        if ($record->riceVariety && $record->seeding_method) {
                            $expected = $record->riceVariety->getYieldForMethod($record->seeding_method);
                        }
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $record->farm->name ?? 'N/A' }}</strong>
                            <br><small class="text-muted">{{ $record->farm->barangay ?? '' }}</small>
                        </td>
                        <td>{{ $record->riceVariety->name ?? 'N/A' }}</td>
                        <td>{{ $record->season }}</td>
                        <td>{{ number_format($record->fertilizer_kg_ha, 2) }}</td>
                        <td>{{ $record->historical_yield_tons_ha ? number_format($record->historical_yield_tons_ha, 2) : 'N/A' }}</td>
                        <td>
                            @if($expected && $expected->avg !== null && $expected->max !== null)
                                <span class="fw-bold text-success">{{ number_format($expected->avg, 2) }}</span>
                                <small class="text-muted">(max: {{ number_format($expected->max, 2) }})</small>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>{{ $record->seeding_method ?? 'N/A' }}</td>
                        <td>{{ $record->created_at ? $record->created_at->format('M d, Y') : 'N/A' }}</td>
                        @if(auth()->user()->role !== 'farmer')
                            <td>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="editFarmRecord({{ $record->id }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="{{ route('admin.farm-records.destroy', $record->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this record?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->role !== 'farmer' ? 10 : 9 }}" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 28px;"></i>
                            <p class="mt-2 mb-0">
                                @if(auth()->user()->role === 'farmer')
                                    No farm records for your farms yet.
                                @else
                                    No farm records yet.
                                @endif
                            </p>
                            @if(auth()->user()->role === 'farmer')
                                <small>
                                    <i class="bi bi-info-circle"></i> 
                                    Farm records are managed by CAO staff. 
                                    Please visit the City Agriculture Office to register your farm records.
                                </small>
                            @else
                                <button type="button" class="btn btn-sm btn-primary mt-2" onclick="openFarmRecordModal()">
                                    <i class="bi bi-plus-circle"></i> Add Farm Record
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Include the Modal (Only for Admin & Staff) -->
@if(auth()->user()->role !== 'farmer')
    @include('admin.farm-records.partials.modal')
@endif

@endsection

@push('scripts')
@if(auth()->user()->role !== 'farmer')
<script>
    // ============================================================
    // OPEN MODAL FOR ADD
    // ============================================================
    function openFarmRecordModal() {
        document.getElementById('modalTitle').textContent = 'Add Farm Record';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        fetch('{{ route("admin.farm-records.create") }}')
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalContent').style.display = 'block';
                document.getElementById('modalContent').innerHTML = html;
                
                const form = document.getElementById('farmRecordForm');
                if (form) {
                    form.addEventListener('submit', handleFormSubmit);
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

        var modal = new bootstrap.Modal(document.getElementById('farmRecordModal'));
        modal.show();
    }

    // ============================================================
    // OPEN MODAL FOR EDIT
    // ============================================================
    function editFarmRecord(id) {
        document.getElementById('modalTitle').textContent = 'Edit Farm Record';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        fetch('/admin/farm-records/' + id + '/edit')
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalContent').style.display = 'block';
                document.getElementById('modalContent').innerHTML = html;
                
                const form = document.getElementById('farmRecordForm');
                if (form) {
                    form.addEventListener('submit', handleFormSubmit);
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

        var modal = new bootstrap.Modal(document.getElementById('farmRecordModal'));
        modal.show();
    }

    // ============================================================
    // HANDLE FORM SUBMISSION
    // ============================================================
    function handleFormSubmit(e) {
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
                var modal = bootstrap.Modal.getInstance(document.getElementById('farmRecordModal'));
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
</script>
@endif
@endpush