@extends('layouts.app')

@section('title', 'Farmers List')

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

<!-- ============================================================ -->
<!-- HEADER: Button + Badge only (Title is in layout) -->
<!-- ============================================================ -->
<div class="d-flex justify-content-end align-items-center mb-4">
    <div class="d-flex align-items-center gap-2">
        @if(auth()->user()->role === 'admin')
            <button type="button" class="btn btn-success" onclick="openFarmerModal()">
                <i class="bi bi-plus-circle"></i> Add Farmer
            </button>
        @endif
        <span class="badge bg-secondary">{{ $farmers->count() }} Farmers</span>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-2 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Total Farmers</div>
                <div class="value">{{ $farmers->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Verified</div>
                <div class="value">{{ $farmers->whereNotNull('email_verified_at')->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-patch-check-fill" style="color: var(--green);"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Unverified</div>
                <div class="value">{{ $farmers->whereNull('email_verified_at')->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-exclamation-circle-fill" style="color: var(--gold);"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">With Farms</div>
                <div class="value">{{ $farmers->filter(fn($f) => $f->farms->count() > 0)->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-geo-alt-fill"></i></div>
        </div>
    </div>
</div>

<!-- Farmers Table -->
<div class="card-custom">
    <div class="card-title"><i class="bi bi-table"></i> Farmers</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Barangay</th>
                    <th>Farms</th>
                    <th>Verified</th>
                    <th>Joined</th>
                    @if(auth()->user()->role === 'admin')
                        <th>Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($farmers as $farmer)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $farmer->name }}</strong></td>
                        <td>{{ $farmer->email }}</td>
                        <td>{{ $farmer->barangay ?? 'N/A' }}</td>
                        <td>{{ $farmer->farms->count() }}</td>
                        <td>
                            @if($farmer->email_verified_at)
                                <span class="badge bg-success">
                                    <i class="bi bi-patch-check-fill"></i> Verified
                                </span>
                                <br><small class="text-muted">{{ $farmer->email_verified_at->format('M d, Y') }}</small>
                            @else
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-hourglass-split"></i> Pending
                                </span>
                            @endif
                        </td>
                        <td>{{ $farmer->created_at->format('M d, Y') }}</td>
                        @if(auth()->user()->role === 'admin')
                            <td>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="editFarmer({{ $farmer->id }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @if($farmer->id !== auth()->id())
                                    <form action="{{ route('admin.farmers.destroy', $farmer->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this farmer?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @else
                                    <button class="btn btn-sm btn-secondary" disabled title="You cannot delete yourself">
                                        <i class="bi bi-lock"></i>
                                    </button>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->role === 'admin' ? 8 : 7 }}" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 28px;"></i>
                            <p class="mt-2 mb-0">No farmers registered yet.</p>
                            @if(auth()->user()->role === 'admin')
                                <button type="button" class="btn btn-sm btn-primary mt-2" onclick="openFarmerModal()">
                                    <i class="bi bi-plus-circle"></i> Add Farmer
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Include the Farmer Modal -->
@if(auth()->user()->role === 'admin')
    @include('admin.farmers.partials.modal')
@endif

@endsection

@if(auth()->user()->role === 'admin')
@push('scripts')
<script>
    // ============================================================
    // OPEN MODAL FOR ADD
    // ============================================================
    function openFarmerModal() {
        document.getElementById('modalTitle').textContent = 'Add Farmer';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        fetch('{{ route("admin.farmers.create") }}')
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalContent').style.display = 'block';
                document.getElementById('modalContent').innerHTML = html;

                const form = document.getElementById('farmerForm');
                if (form) {
                    form.addEventListener('submit', handleFarmerFormSubmit);
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

        var modal = new bootstrap.Modal(document.getElementById('farmerModal'));
        modal.show();
    }

    // ============================================================
    // OPEN MODAL FOR EDIT
    // ============================================================
    function editFarmer(id) {
        document.getElementById('modalTitle').textContent = 'Edit Farmer';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        fetch('/admin/farmers/' + id + '/edit')
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalContent').style.display = 'block';
                document.getElementById('modalContent').innerHTML = html;

                const form = document.getElementById('farmerForm');
                if (form) {
                    form.addEventListener('submit', handleFarmerFormSubmit);
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

        var modal = new bootstrap.Modal(document.getElementById('farmerModal'));
        modal.show();
    }

    // ============================================================
    // HANDLE FORM SUBMISSION
    // ============================================================
    function handleFarmerFormSubmit(e) {
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
                var modal = bootstrap.Modal.getInstance(document.getElementById('farmerModal'));
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
@endpush
@endif