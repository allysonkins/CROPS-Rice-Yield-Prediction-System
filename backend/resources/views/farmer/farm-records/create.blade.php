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
            <label class="form-label fw-semibold">Last season's yield (cavan/ha)</label>
            <input type="number" step="0.1" min="0" max="200"
                   id="farmerHistoricalCavan" class="form-control"
                   placeholder="e.g., 90"
                   value="{{ old('historical_yield_tons_ha') ? old('historical_yield_tons_ha') * 20 : '' }}">
            <input type="hidden" name="historical_yield_tons_ha" id="farmerHistoricalTons"
                   value="{{ old('historical_yield_tons_ha') }}">
            <small class="text-muted">1 cavan = 50 kg · 1 ton = 20 cavan. Leave blank if unsure.</small>
            <div id="farmerHistoricalPreview" style="font-size: 11px; color: var(--slate-500); margin-top: 4px;"></div>
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
            <label class="form-label fw-semibold">Actual Yield (cavan/ha)</label>
            <input type="number" step="0.1" min="0" max="200"
                   id="farmerActualCavan" class="form-control"
                   placeholder="e.g., 90"
                   value="{{ old('actual_yield_tons_ha') ? old('actual_yield_tons_ha') * 20 : '' }}">
            <input type="hidden" name="actual_yield_tons_ha" id="farmerActualTons"
                   value="{{ old('actual_yield_tons_ha') }}">
            <small class="text-muted">Ilang cavan ang inani mo per hectare?</small>
            <div id="farmerActualPreview" style="font-size: 11px; color: var(--slate-500); margin-top: 4px;"></div>
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

    const CAVAN_TO_TONS = 0.05; // 1 cavan = 50 kg = 0.05 tons

    // ── Status toggle (actual yield visible only when harvested) ──
    const statusSelect = form.querySelector('select[name="status"]');
    const actualContainer = document.getElementById('farmerActualYieldContainer');
    function toggleActual() {
        if (statusSelect.value === 'Harvested') {
            actualContainer.style.display = 'block';
        } else {
            actualContainer.style.display = 'none';
            const cavanInput = document.getElementById('farmerActualCavan');
            const tonsInput  = document.getElementById('farmerActualTons');
            if (cavanInput) cavanInput.value = '';
            if (tonsInput)  tonsInput.value  = '';
            const prev = document.getElementById('farmerActualPreview');
            if (prev) prev.innerHTML = '';
        }
    }
    statusSelect.addEventListener('change', toggleActual);
    toggleActual();

    // ── Cavan → Tons live conversion ──
    function bindConversion(cavanId, tonsId, previewId) {
        const cavanInput = document.getElementById(cavanId);
        const tonsInput  = document.getElementById(tonsId);
        const preview    = document.getElementById(previewId);
        if (!cavanInput || !tonsInput) return;

        const update = () => {
            const cavan = parseFloat(cavanInput.value);
            if (!isNaN(cavan) && cavan >= 0) {
                const tons = cavan * CAVAN_TO_TONS;
                tonsInput.value = tons.toFixed(4);
                if (preview) {
                    preview.innerHTML = `<i class="bi bi-arrow-right-circle"></i> Equivalent to <strong>${tons.toFixed(2)} t/ha</strong>`;
                }
            } else {
                tonsInput.value = '';
                if (preview) preview.innerHTML = '';
            }
        };
        cavanInput.addEventListener('input', update);
        update(); // initial
    }

    bindConversion('farmerHistoricalCavan', 'farmerHistoricalTons', 'farmerHistoricalPreview');
    bindConversion('farmerActualCavan',     'farmerActualTons',     'farmerActualPreview');

    // ── Yield preview (fetches t/ha from API, displays cavan/ha) ──
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
                if (d.avg !== null && d.max !== null) {
                    const avgCavan = (d.avg * 20).toFixed(0);
                    const maxCavan = (d.max * 20).toFixed(0);
                    preview.innerHTML = `
                        <span class="fw-bold text-success">${avgCavan} cavan/ha</span>
                        <small class="text-muted">(max: ${maxCavan} cavan/ha)</small>
                        <br>
                        <small class="text-muted">(${d.avg} t/ha avg · ${d.max} t/ha max)</small>
                    `;
                } else {
                    preview.innerHTML = '<span class="text-warning">Yield data not available.</span>';
                }
            })
            .catch(() => preview.innerHTML = '<span class="text-danger">Error loading yield data.</span>');
    }
    varietySelect.addEventListener('change', updatePreview);
    methodSelect.addEventListener('change', updatePreview);
})();
</script>