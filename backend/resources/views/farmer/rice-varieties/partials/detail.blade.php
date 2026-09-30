@php
    $isHybrid = $variety->classification === 'Hybrid';

    $avgT = $variety->avg_yield_transplanted ?? $variety->avg_yield;
    $maxT = $variety->max_yield_transplanted ?? $variety->max_yield;
    $avgD = $variety->avg_yield_direct;
    $maxD = $variety->max_yield_direct;

    $resilience = $variety->resilience;
    if (is_string($resilience)) {
        $resilience = json_decode($resilience, true) ?? [];
    }
    if (!is_array($resilience)) {
        $resilience = [];
    }
@endphp

{{-- ═══════════ NAME + BADGE ═══════════ --}}
<div class="mb-3">
    <h6 style="font-weight: 800; color: var(--slate-900); margin-bottom: 6px; font-size: 15px; letter-spacing: -0.2px; line-height: 1.3;">
        {{ $variety->name }}
    </h6>
    <span class="badge-status"
          style="background: {{ $isHybrid ? '#eef2ff' : 'var(--brand-green-light)' }};
                 color: {{ $isHybrid ? '#3730a3' : 'var(--brand-green-dark)' }};
                 border-color: {{ $isHybrid ? '#c7d2fe' : '#c7e6d2' }}; font-size: 10px;">
        <span class="dot" style="background: {{ $isHybrid ? '#3730a3' : 'var(--brand-green)' }};"></span>
        {{ $variety->classification }}
    </span>
</div>

{{-- ═══════════ DESCRIPTION ═══════════ --}}
@if($variety->description)
    <p style="font-size: 12px; color: var(--slate-600); line-height: 1.55; margin-bottom: 14px;">
        {{ $variety->description }}
    </p>
@endif

{{-- ═══════════ YIELD TILES (side-by-side) ═══════════ --}}
<div class="row g-2 mb-3">
    {{-- Transplanted --}}
    <div class="col-6">
        <div style="background: var(--slate-50); border-radius: var(--radius-md); border: 1px solid var(--slate-200); border-left: 3px solid var(--brand-green); padding: 10px 12px;">
            <div style="font-size: 9px; font-weight: 700; color: var(--slate-500); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 4px;">
                <i class="bi bi-flower1"></i> Transplanted
            </div>
            @if($avgT)
                <div style="font-size: 18px; font-weight: 800; color: var(--brand-green); line-height: 1.1; letter-spacing: -0.4px;">
                    {{ number_format($avgT, 2) }}
                    <small style="font-size: 10px; font-weight: 600;">t/ha</small>
                </div>
                <div style="font-size: 10px; color: var(--slate-400); margin-top: 2px;">
                    Max {{ number_format($maxT, 2) }} · {{ $variety->growth_period_transplanted ?? '—' }}d
                </div>
            @else
                <div style="font-size: 11px; color: var(--slate-400); font-style: italic;">No data</div>
            @endif
        </div>
    </div>

    {{-- Direct Seeded --}}
    <div class="col-6">
        <div style="background: var(--slate-50); border-radius: var(--radius-md); border: 1px solid var(--slate-200); border-left: 3px solid var(--brand-green-mid); padding: 10px 12px;">
            <div style="font-size: 9px; font-weight: 700; color: var(--slate-500); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 4px;">
                <i class="bi bi-flower2"></i> Direct-Seeded
            </div>
            @if($avgD)
                <div style="font-size: 18px; font-weight: 800; color: var(--brand-green); line-height: 1.1; letter-spacing: -0.4px;">
                    {{ number_format($avgD, 2) }}
                    <small style="font-size: 10px; font-weight: 600;">t/ha</small>
                </div>
                <div style="font-size: 10px; color: var(--slate-400); margin-top: 2px;">
                    Max {{ number_format($maxD, 2) }} · {{ $variety->growth_period_direct ?? '—' }}d
                </div>
            @else
                <div style="font-size: 11px; color: var(--slate-400); font-style: italic;">No data</div>
            @endif
        </div>
    </div>
</div>

{{-- ═══════════ GRAIN QUALITY ═══════════ --}}
@if($variety->grain_quality)
    <div class="mb-3">
        <div style="font-size: 10px; font-weight: 700; color: var(--slate-700); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 4px;">
            Milling &amp; Grain Quality
        </div>
        <p style="font-size: 11.5px; color: var(--slate-600); line-height: 1.5; margin: 0; padding: 8px 10px; background: var(--slate-50); border-radius: var(--radius-sm); border: 1px solid var(--slate-200);">
            {{ $variety->grain_quality }}
        </p>
    </div>
@endif

{{-- ═══════════ DISEASE NOTES ═══════════ --}}
@if($variety->disease_susceptibility)
    <div class="mb-3">
        <div style="font-size: 10px; font-weight: 700; color: var(--slate-700); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 4px;">
            Disease Notes
        </div>
        <div class="d-flex align-items-start gap-2 p-2"
             style="background: var(--brand-danger-light); border-radius: var(--radius-sm); border-left: 3px solid var(--brand-danger);">
            <i class="bi bi-shield-exclamation" style="color: var(--brand-danger); font-size: 14px; flex-shrink: 0; margin-top: 1px;"></i>
            <p style="font-size: 11.5px; color: #991b1b; line-height: 1.5; margin: 0;">
                {{ $variety->disease_susceptibility }}
            </p>
        </div>
    </div>
@endif

{{-- ═══════════ RESILIENCE ═══════════ --}}
@if(!empty($resilience))
    <div class="mb-0">
        <div style="font-size: 10px; font-weight: 700; color: var(--slate-700); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 6px;">
            Resilience &amp; Tolerance
        </div>
        <div class="d-flex flex-wrap gap-1">
            @foreach($resilience as $tag)
                <span class="badge-status"
                      style="background: var(--brand-green-subtle); color: var(--brand-green-dark); border-color: #c7e6d2; font-size: 10px; padding: 2px 9px;">
                    <i class="bi bi-shield-check" style="font-size: 10px;"></i>
                    {{ $tag }}
                </span>
            @endforeach
        </div>
    </div>
@endif

{{-- ═══════════ FOOTER ═══════════ --}}
<div class="d-flex justify-content-end mt-3 pt-2" style="border-top: 1px solid var(--slate-200);">
    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
        Close
    </button>
</div>