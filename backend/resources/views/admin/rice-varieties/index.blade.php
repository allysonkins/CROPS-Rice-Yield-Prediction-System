@extends('layouts.app')

@section('title', 'Rice Varieties')

@section('content')
<!-- ============================================================ -->
<!-- HEADER BANNER -->
<!-- ============================================================ -->
<div class="row g-2 mb-4">
    <div class="col-12">
        <div class="card-custom" style="border-left: 4px solid var(--gold); background: linear-gradient(135deg, #f9fafb 0%, #f0f4f2 100%);">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <div class="d-inline-flex align-items-center gap-1 px-2 py-1 rounded-pill bg-success bg-opacity-10 text-success mb-2" style="font-size: 11px; font-weight: 700;">
                        <i class="bi bi-flower1" style="color: var(--gold);"></i>
                        National Seed Industry Council (NSIC) Certified
                    </div>
                    <h5 style="color: var(--gray-900); font-weight: 700; margin-bottom: 2px;">
                        Recommended Rice Varieties
                    </h5>
                    <p style="color: var(--gray-500); font-size: 13px; margin-bottom: 0; max-width: 600px;">
                        Agronomically certified cultivars approved for commercial cultivation, grain milling quality, and pest resilience in Santiago City agro‑climatic zones.
                    </p>
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
    </div>
</div>

<!-- ============================================================ -->
<!-- FILTER CONTROLS -->
<!-- ============================================================ -->
<div class="row g-2 mb-4">
    <div class="col-12">
        <div class="card-custom" style="padding: 12px 20px;">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div style="flex: 1; min-width: 180px;">
                    <div class="input-group" style="border-radius: 12px; overflow: hidden; border: 1px solid var(--gray-200);">
                        <span class="input-group-text" style="background: var(--gray-50); border: none; color: var(--gray-400);">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="searchVariety" class="form-control" placeholder="Search varieties..." style="border: none; background: var(--gray-50); font-size: 13px;">
                    </div>
                </div>
                <div>
                    <div class="bg-gray-100 p-1 rounded-xl d-flex" style="background: var(--gray-100); border-radius: 12px; padding: 4px; gap: 2px;">
                        <button class="filter-btn" data-type="All" style="padding: 6px 16px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: white; color: var(--gray-900); box-shadow: 0 1px 2px rgba(0,0,0,0.05);">All</button>
                        <button class="filter-btn" data-type="Inbred" style="padding: 6px 16px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: transparent; color: var(--gray-600);">Inbred</button>
                        <button class="filter-btn" data-type="Hybrid" style="padding: 6px 16px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: transparent; color: var(--gray-600);">Hybrid</button>
                    </div>
                </div>
                @if(auth()->user()->role === 'admin')
                    <div>
                        <span class="badge bg-secondary">{{ $varieties->count() }} Varieties</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- RICE VARIETIES GRID -->
