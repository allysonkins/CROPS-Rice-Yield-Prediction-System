<div id="formErrors"></div>

<form id="farmerFarmRecordForm" action="{{ route('farmer.farm-records.store') }}" method="POST">
    @csrf
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Farm <span class="text-danger">*</span></label>
            <select name="farm_id" class="form-select" required {{ isset($prefillFarmId) && $prefillFarmId ? 'readonly' : '' }}>
                <option value="">Select a farm...</option>
                @foreach($farms as $farm)
                    <option value="{{ $farm->id }}"
                        {{ old('farm_id', $prefillFarmId ?? '') == $farm->id ? 'selected' : '' }}>
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
            <select name="season" class="form-select" required>
                <option value="">Select Season...</option>
                <option value="Dry Season" {{ old('season') == 'Dry Season' ? 'selected' : '' }}>Dry Season</option>
                <option value="Wet Season" {{ old('season') == 'Wet Season' ? 'selected' : '' }}>Wet Season</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Year <span class="text-danger">*</span></label>
            @php $curYear = now()->year; @endphp
            <select name="year" class="form-select" required>
                @for($y = $curYear + 1; $y >= $curYear - 5; $y--)
                    <option value="{{ $y }}" {{ old('year', $curYear) == $y ? 'selected' : '' }}>
                        {{ $y }}{{ $y === $curYear ? ' (Current)' : '' }}
                    </option>
                @endfor
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Fertilizer used (kg/ha) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0" max="250" name="fertilizer_kg_ha" class="form-control"
                   placeholder="e.g., 120" value="{{ old('fertilizer_kg_ha') }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Last season's yield (t/ha)</label>
            <input type="number" step="0.01" min="0" max="10" name="historical_yield_tons_ha" class="form-control"
                   placeholder="Optional" value="{{ old('historical_yield_tons_ha') }}">
            <small class="text-muted">Leave blank if you don't remember.</small>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">How did you plant?</label>
            <select name="seeding_method" class="form-select">
                <option value="">Select...</option>
                <option value="Transplanted" {{ old('seeding_method') == 'Transplanted' ? 'selected' : '' }}>Transplanted</option>
                <option value="Direct-Seeded" {{ old('seeding_method') == 'Direct-Seeded' ? 'selected' : '' }}>Direct-Seeded</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
            <select name="status" class="form-select" required>
                <option value="Vegetative" {{ old('status') == 'Vegetative' ? 'selected' : '' }}>🌱 Still growing</option>
                <option value="Harvested"  {{ old('status') == 'Harvested'  ? 'selected' : '' }}>🌾 Already harvested</option>
            </select>
        </div>

        <div class="col-md-6" id="farmerActualYieldContainer" style="display: none;">
            <label class="form-label fw-semibold">Actual Yield (t/ha)</label>
            <input type="number" step="0.01" min="0" max="10" name="actual_yield_tons_ha" class="form-control"
                   placeholder="e.g., 4.80" value="{{ old('actual_yield_tons_ha') }}">
        </div>

        <div class="col-12" id="farmerYieldPreviewContainer">
            <label class="form-label fw-semibold">Expected Yield (from variety data)</label>
            <div class="p-2 bg-light rounded" id="farmerYieldPreview" style="min-height: 40px; color: var(--gray-600);">
                Pick a variety and method to see expected yield.
            </div>
        </div>

        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-save"></i> Save Season
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </div>
</form>

<script>
(function() {
    const form = document.getElementById('farmerFarmRecordForm');
    if (!form) return;

    // Status toggle
    const statusSelect = form.querySelector('select[name="status"]');
    const actualContainer = document.getElementById('farmerActualYieldContainer');
    const actualInput = form.querySelector('input[name="actual_yield_tons_ha"]');
    function toggleActual() {
        if (statusSelect.value === 'Harvested') {
            actualContainer.style.display = 'block';
            actualInput.setAttribute('required', 'required');
        } else {
            actualContainer.style.display = 'none';
            actualInput.removeAttribute('required');
            actualInput.value = '';
        }
    }
    statusSelect.addEventListener('change', toggleActual);
    toggleActual();

    // Yield preview
    const varietySelect = form.querySelector('select[name="rice_variety_id"]');
    const methodSelect  = form.querySelector('select[name="seeding_method"]');
    const preview       = document.getElementById('farmerYieldPreview');
    function updatePreview() {
        const vid = varietySelect.value, method = methodSelect.value;
        if (!vid || !method) {
            preview.innerHTML = 'Pick a variety and method to see expected yield.';
            return;
        }
        fetch(`/admin/rice-varieties/${vid}/yield?method=${encodeURIComponent(method)}`)
            .then(r => r.json())
            .then(d => {
                preview.innerHTML = (d.avg !== null && d.max !== null)
                    ? `<span class="fw-bold text-success">${d.avg} t/ha</span> <small class="text-muted">(max: ${d.max} t/ha)</small>`
                    : '<span class="text-warning">Yield data not available.</span>';
            })
            .catch(() => preview.innerHTML = '<span class="text-danger">Error loading yield data.</span>');
    }
    varietySelect.addEventListener('change', updatePreview);
    methodSelect.addEventListener('change', updatePreview);
})();
</script>