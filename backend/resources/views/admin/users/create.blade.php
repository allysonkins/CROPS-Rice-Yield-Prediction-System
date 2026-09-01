<div id="formErrors"></div>

<form id="staffForm" action="{{ route('admin.users.store') }}" method="POST">
    @csrf
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g., Maria Santos" value="{{ old('name') }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" placeholder="staff@cao.gov.ph" value="{{ old('email') }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" placeholder="Min 8 characters" required>
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
                    <i class="bi bi-info-circle"></i> 
                    <strong>Note:</strong> Staff accounts can manage farms, farm records, and advisories. 
                    They cannot manage user accounts.
                </small>
            </div>
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-save"></i> Create Staff
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </div>
</form>