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
                Browse the rice varieties the system uses to predict your farm's yield.
                Yields are shown in <strong style="color: var(--slate-700);">cavan per hectare</strong>
                (1 cavan ≈ 50 kg).
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

            $resilience = $variety->resilience;
            if (is_string($resilience)) {
                $resilience = json_decode($resilience, true) ?? [];
            }
            if (!is_array($resilience)) {
                $resilience = [];
            }

            // Yield conversions
            $transplantedTons  = $variety->avg_yield_transplanted;
            $directTons        = $variety->avg_yield_direct;
            $transplantedCavan = $transplantedTons ? t_ha_to_cavan_ha((float) $transplantedTons) : null;
            $directCavan       = $directTons ? t_ha_to_cavan_ha((float) $directTons) : null;

            $transplantedDays = $variety->growth_period_transplanted ?? $variety->growth_period;
            $directDays       = $variety->growth_period_direct ?? $variety->growth_period;
        @endphp

        <div class="col-12 col-md-6 col-lg-4 variety-card"
             data-type="{{ $variety->classification }}"
             data-name="{{ strtolower($variety->name) }}">

            <div class="text-decoration-none d-block h-100"
                 style="color: inherit; cursor: pointer;"
                 onclick="openVarietyDetail({{ $variety->id }})">

                <div class="card-custom h-100 mb-0 d-flex flex-column"
                     style="transition: var(--transition-smooth);"
                     onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='var(--shadow-md)';"
                     onmouseout="this.style.transform='none'; this.style.boxShadow='var(--shadow-sm)';">

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
                                        {{ $transplantedCavan ? number_format($transplantedCavan, 0) : '—' }}
                                        <small style="font-size: 10px; font-weight: 600;">cavan/ha</small>
                                    </span>
                                    <span style="font-size: 10px; color: var(--slate-400); display: block;">
                                        @if($transplantedCavan)
                                            ≈ {{ number_format($transplantedTons, 2) }} t/ha ·
                                        @endif
                                        {{ $transplantedDays ?? '—' }} days
                                    </span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div style="background: var(--slate-50); padding: 10px 12px; border-radius: 12px; border: 1px solid var(--slate-200);">
                                    <span style="font-size: 10px; font-weight: 700; color: var(--slate-500); display: block; text-transform: uppercase; letter-spacing: 0.4px;">
                                        Direct Seeded
                                    </span>
                                    <span style="font-size: 15px; font-weight: 800; color: var(--brand-green); letter-spacing: -0.3px;">
                                        {{ $directCavan ? number_format($directCavan, 0) : '—' }}
                                        <small style="font-size: 10px; font-weight: 600;">cavan/ha</small>
                                    </span>
                                    <span style="font-size: 10px; color: var(--slate-400); display: block;">
                                        @if($directCavan)
                                            ≈ {{ number_format($directTons, 2) }} t/ha ·
                                        @endif
                                        {{ $directDays ?? '—' }} days
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
                        <span style="font-size: 11.5px; color: var(--brand-green); font-weight: 600;">
                            View details <i class="bi bi-arrow-right"></i>
                        </span>
                    </div>

                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card-custom text-center py-5">
                <i class="bi bi-inbox" style="font-size: 48px; color: var(--slate-300);"></i>
                <h6 class="mt-3 mb-1" style="color: var(--slate-700); font-weight: 700;">No rice varieties yet</h6>
                <p style="font-size: 13px; color: var(--slate-500); margin: 0;">
                    Varieties will appear here once they've been registered.
                </p>
            </div>
        </div>
    @endforelse
</div>

{{-- ═══════════ VARIETY DETAIL MODAL ═══════════ --}}
<div class="modal fade" id="varietyDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--brand-green); color: white; padding: 12px 18px;">
                <h5 class="modal-title" style="font-size: 15px;">
                    <i class="bi bi-flower1"></i> Rice Variety
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="overflow: hidden; padding: 16px 18px;">
                <div class="text-center py-3" id="varietyDetailLoading">
                    <div class="spinner-border text-success" role="status" style="width: 28px; height: 28px;"></div>
                    <p class="mt-2 mb-0" style="color: var(--slate-500); font-size: 12px;">Loading...</p>
                </div>
                <div id="varietyDetailContent" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

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

        searchInput?.addEventListener('input', filterVarieties);

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                filterBtns.forEach(b => {
                    b.style.background = 'transparent';
                    b.style.color = 'var(--slate-600)';
                    b.style.boxShadow = 'none';
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

    // ═══════════════════════════════════════════════════════════
    // VARIETY DETAIL MODAL
    // ═══════════════════════════════════════════════════════════
    function openVarietyDetail(id) {
        const loading = document.getElementById('varietyDetailLoading');
        const content = document.getElementById('varietyDetailContent');

        loading.style.display = 'block';
        content.style.display = 'none';
        content.innerHTML = '';

        new bootstrap.Modal(document.getElementById('varietyDetailModal')).show();

        fetch('/farmer/rice-varieties/' + id, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.text();
        })
        .then(html => {
            loading.style.display = 'none';
            content.style.display = 'block';
            content.innerHTML = html;
        })
        .catch(err => {
            loading.innerHTML =
                '<div class="text-center py-4">' +
                '<i class="bi bi-exclamation-triangle-fill" style="font-size: 32px; color: var(--brand-danger);"></i>' +
                '<p class="mt-2" style="color: var(--brand-danger); font-size: 13px;">Failed to load variety: ' + err.message + '</p>' +
                '</div>';
        });
    }
</script>
@endpush