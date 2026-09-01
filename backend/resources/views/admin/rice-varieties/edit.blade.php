<div id="formErrors"></div>

<form id="riceVarietyForm" action="{{ route('admin.rice-varieties.update', $variety->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <!-- Variety Name -->
        <div class="col-md-6">
            <label class="form-label fw-semibold">Variety Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g., NSIC Rc 222" value="{{ old('name', $variety->name) }}" required>
        </div>

        <!-- Classification -->
        <div class="col-md-6">
            <label class="form-label fw-semibold">Classification <span class="text-danger">*</span></label>
            <select name="classification" class="form-select" required>
                <option value="Hybrid" {{ old('classification', $variety->classification) == 'Hybrid' ? 'selected' : '' }}>Hybrid</option>
                <option value="Inbred" {{ old('classification', $variety->classification) == 'Inbred' ? 'selected' : '' }}>Inbred</option>
            </select>
        </div>

        <!-- Description -->
        <div class="col-12">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Describe the variety...">{{ old('description', $variety->description) }}</textarea>
        </div>

        <hr class="my-2">
        <h6 class="fw-bold text-success"><i class="bi bi-grid-3x3-gap-fill"></i> Seeding Method Specifics</h6>

        @php
            // Check if all direct fields equal transplanted fields (so we can auto‑check the toggle)
            $transGrowth = $variety->growth_period_transplanted;
            $directGrowth = $variety->growth_period_direct;
            $transAvg = $variety->avg_yield_transplanted;
            $directAvg = $variety->avg_yield_direct;
            $transMax = $variety->max_yield_transplanted;
            $directMax = $variety->max_yield_direct;
            $same = ($transGrowth == $directGrowth && $transAvg == $directAvg && $transMax == $directMax);
        @endphp
        <div class="col-12">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="sameValuesCheck" {{ $same ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="sameValuesCheck">
                    Direct Seeded has the same values as Transplanted
                </label>
            </div>
            <small class="text-muted">Uncheck to set different values for Direct Seeded.</small>
        </div>

        <!-- ===== TRANSPLANTED ===== -->
        <div class="col-12">
            <div class="p-3 border rounded-3 bg-light">
                <h6 class="fw-bold text-primary"><i class="bi bi-tree"></i> Transplanted</h6>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Growth Period (days) <span class="text-danger">*</span></label>
                        <input type="number" name="growth_period_transplanted" class="form-control" placeholder="e.g., 115" value="{{ old('growth_period_transplanted', $variety->growth_period_transplanted) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Average Yield (t/ha)</label>
                        <input type="number" step="0.01" name="avg_yield_transplanted" class="form-control" placeholder="e.g., 4.5" value="{{ old('avg_yield_transplanted', $variety->avg_yield_transplanted) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Max Yield (t/ha)</label>
                        <input type="number" step="0.01" name="max_yield_transplanted" class="form-control" placeholder="e.g., 5.8" value="{{ old('max_yield_transplanted', $variety->max_yield_transplanted) }}">
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== DIRECT SEEDED ===== -->
        <div class="col-12">
            <div class="p-3 border rounded-3 bg-light" id="directSeededContainer">
                <h6 class="fw-bold text-warning"><i class="bi bi-seeds"></i> Direct Seeded</h6>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Growth Period (days)</label>
                        <input type="number" name="growth_period_direct" class="form-control" placeholder="e.g., 115" value="{{ old('growth_period_direct', $variety->growth_period_direct) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Average Yield (t/ha)</label>
                        <input type="number" step="0.01" name="avg_yield_direct" class="form-control" placeholder="e.g., 4.5" value="{{ old('avg_yield_direct', $variety->avg_yield_direct) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Max Yield (t/ha)</label>
                        <input type="number" step="0.01" name="max_yield_direct" class="form-control" placeholder="e.g., 5.8" value="{{ old('max_yield_direct', $variety->max_yield_direct) }}">
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== OTHER FIELDS ===== -->
        <div class="col-12">
            <label class="form-label fw-semibold">Milling & Grain Quality</label>
            <textarea name="grain_quality" class="form-control" rows="2" placeholder="Describe grain quality...">{{ old('grain_quality', $variety->grain_quality) }}</textarea>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Disease Susceptibility</label>
            <input type="text" name="disease_susceptibility" class="form-control" placeholder="e.g., Bacterial Blight" value="{{ old('disease_susceptibility', $variety->disease_susceptibility) }}">
        </div>

        <!-- Resilience -->
        @php
            $resilience = $variety->resilience;
            if (is_string($resilience)) {
                $resilience = json_decode($resilience, true) ?? [];
            }
            if (!is_array($resilience)) {
                $resilience = [];
            }
        @endphp
        <div class="col-12">
            <label class="form-label fw-semibold">Resilience & Hardiness</label>
            <div class="d-flex flex-wrap gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="resilience[]" value="Bacterial Blight" id="res_bb" {{ in_array('Bacterial Blight', $resilience) ? 'checked' : '' }}>
                    <label class="form-check-label" for="res_bb">Bacterial Blight</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="resilience[]" value="Tungro" id="res_tungro" {{ in_array('Tungro', $resilience) ? 'checked' : '' }}>
                    <label class="form-check-label" for="res_tungro">Tungro</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="resilience[]" value="Blast" id="res_blast" {{ in_array('Blast', $resilience) ? 'checked' : '' }}>
                    <label class="form-check-label" for="res_blast">Blast</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="resilience[]" value="Sheath Blight" id="res_sb" {{ in_array('Sheath Blight', $resilience) ? 'checked' : '' }}>
                    <label class="form-check-label" for="res_sb">Sheath Blight</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="resilience[]" value="Drought Tolerant" id="res_drought" {{ in_array('Drought Tolerant', $resilience) ? 'checked' : '' }}>
                    <label class="form-check-label" for="res_drought">Drought Tolerant</label>
                </div>
            </div>
            <small class="text-muted">Select all that apply</small>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Optimal Temperature Range</label>
            <div class="row g-2">
                <div class="col-6">
                    <input type="number" step="0.1" name="optimal_temp_min" class="form-control" placeholder="Min °C" value="{{ old('optimal_temp_min', $variety->optimal_temp_min) }}">
                </div>
                <div class="col-6">
                    <input type="number" step="0.1" name="optimal_temp_max" class="form-control" placeholder="Max °C" value="{{ old('optimal_temp_max', $variety->optimal_temp_max) }}">
                </div>
            </div>
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-save"></i> Update Variety
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </div>
</form>

<script>
    // Immediately execute when this script tag is parsed
    (function() {
        const sameCheck = document.getElementById('sameValuesCheck');
        if (!sameCheck) return;

        const transFields = document.querySelectorAll('[name^="growth_period_transplanted"], [name^="avg_yield_transplanted"], [name^="max_yield_transplanted"]');
        const directFields = document.querySelectorAll('[name^="growth_period_direct"], [name^="avg_yield_direct"], [name^="max_yield_direct"]');

        function syncFields() {
            if (sameCheck.checked) {
                directFields.forEach((field, index) => {
                    const transField = transFields[index];
                    if (transField) {
                        field.value = transField.value;
                        field.disabled = true;
                        field.style.background = '#e9ecef';
                    }
                });
            } else {
                directFields.forEach(field => {
                    field.disabled = false;
                    field.style.background = '';
                });
            }
        }

        // Initial sync
        syncFields();

        // On change of transplant fields, copy if checked
        transFields.forEach((field, index) => {
            field.addEventListener('input', function() {
                if (sameCheck.checked) {
                    const directField = directFields[index];
                    if (directField) {
                        directField.value = this.value;
                    }
                }
            });
        });

        sameCheck.addEventListener('change', function() {
            syncFields();
            if (this.checked) {
                transFields.forEach((field, index) => {
                    const directField = directFields[index];
                    if (directField) {
                        directField.value = field.value;
                    }
                });
            }
        });
    })();
</script>