<div id="formErrors"></div>

<form id="staffForm" action="{{ route('admin.users.update', $user->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g., Maria Santos" value="{{ old('name', $user->name) }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" placeholder="staff@cao.gov.ph" value="{{ old('email', $user->email) }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">New Password</label>
            <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
            <small class="text-muted">Min 8 characters</small>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Role</label>
            <div class="form-control bg-light" style="border: 1px solid var(--gray-200);">
                <span class="badge bg-info">Staff</span>
                <small class="text-muted ms-2">(Fixed — staff accounts only)</small>
            </div>
        </div>

        <div class="col-12">
            <div class="alert alert-light" style="border-left: 4px solid var(--gold);">
                <small>
                    <i class="bi bi-person"></i> 
                    <strong>Created:</strong> {{ $user->created_at->format('M d, Y h:i A') }}
                </small>
            </div>
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-save"></i> Update Staff
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </div>
</form>