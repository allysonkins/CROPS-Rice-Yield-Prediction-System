<!-- ============================================================ -->
<!-- CREATE FARM RECORD (loaded into modal)                        -->
<!-- ============================================================ -->
<div id="formErrors"></div>

<form id="farmRecordForm" action="{{ route('admin.farm-records.store') }}" method="POST">
    @csrf
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Farm <span class="text-danger">*</span></label>
            <select name="farm_id" class="form-select" required>
                <option value="">Select a farm...</option>
                @foreach($farms as $farm)
                    <option value="{{ $farm->id }}" {{ old('farm_id') == $farm->id ? 'selected' : '' }}>
                        {{ $farm->name }} ({{ $farm->barangay }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Rice Variety <span class="text-danger">*</span></label>
            <select name="rice_variety_id" class="form-select" required>
                <option value="">Select a variety...</option>
                @foreach($varieties as $variety)
                    <option value="{{ $variety->id }}" {{ old('rice_variety_id') == $variety->id ? 'selected' : '' }}>
                        {{ $variety->name }} ({{ $variety->classification }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Season <span class="text-danger">*</span></label>
            <input type="text" name="season" class="form-control" placeholder="e.g., Wet 2026" value="{{ old('season') }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Fertilizer (kg/ha) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="fertilizer_kg_ha" class="form-control" placeholder="120" value="{{ old('fertilizer_kg_ha') }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Historical Yield (t/ha)</label>
            <input type="number" step="0.01" name="historical_yield_tons_ha" class="form-control" placeholder="4.2" value="{{ old('historical_yield_tons_ha') }}">
            <small class="text-muted">Previous season's yield (if available)</small>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Seeding Method</label>
            <select name="seeding_method" class="form-select">
                <option value="">Select...</option>
                <option value="Transplanted" {{ old('seeding_method') == 'Transplanted' ? 'selected' : '' }}>Transplanted</option>
                <option value="Direct Seeded" {{ old('seeding_method') == 'Direct Seeded' ? 'selected' : '' }}>Direct Seeded</option>
            </select>
        </div>

        <!-- Yield Preview -->
        <div class="col-12" id="yieldPreviewContainer">
            <label class="form-label fw-semibold">Expected Yield (Variety)</label>
            <div class="p-2 bg-light rounded" id="yieldPreview" style="min-height: 40px; color: var(--gray-600);">
                Select a variety and seeding method to see expected yield.
            </div>
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-save"></i> Save Record
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </div>
</form>

<!-- JavaScript for dynamic yield preview -->
<script>
    (function() {
        const varietySelect = document.querySelector('select[name="rice_variety_id"]');
        const methodSelect = document.querySelector('select[name="seeding_method"]');
        const preview = document.getElementById('yieldPreview');

        if (!varietySelect || !methodSelect || !preview) return;

        function updateYieldPreview() {
            const varietyId = varietySelect.value;
            const method = methodSelect.value;

            if (!varietyId || !method) {
                preview.innerHTML = 'Select a variety and seeding method to see expected yield.';
                return;
            }

            fetch(`/admin/rice-varieties/${varietyId}/yield?method=${encodeURIComponent(method)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.avg !== null && data.max !== null) {
                        preview.innerHTML = `
                            <span class="fw-bold text-success">${data.avg} t/ha</span>
                            (max: ${data.max} t/ha)
                        `;
                    } else {
                        preview.innerHTML = '<span class="text-warning">Yield data not available for this method.</span>';
                    }
                })
                .catch(() => {
                    preview.innerHTML = '<span class="text-danger">Error loading yield data.</span>';
                });
        }

        varietySelect.addEventListener('change', updateYieldPreview);
        methodSelect.addEventListener('change', updateYieldPreview);
    })();
</script>