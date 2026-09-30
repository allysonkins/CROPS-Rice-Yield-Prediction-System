@extends('layouts.app')

@section('title', 'Rice Varieties')

@section('content')

{{-- ═══════════ HEADER ═══════════ --}}
<div class="card-custom" style="border-left: 4px solid var(--brand-green);">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div style="min-width: 0;">
            <h5 style="color: var(--slate-900); font-weight: 800; margin-bottom: 4px; letter-spacing: -0.3px;">
                <i class="bi bi-flower1" style="color: var(--brand-green);"></i>
                Rice Varieties
            </h5>
            <p style="color: var(--slate-500); font-size: 13px; margin-bottom: 10px; max-width: 620px;">
                Rice varieties used in farm records and yield predictions for Santiago City.
            </p>
            <div class="d-flex flex-wrap gap-3">
                <span style="font-size: 12px; color: var(--slate-500);">
                    <i class="bi bi-cpu" style="color: var(--brand-green);"></i>
                    <strong style="color: var(--slate-700);">{{ count($trainedVarieties) }}</strong> in ML model
                </span>
                <span style="font-size: 12px; color: var(--slate-500);">
                    <i class="bi bi-clock-history" style="color: var(--brand-green);"></i>
                    Last trained: <strong style="color: var(--slate-700);">{{ $lastTrained }}</strong>
                </span>
                <span style="font-size: 12px; color: var(--slate-500);">
                    <i class="bi bi-collection" style="color: var(--brand-green);"></i>
                    <strong style="color: var(--slate-700);">{{ $varieties->count() }}</strong> total varieties
                </span>
            </div>
        </div>
        <div>
            @if(auth()->user()->role !== 'farmer')
                <button type="button" class="btn btn-success" onclick="openRiceVarietyModal()">
                    <i class="bi bi-plus-circle"></i> Add Variety
                </button>
            @endif
        </div>
    </div>
</div>

{{-- ═══════════ FILTER BAR ═══════════ --}}
<div class="card-custom" style="padding: 14px 20px;">
    <div class="d-flex flex-wrap align-items-center gap-3">
        <div style="flex: 1; min-width: 200px;">
            <div class="input-group" style="border-radius: 12px; overflow: hidden; border: 1px solid var(--slate-200);">
                <span class="input-group-text" style="background: var(--slate-50); border: none; color: var(--slate-400);">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="searchVariety" class="form-control"
                       placeholder="Search varieties..."
                       style="border: none; background: var(--slate-50); font-size: 13px;">
            </div>
        </div>

        <div style="background: var(--slate-100); border-radius: 12px; padding: 4px; display: flex; gap: 2px;">
            <button class="filter-btn active" data-type="All"
                    style="padding: 6px 16px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: white; color: var(--slate-900); box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                All
            </button>
            <button class="filter-btn" data-type="Inbred"
                    style="padding: 6px 16px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: transparent; color: var(--slate-600);">
                Inbred
            </button>
            <button class="filter-btn" data-type="Hybrid"
                    style="padding: 6px 16px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: transparent; color: var(--slate-600);">
                Hybrid
            </button>
        </div>

        <span class="badge-status"
              style="background: var(--brand-green-light); color: var(--brand-green-dark); border-color: #c7e6d2;">
            <span class="dot" style="background: var(--brand-green);"></span>
            {{ $varieties->count() }} Varieties
        </span>
    </div>
</div>

