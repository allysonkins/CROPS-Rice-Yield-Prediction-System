<div id="formErrors"></div>

<form id="farmerFarmRecordForm" action="{{ route('farmer.farm-records.update', $farmRecord->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Farm</label>
            <input type="text" class="form-control" value="{{ $farmRecord->farm->name ?? 'N/A' }} ({{ $farmRecord->farm->barangay ?? '' }})" readonly>
            <small class="text-muted">Farm can't be changed. Delete this season and add a new one instead.</small>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Rice Variety <span class="text-danger">*</span></label>
            <select name="rice_variety_id" class="form-select" required>
                @foreach($varieties as $variety)
                    <option value="{{ $variety->id }}" {{ old('rice_variety_id', $farmRecord->rice_variety_id) == $variety->id ? 'selected' : '' }}>
                        {{ $variety->name }} ({{ $variety->classification }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Season <span class="text-danger">*</span></label>
            <select name="season" class="form-select" required>
                <option value="Dry Season" {{ old('season', $farmRecord->season) == 'Dry Season' ? 'selected' : '' }}>Dry Season</option>
                <option value="Wet Season" {{ old('season', $farmRecord->season) == 'Wet Season' ? 'selected' : '' }}>Wet Season</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Year <span class="text-danger">*</span></label>
            @php
                $curYear = now()->year;
                $selectedYear = old('year', $farmRecord->year ?? $curYear);
            @endphp
            <select name="year" class="form-select" required>
                @for($y = $curYear + 1; $y >= $curYear - 10; $y--)
                    <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>
                        {{ $y }}{{ $y === $curYear ? ' (Current)' : '' }}
                    </option>
                @endfor
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Fertilizer (kg/ha) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0" max="250" name="fertilizer_kg_ha" class="form-control"
                   value="{{ old('fertilizer_kg_ha', $farmRecord->fertilizer_kg_ha) }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Last season's yield (cavan/ha)</label>
            @php
                $historicalCavan = old('historical_yield_tons_ha', $farmRecord->historical_yield_tons_ha);
                $historicalCavan = $historicalCavan ? $historicalCavan * 20 : '';
            @endphp
            <input type="number" step="0.1" min="0" max="200"
                   id="farmerHistoricalCavan" class="form-control"
                   value="{{ $historicalCavan }}">
            <input type="hidden" name="historical_yield_tons_ha" id="farmerHistoricalTons"
                   value="{{ old('historical_yield_tons_ha', $farmRecord->historical_yield_tons_ha) }}">
            <small class="text-muted">1 cavan = 50 kg · 1 ton = 20 cavan</small>
            <div id="farmerHistoricalPreview" style="font-size: 11px; color: var(--slate-500); margin-top: 4px;"></div>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">How did you plant?</label>
            <select name="seeding_method" class="form-select">
                <option value="">Select...</option>
                <option value="Transplanted" {{ old('seeding_method', $farmRecord->seeding_method) == 'Transplanted' ? 'selected' : '' }}>Transplanted</option>
                <option value="Direct-Seeded" {{ old('seeding_method', $farmRecord->seeding_method) == 'Direct-Seeded' ? 'selected' : '' }}>Direct-Seeded</option>
            </select>
        </div>

        @if($farmRecord->status === 'Vegetative')
            <div class="col-12">
                <div class="alert alert-info d-flex align-items-center mb-0" style="border-radius: 10px; padding: 10px 16px;">
                    <i class="bi bi-info-circle-fill me-2" style="font-size: 18px;"></i>
                    <div style="font-size: 13px;">
                        This season is <strong>still growing</strong>. To record the harvest, use the 🌾 <strong>Harvest</strong> button in the season list.
                    </div>
                </div>
            </div>
        @else
            @php
                $actualCavan = $farmRecord->actual_yield_tons_ha ? $farmRecord->actual_yield_tons_ha * 20 : null;
            @endphp
            <div class="col-12">
                <div class="alert alert-secondary d-flex align-items-center mb-0" style="border-radius: 10px; padding: 10px 16px;">
                    <i class="bi bi-basket-fill me-2" style="font-size: 18px;"></i>
                    <div style="font-size: 13px;">
                        This season is already <strong>harvested</strong>@if($actualCavan !== null) with an actual yield of <strong>{{ number_format($actualCavan, 0) }} cavan/ha</strong> <span class="text-muted">({{ number_format($farmRecord->actual_yield_tons_ha, 2) }} t/ha)</span>@endif.
                    </div>
                </div>
            </div>
        @endif

        <div class="col-12">
            <label class="form-label fw-semibold">Expected Yield (from variety data)</label>
            <div class="p-2 bg-light rounded" id="farmerYieldPreview" style="min-height: 40px; color: var(--gray-600);">
                @php
                    $method = old('seeding_method', $farmRecord->seeding_method);
                    $yield  = $method && $farmRecord->riceVariety ? $farmRecord->riceVariety->getYieldForMethod($method) : null;
                @endphp
                @if($yield && $yield->avg !== null)
                    <span class="fw-bold text-success">{{ number_format($yield->avg * 20, 0) }} cavan/ha</span>
                    <small class="text-muted">(max: {{ number_format($yield->max * 20, 0) }} cavan/ha)</small>
                    <br>
                    <small class="text-muted">({{ $yield->avg }} t/ha avg · {{ $yield->max }} t/ha max)</small>
                @else
                    Pick a variety and method to see expected yield.
                @endif
            </div>
        </div>

        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-save"></i> Update Season
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </div>
</form>

<script>
(function() {
    const form = document.getElementById('farmerFarmRecordForm');
    if (!form) return;

    const CAVAN_TO_TONS = 0.05;

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
        update();
    }

    bindConversion('farmerHistoricalCavan', 'farmerHistoricalTons', 'farmerHistoricalPreview');

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