@extends('layouts.app')

@section('title', 'My Dashboard')

@php
    $isVerified = auth()->user()->verified_by_cao_at !== null;
@endphp

@section('content')

{{-- ═══════════ GREETING ═══════════ --}}
<div class="card-custom mb-4" style="border-top: 4px solid var(--brand-green);">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3" style="min-width: 0;">
            <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--brand-green-light); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="bi bi-person-fill" style="font-size: 24px; color: var(--brand-green);"></i>
            </div>
            <div style="min-width: 0;">
                <h4 style="margin: 0; font-weight: 800; color: var(--slate-900); letter-spacing: -0.4px;">
                    Kumusta, {{ $user->name }}! 🌾
                </h4>
                @if($focusFarm)
                    <div style="font-size: 13px; color: var(--slate-500); margin-top: 3px;">
                        Focus farm: <strong style="color: var(--slate-700);">{{ $focusFarm->name }}</strong>
                        · {{ $focusFarm->barangay }}
                        · {{ number_format($focusFarm->land_area_ha, 2) }} ha
                    </div>
                @endif
            </div>
        </div>
        @if($farms->count() > 1)
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-success dropdown-toggle" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-arrow-left-right"></i> Switch Farm ({{ $farms->count() }})
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header" style="font-size: 11px; text-transform: uppercase;">Your Farms</h6></li>
                    @foreach($farms as $f)
                        <li>
                            <a class="dropdown-item {{ $focusFarm && $focusFarm->id === $f->id ? 'active' : '' }}"
                               href="{{ route('farmer.dashboard', ['farm' => $f->id]) }}">
                                <strong>{{ $f->name }}</strong>
                                <br>
                                <small style="opacity: 0.7;">{{ $f->barangay }} · {{ number_format($f->land_area_ha, 2) }} ha</small>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>

@if($farms->isEmpty())

    <div class="card-custom text-center py-5">
        <i class="bi bi-geo-alt" style="font-size: 48px; color: var(--slate-300);"></i>
        <h6 class="mt-3 mb-1" style="color: var(--slate-700); font-weight: 700;">No farms yet</h6>
        <p style="font-size: 13px; color: var(--slate-500); margin-bottom: 20px;">
            Add a farm to start recording your seasons and getting predictions.
        </p>
        @if($isVerified)
            <a href="{{ route('farmer.farms.index') }}" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Add My First Farm
            </a>
        @else
            <button class="btn btn-success" disabled title="Available after CAO verification">
                <i class="bi bi-lock"></i> Available after CAO verification
            </button>
        @endif
    </div>

@else