<!-- ============================================================ -->
<div class="row g-3" id="varietiesGrid">
    @forelse($varieties as $variety)
        <div class="col-12 col-md-6 col-lg-4 variety-card"
             data-type="{{ $variety->classification }}"
             data-name="{{ strtolower($variety->name) }}">
            <div class="card-custom" style="height: 100%; display: flex; flex-direction: column; justify-content: space-between; transition: box-shadow 0.2s ease; border: 1px solid var(--gray-200);">
                <div>
                    <!-- Header -->
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div>
                            <span class="badge {{ $variety->classification == 'Hybrid' ? 'bg-warning text-dark' : 'bg-success' }}" style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 4px 10px; border-radius: 20px;">
                                {{ $variety->classification }} Variety
                            </span>
                            <h6 class="fw-bold mt-1 mb-0" style="color: var(--gray-900); font-size: 16px;">{{ $variety->name }}</h6>
                        </div>
                        <div style="width: 36px; height: 36px; border-radius: 12px; background: var(--gray-50); border: 1px solid var(--gray-200); display: flex; align-items: center; justify-content: center; color: var(--green);">
                            <i class="bi bi-award" style="font-size: 18px;"></i>
                        </div>
                    </div>

                    <!-- Description -->
                    <p class="text-muted small mb-3" style="font-size: 12px; line-height: 1.6;">
                        {{ $variety->description ?? 'A high-quality rice variety recommended for cultivation in Santiago City.' }}
                    </p>

                    <!-- Stats: show both Transplanted and Direct Seeded values -->
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div style="background: var(--gray-50); padding: 8px 10px; border-radius: 12px;">
                                <span style="font-size: 10px; font-weight: 600; color: var(--gray-500); display: block;">Transplanted</span>
                                <span style="font-size: 14px; font-weight: 800; color: var(--green);">
                                    {{ $variety->avg_yield_transplanted ? number_format($variety->avg_yield_transplanted, 2) : '—' }} t/ha
                                </span>
                                <span style="font-size: 10px; color: var(--gray-400); display: block;">
                                    Days: {{ $variety->growth_period_transplanted ?? $variety->growth_period ?? '—' }}
                                </span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div style="background: var(--gray-50); padding: 8px 10px; border-radius: 12px;">
                                <span style="font-size: 10px; font-weight: 600; color: var(--gray-500); display: block;">Direct Seeded</span>
                                <span style="font-size: 14px; font-weight: 800; color: var(--green);">
                                    {{ $variety->avg_yield_direct ? number_format($variety->avg_yield_direct, 2) : '—' }} t/ha
                                </span>
                                <span style="font-size: 10px; color: var(--gray-400); display: block;">
                                    Days: {{ $variety->growth_period_direct ?? $variety->growth_period ?? '—' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Grain Quality -->
                    <div class="mb-2">
                        <span style="font-size: 11px; font-weight: 700; color: var(--gray-700); display: block; margin-bottom: 4px;">Milling & Grain:</span>
                        <p style="font-size: 12px; color: var(--gray-600); background: var(--gray-50); padding: 6px 10px; border-radius: 8px; border: 1px solid var(--gray-200); margin: 0;">
                            {{ $variety->grain_quality ?? 'Premium quality with excellent milling recovery and good head rice.' }}
                        </p>
                    </div>

                    <!-- Resilience -->
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: var(--gray-700); display: block; margin-bottom: 4px;">Resilience & Hardiness:</span>
                        <div class="d-flex flex-wrap gap-1">
                            @php
                                $resilience = $variety->resilience;
                                if (is_string($resilience)) {
                                    $resilience = json_decode($resilience, true) ?? ['Bacterial Blight', 'Tungro', 'Blast'];
                                }
                                if (!is_array($resilience) || empty($resilience)) {
                                    $resilience = ['Bacterial Blight', 'Tungro', 'Blast'];
                                }
                            @endphp
                            @foreach($resilience as $trait)
                                <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 500; background: var(--green-light); color: var(--green); padding: 2px 10px; border-radius: 6px;">
                                    <i class="bi bi-shield-check" style="font-size: 11px;"></i>
                                    {{ $trait }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-3 pt-3" style="border-top: 1px solid var(--gray-200); display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 11px; color: var(--gray-400); font-weight: 500;">DA-PhilRice Certified</span>
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
                <i class="bi bi-inbox" style="font-size: 48px; color: var(--gray-400);"></i>
                <p class="mt-3 mb-0">No rice varieties yet.</p>
                @if(auth()->user()->role !== 'farmer')
                    <button type="button" class="btn btn-sm btn-primary mt-2" onclick="openRiceVarietyModal()">
                        <i class="bi bi-plus-circle"></i> Add Variety
                    </button>
                @endif
            </div>
        </div>
    @endforelse
</div>

<!-- Include Modal (Admin & Staff only) -->
@if(auth()->user()->role !== 'farmer')
    @include('admin.rice-varieties.partials.modal')
@endif

@endsection

@push('scripts')
<script>
    // ============================================================
    // SEARCH & FILTER
    // ============================================================
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
                    b.style.color = 'var(--gray-600)';
                    b.classList.remove('active');
                });
                this.style.background = 'white';
                this.style.color = 'var(--gray-900)';
                this.classList.add('active');
                filterVarieties();
            });
        });

        document.querySelector('.filter-btn[data-type="All"]')?.click();
    });

    // ============================================================
    // OPEN MODAL FOR ADD
    // ============================================================
    function openRiceVarietyModal() {
        document.getElementById('modalTitle').textContent = 'Add Rice Variety';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        fetch('{{ route("admin.rice-varieties.create") }}')
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalContent').style.display = 'block';
                document.getElementById('modalContent').innerHTML = html;
                
                const form = document.getElementById('riceVarietyForm');
                if (form) {
                    form.addEventListener('submit', handleRiceVarietyFormSubmit);
                }
            })
            .catch(() => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalContent').style.display = 'block';
                document.getElementById('modalContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> Failed to load form. Please try again.
                    </div>
                `;
            });

        var modal = new bootstrap.Modal(document.getElementById('riceVarietyModal'));
        modal.show();
    }

    // ============================================================
    // OPEN MODAL FOR EDIT
    // ============================================================
    function editRiceVariety(id) {
        document.getElementById('modalTitle').textContent = 'Edit Rice Variety';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        fetch('/admin/rice-varieties/' + id + '/edit')
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalContent').style.display = 'block';
                document.getElementById('modalContent').innerHTML = html;
                
                const form = document.getElementById('riceVarietyForm');
                if (form) {
                    form.addEventListener('submit', handleRiceVarietyFormSubmit);
                }
            })
            .catch(() => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalContent').style.display = 'block';
                document.getElementById('modalContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> Failed to load form. Please try again.
                    </div>
                `;
            });

        var modal = new bootstrap.Modal(document.getElementById('riceVarietyModal'));
        modal.show();
    }

    // ============================================================
    // HANDLE FORM SUBMISSION
    // ============================================================
    function handleRiceVarietyFormSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Saving...';
        submitBtn.disabled = true;

        const existingErrors = form.querySelector('#formErrors');
        if (existingErrors) existingErrors.remove();

        fetch(form.action, {
            method: form.method || 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                var modal = bootstrap.Modal.getInstance(document.getElementById('riceVarietyModal'));
                modal.hide();
                location.reload();
            } else {
                const errorDiv = document.createElement('div');
                errorDiv.id = 'formErrors';
                errorDiv.className = 'alert alert-danger mt-3';
                let errorMsg = data.error || 'An error occurred.';
                if (data.errors) {
                    errorMsg = Object.values(data.errors).flat().join('<br>');
                }
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