{{-- ═══════════ GRID ═══════════ --}}
<div class="row g-3" id="varietiesGrid">
    @forelse($varieties as $variety)
        @php
            $isTrained = in_array($variety->name, $trainedVarieties);
            $isHybrid  = $variety->classification === 'Hybrid';
        @endphp
        <div class="col-12 col-md-6 col-lg-4 variety-card"
             data-type="{{ $variety->classification }}"
             data-name="{{ strtolower($variety->name) }}"
             data-trained="{{ $isTrained ? '1' : '0' }}">
            <div class="card-custom h-100 mb-0" style="display: flex; flex-direction: column; justify-content: space-between;">

                {{-- Header --}}
                <div>
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div style="min-width: 0;">
                            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                <span class="badge-status"
                                      style="background: {{ $isHybrid ? '#eef2ff' : 'var(--brand-green-light)' }};
                                             color: {{ $isHybrid ? '#3730a3' : 'var(--brand-green-dark)' }};
                                             border-color: {{ $isHybrid ? '#c7d2fe' : '#c7e6d2' }};">
                                    <span class="dot" style="background: {{ $isHybrid ? '#3730a3' : 'var(--brand-green)' }};"></span>
                                    {{ $variety->classification }}
                                </span>

                                @if($isTrained)
                                    <span class="badge-status"
                                          style="background: var(--brand-green-subtle); color: var(--brand-green-dark); border-color: #c7e6d2;">
                                        <span class="dot" style="background: var(--brand-green);"></span>
                                        In ML Model
                                    </span>
                                @else
                                    <span class="badge-status"
                                          style="background: var(--brand-gold-subtle); color: #92400e; border-color: #fde68a;">
                                        <span class="dot" style="background: var(--brand-gold);"></span>
                                        Fallback Mode
                                    </span>
                                @endif
                            </div>
                            <h6 style="font-weight: 700; color: var(--slate-900); font-size: 15px; margin: 0; letter-spacing: -0.2px;">
                                {{ $variety->name }}
                            </h6>
                        </div>
                        <div style="width: 36px; height: 36px; border-radius: 12px; background: var(--slate-50); border: 1px solid var(--slate-200); display: flex; align-items: center; justify-content: center; color: var(--brand-green); flex-shrink: 0;">
                            <i class="bi bi-award" style="font-size: 18px;"></i>
                        </div>
                    </div>

                    {{-- Description --}}
                    <p style="font-size: 12px; color: var(--slate-500); line-height: 1.55; margin-bottom: 14px;">
                        {{ $variety->description ?? 'No description provided.' }}
                    </p>

                    {{-- Yield tiles --}}
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div style="background: var(--slate-50); padding: 10px 12px; border-radius: 12px; border: 1px solid var(--slate-200);">
                                <span style="font-size: 10px; font-weight: 700; color: var(--slate-500); display: block; text-transform: uppercase; letter-spacing: 0.4px;">
                                    Transplanted
                                </span>
                                <span style="font-size: 15px; font-weight: 800; color: var(--brand-green); letter-spacing: -0.3px;">
                                    {{ $variety->avg_yield_transplanted ? number_format($variety->avg_yield_transplanted, 2) : '—' }}
                                    <small style="font-size: 10px; font-weight: 600;">t/ha</small>
                                </span>
                                <span style="font-size: 10px; color: var(--slate-400); display: block;">
                                    {{ $variety->growth_period_transplanted ?? $variety->growth_period ?? '—' }} days
                                </span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div style="background: var(--slate-50); padding: 10px 12px; border-radius: 12px; border: 1px solid var(--slate-200);">
                                <span style="font-size: 10px; font-weight: 700; color: var(--slate-500); display: block; text-transform: uppercase; letter-spacing: 0.4px;">
                                    Direct Seeded
                                </span>
                                <span style="font-size: 15px; font-weight: 800; color: var(--brand-green); letter-spacing: -0.3px;">
                                    {{ $variety->avg_yield_direct ? number_format($variety->avg_yield_direct, 2) : '—' }}
                                    <small style="font-size: 10px; font-weight: 600;">t/ha</small>
                                </span>
                                <span style="font-size: 10px; color: var(--slate-400); display: block;">
                                    {{ $variety->growth_period_direct ?? $variety->growth_period ?? '—' }} days
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Grain quality --}}
                    <div class="mb-2">
                        <span style="font-size: 11px; font-weight: 700; color: var(--slate-700); display: block; margin-bottom: 4px;">
                            Milling &amp; Grain Quality
                        </span>
                        <p style="font-size: 11.5px; color: var(--slate-600); background: var(--slate-50); padding: 6px 10px; border-radius: 8px; border: 1px solid var(--slate-200); margin: 0; line-height: 1.5;">
                            {{ $variety->grain_quality ?? 'No grain quality data.' }}
                        </p>
                    </div>

                    {{-- Resilience --}}
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: var(--slate-700); display: block; margin-bottom: 6px;">
                            Resilience &amp; Hardiness
                        </span>
                        <div class="d-flex flex-wrap gap-1">
                            @php
                                $resilience = $variety->resilience;
                                if (is_string($resilience)) {
                                    $resilience = json_decode($resilience, true) ?? [];
                                }
                                if (!is_array($resilience) || empty($resilience)) {
                                    $resilience = [];
                                }
                            @endphp
                            @forelse($resilience as $trait)
                                <span class="badge-status"
                                      style="background: var(--brand-green-subtle); color: var(--brand-green-dark); border-color: #c7e6d2; font-size: 10px; padding: 2px 10px;">
                                    <i class="bi bi-shield-check" style="font-size: 11px;"></i>
                                    {{ $trait }}
                                </span>
                            @empty
                                <span style="font-size: 11px; color: var(--slate-400); font-style: italic;">
                                    No resilience data
                                </span>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="mt-3 pt-3" style="border-top: 1px solid var(--slate-200); display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 11px; color: var(--slate-400); font-weight: 500;">
                        {{ $isTrained ? 'Used by ML model' : 'Fallback (numeric only)' }}
                    </span>
                    @if(auth()->user()->role !== 'farmer')
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-secondary" onclick="editRiceVariety({{ $variety->id }})" style="padding: 4px 10px; font-size: 11px;">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="{{ route('admin.rice-varieties.destroy', $variety->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this variety?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" style="padding: 4px 10px; font-size: 11px;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card-custom text-center py-5">
                <i class="bi bi-inbox" style="font-size: 48px; color: var(--slate-300);"></i>
                <h6 class="mt-3 mb-1" style="color: var(--slate-700); font-weight: 700;">No rice varieties yet</h6>
                <p style="font-size: 13px; color: var(--slate-500); margin: 0;">
                    Add your first variety to start recording farm data.
                </p>
                @if(auth()->user()->role !== 'farmer')
                    <button type="button" class="btn btn-sm btn-success mt-3" onclick="openRiceVarietyModal()">
                        <i class="bi bi-plus-circle"></i> Add Variety
                    </button>
                @endif
            </div>
        </div>
    @endforelse
</div>

{{-- Modal --}}
@if(auth()->user()->role !== 'farmer')
    @include('admin.rice-varieties.partials.modal')
@endif

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchVariety');
        const filterBtns = document.querySelectorAll('.filter-btn');
        const cards = document.querySelectorAll('.variety-card');

        function filterVarieties() {
            const search = searchInput.value.toLowerCase().trim();
            const activeBtn = document.querySelector('.filter-btn.active');
            const activeType = activeBtn ? activeBtn.dataset.type : 'All';

            cards.forEach(card => {
                const name = card.dataset.name || '';
                const type = card.dataset.type || '';
                const matchesSearch = name.includes(search);
                const matchesType = activeType === 'All' || type === activeType;
                card.style.display = (matchesSearch && matchesType) ? '' : 'none';
            });
        }

        searchInput.addEventListener('input', filterVarieties);

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                filterBtns.forEach(b => {
                    b.style.background = 'transparent';
                    b.style.color = 'var(--slate-600)';
                    b.classList.remove('active');
                });
                this.style.background = 'white';
                this.style.color = 'var(--slate-900)';
                this.style.boxShadow = '0 1px 2px rgba(0,0,0,0.05)';
                this.classList.add('active');
                filterVarieties();
            });
        });
    });

    function openRiceVarietyModal() {
        document.getElementById('modalTitle').textContent = 'Add Rice Variety';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        fetch('{{ route("admin.rice-varieties.create") }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.text())
        .then(html => {
            document.getElementById('modalLoading').style.display = 'none';
            document.getElementById('modalContent').style.display = 'block';
            document.getElementById('modalContent').innerHTML = html;
            const form = document.getElementById('riceVarietyForm');
            if (form) form.addEventListener('submit', handleRiceVarietyFormSubmit);
        })
        .catch(() => {
            document.getElementById('modalLoading').innerHTML =
                '<p class="text-danger">Failed to load form.</p>';
        });

        new bootstrap.Modal(document.getElementById('riceVarietyModal')).show();
    }

    function editRiceVariety(id) {
        document.getElementById('modalTitle').textContent = 'Edit Rice Variety';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        fetch('/admin/rice-varieties/' + id + '/edit', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.text())
        .then(html => {
            document.getElementById('modalLoading').style.display = 'none';
            document.getElementById('modalContent').style.display = 'block';
            document.getElementById('modalContent').innerHTML = html;
            const form = document.getElementById('riceVarietyForm');
            if (form) form.addEventListener('submit', handleRiceVarietyFormSubmit);
        })
        .catch(() => {
            document.getElementById('modalLoading').innerHTML =
                '<p class="text-danger">Failed to load form.</p>';
        });

        new bootstrap.Modal(document.getElementById('riceVarietyModal')).show();
    }

    function handleRiceVarietyFormSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
        submitBtn.disabled = true;

        const existingErrors = form.querySelector('#formErrors');
        if (existingErrors) existingErrors.remove();

        fetch(form.action, {
            method: form.method || 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('riceVarietyModal')).hide();
                location.reload();
            } else {
                const errorDiv = document.createElement('div');
                errorDiv.id = 'formErrors';
                errorDiv.className = 'alert alert-danger mt-3';
                let errorMsg = data.error || 'An error occurred.';
                if (data.errors) errorMsg = Object.values(data.errors).flat().join('<br>');
                errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + errorMsg;
                form.prepend(errorDiv);
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            alert('An error occurred. Please try again.');
        });
    }
</script>
@endpush