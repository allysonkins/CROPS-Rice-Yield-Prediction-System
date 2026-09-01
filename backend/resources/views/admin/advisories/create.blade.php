<div id="formErrors"></div>

<form id="advisoryForm" action="{{ route('admin.advisories.store') }}" method="POST">
    @csrf
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" placeholder="e.g., Rice Planting Tips for Wet Season" value="{{ old('title') }}" required>
        </div>

        <div class="col-12">
            <label class="form-label fw-semibold">Content <span class="text-danger">*</span></label>
            <textarea name="content" class="form-control" rows="4" placeholder="Write your advisory content here..." required>{{ old('content') }}</textarea>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Target Audience <span class="text-danger">*</span></label>
            <select name="target_audience" class="form-select" required>
                <option value="all" {{ old('target_audience') == 'all' ? 'selected' : '' }}>All</option>
                <option value="farmers" {{ old('target_audience') == 'farmers' ? 'selected' : '' }}>Farmers Only</option>
                <option value="staff" {{ old('target_audience') == 'staff' ? 'selected' : '' }}>Staff Only</option>
                <option value="admin" {{ old('target_audience') == 'admin' ? 'selected' : '' }}>Admin Only</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Expiry Date</label>
            <input type="date" name="expiry_date" class="form-control" value="{{ old('expiry_date') }}">
            <small class="text-muted">Leave blank for no expiry</small>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Status</label>
            <select name="is_active" class="form-select">
                <option value="1" {{ old('is_active') !== '0' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-save"></i> Publish Advisory
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </div>
</form>