<div class="row g-3">

    {{-- ═══════════ LAST HARVEST ═══════════ --}}
    <div class="col-12 col-lg-6">
        <div class="card-custom h-100 mb-0" style="border-left: 4px solid var(--brand-gold);">
            <div class="card-title d-flex align-items-center flex-wrap gap-2 mb-3">
                <i class="bi bi-basket-fill" style="color: var(--brand-gold);"></i>
                <span>Last Harvest</span>
                @if($lastHarvest)
                    <span class="badge-status ms-auto"
                          style="background: var(--slate-100); color: var(--slate-700); border-color: var(--slate-200); font-size: 11px;">
                        <span class="dot" style="background: var(--brand-gold);"></span>
                        {{ $lastHarvest->season }} {{ $lastHarvest->year }}
                    </span>
                @endif
            </div>

            @if($lastHarvest)
                <div style="font-size: 15px; font-weight: 700; color: var(--slate-900); margin-bottom: 4px;">
                    {{ $lastHarvest->riceVariety->name ?? 'N/A' }}
                </div>
                <div style="font-size: 12px; color: var(--slate-500); margin-bottom: 14px;">
                    {{ $lastHarvest->seeding_method ?? '—' }}
                </div>

                @php
                    $predCavanHa = $lastPred ? t_ha_to_cavan_ha((float) $lastPred->predicted_yield_tons_ha) : null;
                    $actCavanHa  = t_ha_to_cavan_ha((float) $lastHarvest->actual_yield_tons_ha);
                    $totalCavan  = cavan_total((float) $lastHarvest->actual_yield_tons_ha, (float) $focusFarm->land_area_ha);
                    $totalKg     = $totalCavan * 50;
                @endphp

                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.4px;">Predicted</div>
                        @if($predCavanHa !== null)
                            <div style="font-size: 22px; font-weight: 800; color: var(--slate-700); line-height: 1.1;">
                                {{ $predCavanHa }}
                            </div>
                            <div style="font-size: 11px; color: var(--slate-500);">cavan/ha</div>
                            <div style="font-size: 10px; color: var(--slate-400); margin-top: 2px;">
                                ({{ number_format($lastPred->predicted_yield_tons_ha, 2) }} t/ha)
                            </div>
                        @else
                            <div style="font-size: 18px; color: var(--slate-400);">—</div>
                        @endif
                    </div>
                    <div class="col-4">
                        <div style="font-size: 10px; text-transform: uppercase; color: var(--brand-green); font-weight: 700; letter-spacing: 0.4px;">Actual</div>
                        <div style="font-size: 22px; font-weight: 800; color: var(--brand-green); line-height: 1.1;">
                            {{ $actCavanHa }}
                        </div>
                        <div style="font-size: 11px; color: var(--slate-500);">cavan/ha</div>
                        <div style="font-size: 10px; color: var(--slate-400); margin-top: 2px;">
                            ({{ number_format($lastHarvest->actual_yield_tons_ha, 2) }} t/ha)
                        </div>
                    </div>
                    <div class="col-4">
                        <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.4px;">Accuracy</div>
                        <div style="font-size: 14px; font-weight: 700; padding-top: 4px;">
                            @if($lastPred && $lastPred->predicted_yield_tons_ha > 0)
                                @php
                                    $diff = abs($lastPred->predicted_yield_tons_ha - $lastHarvest->actual_yield_tons_ha);
                                    $pct  = $diff / $lastHarvest->actual_yield_tons_ha * 100;
                                @endphp
                                @if($pct <= 10)
                                    <span class="badge-status" style="background: var(--brand-green-light); color: var(--brand-green-dark); border-color: #c7e6d2;">
                                        <span class="dot" style="background: var(--brand-green);"></span> Matched
                                    </span>
                                @elseif($pct <= 25)
                                    <span class="badge-status" style="background: var(--brand-gold-light); color: #92400e; border-color: #fde68a;">
                                        <span class="dot" style="background: var(--brand-gold);"></span> Close
                                    </span>
                                @else
                                    <span class="badge-status" style="background: var(--brand-danger-light); color: #991b1b; border-color: #fecaca;">
                                        <span class="dot" style="background: var(--brand-danger);"></span> Off
                                    </span>
                                @endif
                            @else
                                <span style="color: var(--slate-400);">—</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="p-3" style="background: var(--brand-green-light); border-radius: var(--radius-md);">
                    <div style="font-size: 11px; text-transform: uppercase; color: var(--brand-green-dark); font-weight: 700; letter-spacing: 0.4px; margin-bottom: 4px;">
                        <i class="bi bi-box-seam"></i> Total Harvest
                    </div>
                    <div style="font-size: 26px; font-weight: 800; color: var(--brand-green-dark); line-height: 1.1;">
                        {{ number_format($totalCavan, 0) }}
                        <span style="font-size: 14px; font-weight: 600;">cavan</span>
                    </div>
                    <div style="font-size: 12px; color: var(--brand-green-dark); opacity: 0.8; margin-top: 2px;">
                        ≈ {{ number_format($totalKg, 0) }} kg · from {{ number_format($focusFarm->land_area_ha, 2) }} ha
                    </div>
                </div>

            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 38px; color: var(--slate-300);"></i>
                    <p style="font-size: 13px; color: var(--slate-500); margin: 12px 0 0;">No harvested seasons yet.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════════ THIS SEASON ═══════════ --}}
    <div class="col-12 col-lg-6">
        <div class="card-custom h-100 mb-0" style="border-left: 4px solid var(--brand-green);">
            <div class="card-title d-flex align-items-center flex-wrap gap-2 mb-3">
                <i class="bi bi-flower1"></i>
                <span>This Season</span>
                @if($thisSeason)
                    <span class="badge-status ms-auto"
                          style="background: var(--slate-100); color: var(--slate-700); border-color: var(--slate-200); font-size: 11px;">
                        <span class="dot" style="background: var(--brand-green);"></span>
                        {{ $thisSeason->season }} {{ $thisSeason->year }}
                    </span>
                @endif
            </div>

            @if($thisSeason)
                <div style="font-size: 15px; font-weight: 700; color: var(--slate-900); margin-bottom: 4px;">
                    {{ $thisSeason->riceVariety->name ?? 'N/A' }}
                </div>
                <div style="font-size: 12px; color: var(--slate-500); margin-bottom: 14px;">
                    {{ $thisSeason->seeding_method ?? '—' }} · {{ number_format($thisSeason->fertilizer_kg_ha, 0) }} kg/ha fertilizer
                </div>

                @if($thisPred)
                    @php
                        $predCavanHa = t_ha_to_cavan_ha((float) $thisPred->predicted_yield_tons_ha);
                        $expectedCavan = cavan_total((float) $thisPred->predicted_yield_tons_ha, (float) $focusFarm->land_area_ha);
                        $expectedKg = $expectedCavan * 50;
                    @endphp

                    <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.4px;">Estimated harvest</div>
                    <div style="font-size: 34px; font-weight: 800; color: var(--brand-green); line-height: 1.1; letter-spacing: -0.6px;">
                        {{ $predCavanHa }}
                        <small style="font-size: 14px; font-weight: 700;">cavan/ha</small>
                    </div>
                    <div style="font-size: 12px; color: var(--slate-600); margin-top: 4px;">
                        {{ $thisPred->predicted_class ?? 'Medium' }} confidence
                        @if($thisPred->confidence)
                            · {{ number_format($thisPred->confidence * 100, 0) }}%
                        @endif
                        <br>
                        <small style="font-size: 11px; color: var(--slate-400);">
                            ({{ number_format($thisPred->predicted_yield_tons_ha, 2) }} t/ha predicted by the ML model)
                        </small>
                    </div>

                    <div class="mt-3 p-3" style="background: var(--brand-green-light); border-radius: var(--radius-md);">
                        <div style="font-size: 11px; text-transform: uppercase; color: var(--brand-green-dark); font-weight: 700; letter-spacing: 0.4px; margin-bottom: 4px;">
                            <i class="bi bi-calculator"></i> Expected total
                        </div>
                        <div style="font-size: 26px; font-weight: 800; color: var(--brand-green-dark); line-height: 1.1;">
                            {{ number_format($expectedCavan, 0) }}
                            <span style="font-size: 14px; font-weight: 600;">cavan</span>
                        </div>
                        <div style="font-size: 12px; color: var(--brand-green-dark); opacity: 0.8; margin-top: 2px;">
                            ≈ {{ number_format($expectedKg, 0) }} kg · from {{ number_format($focusFarm->land_area_ha, 2) }} ha
                        </div>
                    </div>
                @else
                    <div class="alert-custom alert-custom-warning mb-0" style="font-size: 13px;">
                        <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                        <div class="alert-content">No prediction generated yet for this season.</div>
                    </div>
                @endif

            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 38px; color: var(--slate-300);"></i>
                    <p style="font-size: 13px; color: var(--slate-500); margin: 12px 0 16px;">No active season right now.</p>
                    @if($isVerified)
                        <a href="{{ route('farmer.farm-records.index') }}" class="btn btn-sm btn-success">
                            <i class="bi bi-plus-circle"></i> Add This Season
                        </a>
                    @else
                        <button class="btn btn-sm btn-success" disabled>
                            <i class="bi bi-lock"></i> Available after CAO verification
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════════ RECOMMENDATIONS ═══════════ --}}
    <div class="col-12">
        <div class="card-custom mb-0" style="border-top: 4px solid var(--brand-green);">
            <div class="card-title d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-lightbulb-fill" style="color: var(--brand-gold);"></i>
                    Planning for Next Season?
                </span>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if($nextSeason)
                        <span class="badge-status"
                              style="background: var(--brand-green-light); color: var(--brand-green-dark); border-color: #c7e6d2; font-size: 11px;">
                            <span class="dot" style="background: var(--brand-green);"></span>
                            Best for {{ $nextSeason }}
                        </span>
                    @endif
                    @if(!empty($recommendationsGeneratedAt))
                        <span style="font-size: 10px; color: var(--slate-400);">
                            <i class="bi bi-clock-history"></i>
                            Generated {{ $recommendationsGeneratedAt->diffForHumans() }}
                        </span>
                    @endif
                </div>
            </div>

            <p style="font-size: 13px; color: var(--slate-500); margin-bottom: 20px;">
                Based on <strong style="color: var(--slate-700);">{{ $focusFarm->name }}</strong>'s soil, weather, and past seasons, these varieties are predicted to perform well next season:
            </p>

            @if(count($recommendations) > 0)
                <div class="row g-3">
                    @foreach($recommendations as $i => $rec)
                        @php
                            $medals = ['🥇', '🥈', '🥉'];
                            $cavanHa  = t_ha_to_cavan_ha((float) $rec['yield']);
                            $totCavan = cavan_total((float) $rec['yield'], (float) $focusFarm->land_area_ha);
                            $isHybrid = ($rec['variety_classification'] ?? '') === 'Hybrid';
                        @endphp

                        <div class="col-12 col-sm-6 col-lg-4">
                            <a href="{{ route('farmer.rice-varieties.show', $rec['variety_id']) }}"
                               class="text-decoration-none d-block h-100"
                               style="color: inherit;">
                                <div class="card-custom h-100 d-flex flex-column mb-0"
                                     style="border: 1px solid var(--slate-200); border-top: 4px solid var(--brand-green); transition: var(--transition-smooth);"
                                     onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='var(--shadow-md)';"
                                     onmouseout="this.style.transform='none'; this.style.boxShadow='var(--shadow-sm)';">

                                    {{-- Medal + Classification --}}
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span style="font-size: 26px; line-height: 1;">{{ $medals[$i] ?? '🌾' }}</span>
                                        <span class="badge-status"
                                              style="background: {{ $isHybrid ? '#eef2ff' : 'var(--brand-green-light)' }};
                                                     color: {{ $isHybrid ? '#3730a3' : 'var(--brand-green-dark)' }};
                                                     border-color: {{ $isHybrid ? '#c7d2fe' : '#c7e6d2' }};">
                                            <span class="dot" style="background: {{ $isHybrid ? '#3730a3' : 'var(--brand-green)' }};"></span>
                                            {{ $rec['variety_classification'] }}
                                        </span>
                                    </div>

                                    {{-- Name --}}
                                    <div style="font-weight: 700; font-size: 15px; color: var(--slate-900); margin-bottom: 8px; line-height: 1.3;">
                                        {{ $rec['variety_name'] }}
                                    </div>

                                    {{-- Yield --}}
                                    <div class="mt-auto pt-3">
                                        <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.5px; margin-bottom: 2px;">
                                            Estimated yield
                                        </div>
                                        <div style="font-size: 26px; font-weight: 800; color: var(--brand-green); line-height: 1.1; letter-spacing: -0.5px;">
                                            {{ $cavanHa }}
                                            <small style="font-size: 13px; font-weight: 600; color: var(--brand-green-mid);">cavan/ha</small>
                                        </div>
                                        <div style="font-size: 11px; color: var(--slate-400); margin-top: 2px;">
                                            ({{ number_format($rec['yield'], 2) }} t/ha)
                                        </div>

                                        <div style="margin-top: 12px; padding-top: 10px; border-top: 1px dashed var(--slate-200); font-size: 11px; color: var(--slate-600);">
                                            From your {{ number_format($focusFarm->land_area_ha, 2) }} ha:<br>
                                            <strong style="color: var(--slate-800); font-size: 13px;">
                                                ≈ {{ number_format($totCavan, 0) }} cavan
                                            </strong>
                                        </div>

                                        <div class="mt-3 d-flex align-items-center gap-1" style="font-size: 12px; color: var(--brand-green); font-weight: 600;">
                                            <span>View variety</span>
                                            <i class="bi bi-arrow-right"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 text-end">
                    <a href="{{ route('farmer.farms.show', $focusFarm->id) }}" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-arrow-right"></i> View Farm Details
                    </a>
                </div>
            @else
                <div class="alert-custom alert-custom-success mb-0" style="font-size: 13px;">
                    <i class="bi bi-info-circle-fill alert-icon"></i>
                    <div class="alert-content">
                        Recommendations will appear once you have at least one season recorded on this farm.
                    </div>
                </div>
            @endif
        </div>
    </div>

</div>

@endif

@endsection