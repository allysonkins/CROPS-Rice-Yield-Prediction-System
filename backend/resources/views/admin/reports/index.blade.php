@extends('layouts.app')

@section('title', 'Reports')

@section('content')

{{-- ═══════════ HEADER ═══════════ --}}
<div class="row g-2 mb-3">
    <div class="col-12">
        <div class="card-custom" style="border-left: 4px solid var(--gold); padding: 16px 22px;">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div>
                    <div style="font-size: 15px; font-weight: 700; color: var(--green);">Report Builder</div>
                    <div class="text-muted" style="font-size: 12px;">
                        Choose what to include and which records to filter — export as PDF or Excel.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="reportBuilderForm" method="GET" action="{{ route('admin.reports.generate-pdf') }}">

{{-- ═══════════ SECTION 1 — DATA FILTERS ═══════════ --}}
<div class="row g-2 mb-3">
    <div class="col-12">
        <div class="card-custom" style="padding: 18px 20px;">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-funnel-fill" style="color: var(--green); font-size: 16px;"></i>
                    <span style="font-size: 14px; font-weight: 700;">Filter Records</span>
                </div>
                <span class="text-muted" style="font-size: 11px;">Leave blank to include everything</span>
            </div>

            <div class="row g-2">
                {{-- Year range --}}
                <div class="col-6 col-md-3">
                    <label class="form-label" style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--slate-500);">Year from</label>
                    <select name="year_from" class="form-select form-select-sm">
                        <option value="">—</option>
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ request('year_from') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--slate-500);">Year to</label>
                    <select name="year_to" class="form-select form-select-sm">
                        <option value="">—</option>
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ request('year_to') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Season --}}
                <div class="col-6 col-md-3">
                    <label class="form-label" style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--slate-500);">Season</label>
                    <select name="season" class="form-select form-select-sm">
                        <option value="">All Seasons</option>
                        <option value="Dry" {{ request('season') === 'Dry' ? 'selected' : '' }}>Dry</option>
                        <option value="Wet" {{ request('season') === 'Wet' ? 'selected' : '' }}>Wet</option>
                    </select>
                </div>

                {{-- Status --}}
                <div class="col-6 col-md-3">
                    <label class="form-label" style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--slate-500);">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Any Status</option>
                        <option value="Vegetative" {{ request('status') === 'Vegetative' ? 'selected' : '' }}>Growing</option>
                        <option value="Harvested"  {{ request('status') === 'Harvested'  ? 'selected' : '' }}>Harvested</option>
                    </select>
                </div>

                {{-- Water source --}}
                <div class="col-6 col-md-3">
                    <label class="form-label" style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--slate-500);">Water Source</label>
                    <select name="water_source" class="form-select form-select-sm">
                        <option value="">Any Water</option>
                        <option value="Rainfed"   {{ request('water_source') === 'Rainfed'   ? 'selected' : '' }}>Rainfed</option>
                        <option value="Irrigated" {{ request('water_source') === 'Irrigated' ? 'selected' : '' }}>Irrigated</option>
                        <option value="Both"      {{ request('water_source') === 'Both'      ? 'selected' : '' }}>Both</option>
                    </select>
                </div>

                {{-- Variety --}}
                <div class="col-6 col-md-3">
                    <label class="form-label" style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--slate-500);">Variety</label>
                    <select name="variety_id" class="form-select form-select-sm">
                        <option value="">All Varieties</option>
                        @foreach($varieties as $v)
                            <option value="{{ $v->id }}" {{ request('variety_id') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Barangay --}}
                <div class="col-6 col-md-3">
                    <label class="form-label" style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--slate-500);">Barangay</label>
                    <select name="barangay" class="form-select form-select-sm">
                        <option value="">All Barangays</option>
                        @foreach($barangays as $b)
                            <option value="{{ $b }}" {{ request('barangay') === $b ? 'selected' : '' }}>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Farmer --}}
                <div class="col-6 col-md-3">
                    <label class="form-label" style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--slate-500);">Farmer</label>
                    <select name="farmer_id" class="form-select form-select-sm">
                        <option value="">All Farmers</option>
                        @foreach($farmers as $f)
                            <option value="{{ $f->id }}" {{ request('farmer_id') == $f->id ? 'selected' : '' }}>{{ $f->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════ SECTION 2 — WHAT TO INCLUDE ═══════════ --}}
<div class="row g-2 mb-3">
    <div class="col-12">
        <div class="card-custom" style="padding: 18px 20px;">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-list-check" style="color: var(--green); font-size: 16px;"></i>
                    <span style="font-size: 14px; font-weight: 700;">Sections to Include</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllSections(true)">Select all</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllSections(false)">Deselect all</button>
                </div>
            </div>

            <div class="row g-2">
                @foreach($sections as $key => $label)
                    <div class="col-6 col-md-4">
                        <label class="d-flex align-items-start gap-2 p-2 border rounded-3 cursor-pointer section-card"
                               style="border-color: var(--slate-200); cursor: pointer; transition: all 0.15s ease;">
                            <input type="checkbox" name="sections[]" value="{{ $key }}"
                                   class="form-check-input section-toggle mt-1"
                                   checked>
                            <div>
                                <div style="font-size: 13px; font-weight: 600; color: var(--slate-800);">{{ $label }}</div>
                            </div>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ═══════════ SECTION 3 — EXPORT ═══════════ --}}
<div class="row g-2">
    <div class="col-12">
        <div class="card-custom" style="border-top: 3px solid var(--green); padding: 22px;">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <div style="font-size: 14px; font-weight: 700; color: var(--slate-800);">
                        <i class="bi bi-download" style="color: var(--brand-green);"></i>
                        Export Report
                    </div>
                    <div class="text-muted" style="font-size: 12px; margin-top: 2px;">
                        Choose a format. Only selected sections and filtered records will appear.
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit"
                            formaction="{{ route('admin.reports.generate-pdf') }}"
                            class="btn btn-danger">
                        <i class="bi bi-file-earmark-pdf-fill"></i> Download PDF
                    </button>
                    <button type="submit"
                            formaction="{{ route('admin.reports.generate-excel') }}"
                            class="btn btn-success">
                        <i class="bi bi-file-earmark-excel-fill"></i> Download Excel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

</form>

@endsection

@push('scripts')
<script>
function toggleAllSections(checked) {
    document.querySelectorAll('.section-toggle').forEach(cb => {
        cb.checked = checked;
        cb.dispatchEvent(new Event('change'));
    });
}

// Visual state for each section card
document.querySelectorAll('.section-toggle').forEach(cb => {
    const card = cb.closest('label');
    if (!card) return;

    const update = () => {
        card.style.borderColor = cb.checked ? 'var(--brand-green)' : 'var(--slate-200)';
        card.style.background  = cb.checked ? 'var(--brand-green-subtle)' : '#fff';
    };
    cb.addEventListener('change', update);
    update();
});
</script>
@endpush