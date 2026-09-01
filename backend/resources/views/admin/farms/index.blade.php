@extends('layouts.app')

@section('title', 'Manage Farms')

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

<!-- Include the Modal (only for Admin) -->
@if(auth()->user()->role === 'admin')
    @include('admin.farms.partials.modal')
@endif

@endsection

@push('scripts')
<script>
    // Only define functions if user is admin
    @if(auth()->user()->role === 'admin')
    function openFarmModal() {
        document.getElementById('modalTitle').textContent = 'Add Farm';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        fetch('{{ route("admin.farms.create") }}')
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalContent').style.display = 'block';
                document.getElementById('modalContent').innerHTML = html;
                
                const form = document.getElementById('farmForm');
                if (form) {
                    form.addEventListener('submit', handleFarmFormSubmit);
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

        var modal = new bootstrap.Modal(document.getElementById('farmModal'));
        modal.show();
    }

    function editFarm(id) {
        document.getElementById('modalTitle').textContent = 'Edit Farm';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        fetch('/admin/farms/' + id + '/edit')
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalContent').style.display = 'block';
                document.getElementById('modalContent').innerHTML = html;
                
                const form = document.getElementById('farmForm');
                if (form) {
                    form.addEventListener('submit', handleFarmFormSubmit);
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

        var modal = new bootstrap.Modal(document.getElementById('farmModal'));
        modal.show();
    }

    function handleFarmFormSubmit(e) {
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
                var modal = bootstrap.Modal.getInstance(document.getElementById('farmModal'));
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
    @endif
</script>
@endpush