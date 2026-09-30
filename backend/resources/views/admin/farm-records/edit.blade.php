<div id="formErrors"></div>

<form id="farmRecordForm" action="{{ route('admin.farm-records.update', $farmRecord->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Farm <span class="text-danger">*</span></label>
            <select name="farm_id" class="form-select" required>
                <option value="">Select a farm...</option>
                @foreach($farms as $farm)
                    <option value="{{ $farm->id }}" {{ old('farm_id', $farmRecord->farm_id) == $farm->id ? 'selected' : '' }}>
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
                    <option value="{{ $variety->id }}" {{ old('rice_variety_id', $farmRecord->rice_variety_id) == $variety->id ? 'selected' : '' }}>
                        {{ $variety->name }} ({{ $variety->classification }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Season <span class="text-danger">*</span></label>
            <select name="season" class="form-select" required>
                <option value="">Select Season...</option>
                <option value="Dry Season" {{ old('season', $farmRecord->season) == 'Dry Season' ? 'selected' : '' }}>Dry Season</option>
                <option value="Wet Season" {{ old('season', $farmRecord->season) == 'Wet Season' ? 'selected' : '' }}>Wet Season</option>
            </select>
            <small class="text-muted">Predictions only available for Dry and Wet seasons.</small>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Season Year <span class="text-danger">*</span></label>
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
            <small class="text-muted">Which year does this season belong to?</small>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Fertilizer (kg/ha) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0" max="250" name="fertilizer_kg_ha" class="form-control" placeholder="120" value="{{ old('fertilizer_kg_ha', $farmRecord->fertilizer_kg_ha) }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Previous Yield (t/ha)</label>
            <input type="number" step="0.01" min="0" max="10" name="historical_yield_tons_ha" class="form-control" placeholder="4.2" value="{{ old('historical_yield_tons_ha', $farmRecord->historical_yield_tons_ha) }}">
            <small class="text-muted">Previous season's yield (if available)</small>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Seeding Method</label>
            <select name="seeding_method" class="form-select">
                <option value="">Select...</option>
                <option value="Transplanted" {{ old('seeding_method', $farmRecord->seeding_method) == 'Transplanted' ? 'selected' : '' }}>Transplanted</option>
                <option value="Direct-Seeded" {{ old('seeding_method', $farmRecord->seeding_method) == 'Direct-Seeded' ? 'selected' : '' }}>Direct-Seeded</option>
            </select>
        </div>

        {{-- Status is fixed on this form:
             - Vegetative: use the 🌾 "Mark as Harvested" button in the records list to transition.
             - Harvested: already recorded — banner reflects that. --}}
        @if($farmRecord->status === 'Vegetative')
            <div class="col-12">
                <div class="alert alert-info d-flex align-items-center mb-0" style="border-radius: 10px; padding: 10px 16px;">
                    <i class="bi bi-info-circle-fill me-2" style="font-size: 18px;"></i>
                    <div style="font-size: 13px;">
                        This record is <strong>Vegetative</strong>. To record the harvest, use the
                        <i class="bi bi-basket-fill"></i> <strong>Harvest</strong> button in the records list.
                    </div>
                </div>
            </div>
        @elseif($farmRecord->status === 'Harvested')
            <div class="col-12">
                <div class="alert alert-secondary d-flex align-items-center mb-0" style="border-radius: 10px; padding: 10px 16px;">
                    <i class="bi bi-basket-fill me-2" style="font-size: 18px;"></i>
                    <div style="font-size: 13px;">
                        This record is already <strong>Harvested</strong>
                        @if($farmRecord->actual_yield_tons_ha)
                            with an actual yield of <strong>{{ number_format($farmRecord->actual_yield_tons_ha, 2) }} t/ha</strong>.
                        @else
                            .
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="col-12" id="yieldPreviewContainer">
            <label class="form-label fw-semibold">Expected Yield (Variety)</label>
            <div class="p-2 bg-light rounded" id="yieldPreview" style="min-height: 40px; color: var(--gray-600);">
                @php
                    $method = old('seeding_method', $farmRecord->seeding_method);
                    $yield = $method && $farmRecord->riceVariety ? $farmRecord->riceVariety->getYieldForMethod($method) : null;
                @endphp
                @if($yield && $yield->avg !== null)
                    <span class="fw-bold text-success">{{ $yield->avg }} t/ha</span> (max: {{ $yield->max }} t/ha)
                @else
                    Select a variety and seeding method to see expected yield.
                @endif
            </div>
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-save"></i> Update Record
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </div>
</form>

<script>
    (function() {
        const varietySelect = document.querySelector('select[name="rice_variety_id"]');
        const methodSelect = document.querySelector('select[name="seeding_method"]');
        const preview = document.getElementById('yieldPreview');

        if (varietySelect && methodSelect && preview) {
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
        }
    })();
</script>