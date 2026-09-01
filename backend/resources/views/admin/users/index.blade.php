@extends('layouts.app')

@section('title', 'Staff Accounts')

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
<!-- HEADER: Button + Badge only -->
<!-- ============================================================ -->
<div class="d-flex justify-content-end align-items-center mb-4">
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-success" onclick="openStaffModal()">
            <i class="bi bi-plus-circle"></i> Add Staff
        </button>
        <span class="badge bg-secondary">{{ $users->count() }} Staff</span>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-2 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Total Staff</div>
                <div class="value">{{ $users->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Active Staff</div>
                <div class="value">{{ $users->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-check-circle-fill" style="color: var(--green);"></i></div>
        </div>
    </div>
</div>

<!-- Staff Table -->
<div class="card-custom">
    <div class="card-title"><i class="bi bi-table"></i> Staff Accounts</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $user->name }}</strong></td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge bg-info">{{ ucfirst($user->role) }}</span>
                        </td>
                        <td>{{ $user->created_at->format('M d, Y') }}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="editStaff({{ $user->id }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @if($user->id !== auth()->id())
                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this staff account?')">
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
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 28px;"></i>
                            <p class="mt-2 mb-0">No staff accounts yet.</p>
                            <button type="button" class="btn btn-sm btn-primary mt-2" onclick="openStaffModal()">
                                <i class="bi bi-plus-circle"></i> Add Staff
                            </button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Include the Modal -->
@include('admin.users.partials.modal')

@endsection

@push('scripts')
<script>
    // ============================================================
    // OPEN MODAL FOR ADD
    // ============================================================
    function openStaffModal() {
        document.getElementById('modalTitle').textContent = 'Add Staff Account';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        fetch('{{ route("admin.users.create") }}')
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalContent').style.display = 'block';
                document.getElementById('modalContent').innerHTML = html;
                
                const form = document.getElementById('staffForm');
                if (form) {
                    form.addEventListener('submit', handleStaffFormSubmit);
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

        var modal = new bootstrap.Modal(document.getElementById('staffModal'));
        modal.show();
    }

    // ============================================================
    // OPEN MODAL FOR EDIT
    // ============================================================
    function editStaff(id) {
        document.getElementById('modalTitle').textContent = 'Edit Staff Account';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        fetch('/admin/users/' + id + '/edit')
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalContent').style.display = 'block';
                document.getElementById('modalContent').innerHTML = html;
                
                const form = document.getElementById('staffForm');
                if (form) {
                    form.addEventListener('submit', handleStaffFormSubmit);
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

        var modal = new bootstrap.Modal(document.getElementById('staffModal'));
        modal.show();
    }

    // ============================================================
    // HANDLE FORM SUBMISSION
    // ============================================================
    function handleStaffFormSubmit(e) {
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
                var modal = bootstrap.Modal.getInstance(document.getElementById('staffModal'));
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