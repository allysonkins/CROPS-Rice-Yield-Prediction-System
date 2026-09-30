@extends('layouts.app')

@section('title', 'Dashboard')

@php
    $isVerified = auth()->user()->verified_by_cao_at !== null;

    $hour = (int) now()->format('H');
    if ($hour < 12)      $greeting = 'Magandang umaga';
    elseif ($hour < 18)  $greeting = 'Magandang hapon';
    else                 $greeting = 'Magandang gabi';

    $hasIncomeData  = ($incomeSeasonLabel && ($incomeActualCavan > 0 || $incomeExpectedCavan > 0));
    $incomeCavan    = $incomeActualCavan > 0 ? $incomeActualCavan : $incomeExpectedCavan;
    $incomePesos    = $incomeCavan * 50 * $palayPricePerKg;
@endphp

@section('content')

{{-- ═══════════════ GREETING + WEATHER ═══════════════ --}}
@php
    // Pick an icon from the weather description
    $weatherIcon = 'cloud-sun';
    $weatherDesc = strtolower($weather['description'] ?? '');
    if ($weather) {
        if (str_contains($weatherDesc, 'thunder') || str_contains($weatherDesc, 'storm')) $weatherIcon = 'cloud-lightning-rain';
        elseif (str_contains($weatherDesc, 'drizzle'))                                     $weatherIcon = 'cloud-drizzle';
        elseif (str_contains($weatherDesc, 'rain'))                                        $weatherIcon = 'cloud-rain';
        elseif (str_contains($weatherDesc, 'snow'))                                        $weatherIcon = 'cloud-snow';
        elseif (str_contains($weatherDesc, 'mist') || str_contains($weatherDesc, 'fog'))   $weatherIcon = 'cloud-fog2';
        elseif (str_contains($weatherDesc, 'few clouds') || str_contains($weatherDesc, 'scattered')) $weatherIcon = 'cloud-sun';
        elseif (str_contains($weatherDesc, 'cloud') || str_contains($weatherDesc, 'overcast'))       $weatherIcon = 'clouds';
        elseif (str_contains($weatherDesc, 'clear'))                                       $weatherIcon = 'sun';
    }
@endphp

<div class="card-custom mb-3" style="border-top: 4px solid var(--brand-green);">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div style="min-width: 0; flex: 1;">
            <div style="font-size: 12px; color: var(--slate-500); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">
                {{ now()->format('l · F j, Y') }}
            </div>
            <h4 style="font-weight: 800; color: var(--slate-900); margin: 6px 0 4px; letter-spacing: -0.5px;">
                {{ $greeting }}, {{ explode(' ', $user->name)[0] }}! 🌾
            </h4>
            <div style="font-size: 13px; color: var(--slate-500);">
                @if($thisSeason)
                    May <strong style="color: var(--brand-green);">{{ $thisSeason->riceVariety->name }}</strong>
                    kang tumutubo sa <strong>{{ $focusFarm->name }}</strong>.
                @else
                    Wala kang aktibong pananim sa ngayon.
                @endif
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-stretch gap-2">

            {{-- WEATHER WIDGET --}}
            @if($weather)
                <div class="d-flex align-items-center gap-3 px-3 py-2" style="background: linear-gradient(135deg, #eef7ff 0%, #f7fbff 100%); border-radius: 10px; border: 1px solid #c7e2f5; min-width: 260px;">
                    <div style="width: 42px; height: 42px; background: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid #c7e2f5;">
                        <i class="bi bi-{{ $weatherIcon }}" style="color: #0284c7; font-size: 22px;"></i>
                    </div>
                    <div style="line-height: 1.2;">
                        <div style="display: flex; align-items: baseline; gap: 6px;">
                            <span style="font-size: 20px; font-weight: 800; color: #0c4a6e;">
                                {{ $weather['temperature'] ?? '—' }}°C
                            </span>
                            <span style="font-size: 11px; color: #0369a1; font-weight: 600;">
                                {{ $weather['description'] ?? '' }}
                            </span>
                        </div>
                        <div style="font-size: 10px; color: #0369a1; margin-top: 4px; display: flex; flex-wrap: wrap; gap: 8px;">
                            <span><i class="bi bi-droplet"></i> {{ $weather['humidity'] ?? '—' }}%</span>
                            <span><i class="bi bi-cloud-rain"></i> {{ $weather['rainfall'] ?? 0 }} mm</span>
                            <span><i class="bi bi-wind"></i> {{ $weather['wind_speed'] ?? '—' }} km/h</span>
                        </div>
                    </div>
                </div>
            @else
                <div class="d-flex align-items-center gap-2 px-3 py-2" style="background: var(--slate-50); border-radius: 10px; border: 1px solid var(--slate-200);">
                    <i class="bi bi-cloud-slash" style="color: var(--slate-400); font-size: 18px;"></i>
                    <div style="font-size: 11px; color: var(--slate-500);">Hindi makuha ang panahon</div>
                </div>
            @endif

            {{-- FOCUS FARM PILL --}}
            @if($focusFarm)
                <div class="d-flex align-items-center gap-2 px-3 py-2" style="background: var(--slate-50); border-radius: 10px; border: 1px solid var(--slate-200);">
                    <i class="bi bi-geo-alt-fill" style="color: var(--brand-green);"></i>
                    <div>
                        <div style="font-size: 11px; color: var(--slate-500); font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px;">Focus Farm</div>
                        <div style="font-size: 13px; font-weight: 700; color: var(--slate-800);">
                            {{ $focusFarm->name }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ═══════════════ FARM SWITCHER ═══════════════ --}}
@if($farms->count() > 1)
    <div class="card-custom mb-3" style="padding: 12px 16px;">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span style="font-size: 11px; font-weight: 700; color: var(--slate-500); text-transform: uppercase; letter-spacing: 0.4px;">
                <i class="bi bi-geo-alt"></i> Buksan ang:
            </span>
            @foreach($farms as $f)
                <a href="{{ request()->fullUrlWithQuery(['farm' => $f->id]) }}"
                   class="btn btn-sm {{ $f->id === $focusFarm->id ? 'btn-success' : 'btn-outline-secondary' }}"
                   style="border-radius: 8px; font-size: 12px; font-weight: 600;">
                    {{ $f->name }}
                    <span class="badge bg-light text-dark ms-1" style="font-size: 10px;">{{ $f->farm_records_count }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endif

{{-- ═══════════════ STAT CARDS ═══════════════ --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="border-left-color: var(--brand-green);">
            <div class="stat-info">
                <div class="label">Aktibong Pananim</div>
                <div class="value" style="font-size: 24px;">
                    {{ $thisSeason ? 1 : 0 }}
                    @if($farms->count() > 1)
                        <span style="font-size: 11px; color: var(--slate-400); font-weight: 500;">sa {{ $focusFarm->name }}</span>
                    @endif
                </div>
                <div class="text-muted small">
                    {{ $thisSeason ? $thisSeason->riceVariety->name : 'Walang tumutubo' }}
                </div>
            </div>
            <div class="stat-icon"><i class="bi bi-flower1" style="color: var(--brand-green);"></i></div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stat-card" style="border-left-color: #4f46e5;">
            <div class="stat-info">
                <div class="label">Tantyang Ani</div>
                <div class="value" style="font-size: 22px;">
                    @if($thisPred && $thisPred->predicted_yield_tons_ha)
                        {{ number_format(t_ha_to_cavan_ha((float) $thisPred->predicted_yield_tons_ha), 0) }}
                        <span style="font-size: 11px; color: var(--slate-400);">cav/ha</span>
                    @else
                        <span style="font-size: 16px; color: var(--slate-400);">—</span>
                    @endif
                </div>
                <div class="text-muted small">
                    @if($expectedTons)
                        ≈ {{ number_format($expectedTons, 2) }} t sa {{ $focusFarm->name }}
                    @else
                        Wala pang tantya
                    @endif
                </div>
            </div>
            <div class="stat-icon"><i class="bi bi-cpu" style="color: #4f46e5;"></i></div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stat-card" style="border-left-color: var(--brand-gold);">
            <div class="stat-info">
                <div class="label">Huling Ani</div>
                <div class="value" style="font-size: 22px;">
                    @if($lastHarvest && $lastHarvest->actual_yield_tons_ha)
                        {{ number_format(t_ha_to_cavan_ha((float) $lastHarvest->actual_yield_tons_ha), 0) }}
                        <span style="font-size: 11px; color: var(--slate-400);">cav/ha</span>
                    @else
                        <span style="font-size: 16px; color: var(--slate-400);">—</span>
                    @endif
                </div>
                <div class="text-muted small">
                    {{ $lastHarvest ? $lastHarvest->season . ' ' . $lastHarvest->year : 'Wala pang naitala' }}
                </div>
            </div>
            <div class="stat-icon"><i class="bi bi-basket-fill" style="color: var(--brand-gold);"></i></div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stat-card" style="border-left-color: #059669;">
            <div class="stat-info">
                <div class="label">Tantyang Kita</div>
                <div class="value" style="font-size: 20px;">
                    @if($hasIncomeData)
                        ₱{{ number_format($incomePesos, 0) }}
                    @else
                        <span style="font-size: 16px; color: var(--slate-400);">—</span>
                    @endif
                </div>
                <div class="text-muted small">
                    @if($hasIncomeData)
                        {{ $incomeSeasonLabel }} {{ $incomeSeasonYear }}
                        · {{ $incomeFarmCount }} bukid{{ $incomeFarmCount !== 1 ? 'an' : '' }}
                        <br>
                        <span style="font-size: 10px; color: var(--slate-400);">
                            @ ₱{{ $palayPricePerKg }}/kg palay
                        </span>
                    @else
                        @ ₱{{ $palayPricePerKg }}/kg palay
                    @endif
                </div>
            </div>
            <div class="stat-icon"><i class="bi bi-cash-coin" style="color: #059669;"></i></div>
        </div>
    </div>
</div>

{{-- ═══════════════ RICE VARIETY RECOMMENDATIONS ═══════════════ --}}
@if(!empty($recommendations))
    <div class="card-custom mb-3" style="border-left: 4px solid #4f46e5;">
        <div class="card-title" style="font-size: 14px;">
            <i class="bi bi-stars" style="color: #4f46e5;"></i>
            Inirerekomendang Variety
            <span class="badge-status ms-auto" style="background: var(--slate-100); color: var(--slate-700); border-color: var(--slate-200); font-size: 11px;">
                Para sa {{ $nextSeason }}
            </span>
        </div>

        <p style="font-size: 12px; color: var(--slate-600); margin: 0 0 16px;">
            Batay sa mga naunang panahon sa <strong>{{ $focusFarm->name }}</strong> at kasalukuyang kondisyon,
            ito ang tatlong pinakamainam na variety para sa susunod na panahon.
        </p>

        <div class="row g-2">
            @foreach($recommendations as $idx => $rec)
                @php
                    $isTop = $idx === 0;
                    $recCavan   = isset($rec['yield']) ? t_ha_to_cavan_ha((float) $rec['yield']) : null;
                    $recLoCavan = !empty($rec['yield_lower']) ? t_ha_to_cavan_ha((float) $rec['yield_lower']) : null;
                    $recHiCavan = !empty($rec['yield_upper']) ? t_ha_to_cavan_ha((float) $rec['yield_upper']) : null;
                @endphp
                <div class="col-12 col-md-4">
                    <div style="padding: 14px 16px; border-radius: 12px; border: {{ $isTop ? '2px solid #4f46e5' : '1px solid var(--slate-200)' }}; background: {{ $isTop ? '#eef2ff' : 'var(--slate-50)' }}; position: relative; height: 100%;">
                        @if($isTop)
                            <span style="position: absolute; top: -8px; left: 12px; padding: 2px 8px; background: #4f46e5; color: white; border-radius: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px;">
                                Pinakamainam
                            </span>
                        @endif

                        <div style="font-size: 14px; font-weight: 800; color: var(--slate-900); margin-top: {{ $isTop ? '4px' : '0' }};">
                            {{ $rec['variety_name'] ?? 'N/A' }}
                        </div>
                        <div style="font-size: 11px; color: var(--slate-500); margin-top: 2px;">
                            {{ $rec['variety_classification'] ?? '' }}
                        </div>

                        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed {{ $isTop ? '#c7d2fe' : 'var(--slate-200)' }};">
                            <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.4px;">
                                Tantyang Ani
                            </div>
                            <div style="font-size: 22px; font-weight: 800; color: {{ $isTop ? '#3730a3' : 'var(--brand-green-dark)' }}; line-height: 1.1; margin-top: 2px;">
                                {{ $recCavan !== null ? number_format($recCavan, 0) : '—' }}
                                <span style="font-size: 12px; font-weight: 600;">cavan/ha</span>
                            </div>
                            @if($recLoCavan !== null && $recHiCavan !== null)
                                <div style="font-size: 10px; color: var(--slate-500); margin-top: 2px;">
                                    80% range: {{ number_format($recLoCavan, 0) }}–{{ number_format($recHiCavan, 0) }} cavan/ha
                                </div>
                            @endif
                            @if(!empty($rec['confidence']))
                                <div style="display: inline-flex; align-items: center; padding: 2px 8px; background: white; border: 1px solid {{ $isTop ? '#c7d2fe' : 'var(--slate-200)' }}; border-radius: 999px; font-size: 10px; font-weight: 700; color: {{ $isTop ? '#3730a3' : 'var(--slate-600)' }}; margin-top: 8px;">
                                    <i class="bi bi-activity" style="margin-right: 4px;"></i>
                                    {{ number_format($rec['confidence'] * 100, 0) }}% kumpiyansa
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2" style="font-size: 11px; color: var(--slate-500); line-height: 1.5;">
            <div>
                <i class="bi bi-info-circle"></i>
                Gabay lamang ito — kausapin pa rin ang inyong <strong>CAO technician</strong> bago magdesisyon.
            </div>
            @if($recommendationsGeneratedAt)
                <div>
                    Na-update {{ $recommendationsGeneratedAt->diffForHumans() }}
                </div>
            @endif
        </div>
    </div>
@endif

{{-- ═══════════════ THIS SEASON / LAST HARVEST ═══════════════ --}}
<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card-custom h-100">
            <div class="card-title" style="font-size: 14px;">
                <i class="bi bi-flower1" style="color: var(--brand-green);"></i>
                Kasalukuyang Pananim
            </div>

            @if($thisSeason)
                @php
                    $class = null;
                    if ($thisPred && $thisSeason->riceVariety && $thisPred->predicted_yield_tons_ha !== null) {
                        $methodYield = $thisSeason->riceVariety->getYieldForMethod($thisSeason->seeding_method ?? 'Transplanted');
                        $avgTons = $methodYield->avg ?? $thisSeason->riceVariety->avg_yield;
                        if ($avgTons && $avgTons > 0) {
                            $ratio = $thisPred->predicted_yield_tons_ha / $avgTons;
                            if     ($ratio >= 1.125) $class = 'High';
                            elseif ($ratio >= 0.875) $class = 'Medium';
                            else                     $class = 'Low';
                        }
                    }
                    $badge = match($class) {
                        'High' => 'high',
                        'Low'  => 'low',
                        'Medium' => 'medium',
                        default => 'medium',
                    };
                    $predCavan = $thisPred && $thisPred->predicted_yield_tons_ha !== null
                        ? t_ha_to_cavan_ha((float) $thisPred->predicted_yield_tons_ha) : null;

                    // Total cavan for the whole farm
                    $farmArea = (float) ($focusFarm->land_area_ha ?? 1.0);
                    $totalCavan = $predCavan !== null ? $predCavan * $farmArea : null;
                @endphp

                <div style="padding: 14px 16px; border-radius: 12px; border: 1px solid var(--slate-200); background: white;">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div style="min-width: 0;">
                            <div style="font-size: 15px; font-weight: 800; color: var(--slate-900); letter-spacing: -0.2px;">
                                {{ $thisSeason->riceVariety->name ?? 'N/A' }}
                            </div>
                            <div style="font-size: 11px; color: var(--slate-500); margin-top: 2px;">
                                {{ $focusFarm->name }} · {{ $thisSeason->season }} {{ $thisSeason->year }}
                            </div>
                        </div>
                        @if($class)
                            <span class="badge-status {{ $badge }}" style="font-size: 10px; flex-shrink: 0;">
                                <span class="dot"></span> {{ $class }}
                            </span>
                        @endif
                    </div>

                    <div class="row g-2 mt-3">
                        <div class="col-6">
                            <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700;">Tantya kada ektarya</div>
                            <div style="font-size: 20px; font-weight: 800; color: var(--brand-green-dark); line-height: 1.1;">
                                @if($predCavan !== null)
                                    {{ number_format($predCavan, 0) }}
                                    <span style="font-size: 10px; font-weight: 600;">cav/ha</span>
                                @else
                                    <span style="font-size: 12px; color: var(--slate-400);">—</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-6">
                            <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700;">
                                Kabuuan ({{ number_format($farmArea, 2) }} ha)
                            </div>
                            <div style="font-size: 20px; font-weight: 800; color: var(--brand-green-dark); line-height: 1.1;">
                                @if($totalCavan !== null)
                                    {{ number_format($totalCavan, 0) }}
                                    <span style="font-size: 10px; font-weight: 600;">cavan</span>
                                @else
                                    <span style="font-size: 12px; color: var(--slate-400);">—</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($isVerified)
                        <button type="button"
                                class="btn btn-sm btn-success w-100 mt-3"
                                onclick="openFarmerHarvestModal(this)"
                                data-record-id="{{ $thisSeason->id }}"
                                data-farm="{{ $focusFarm->name }}"
                                data-variety="{{ $thisSeason->riceVariety->name }}"
                                data-season="{{ $thisSeason->season }} {{ $thisSeason->year }}"
                                data-predicted-tons="{{ $thisPred && $thisPred->predicted_yield_tons_ha ? number_format($thisPred->predicted_yield_tons_ha, 2, '.', '') : '' }}"
                                data-predicted-cavan="{{ $predCavan !== null ? number_format($predCavan, 0, '.', '') : '' }}">
                            <i class="bi bi-basket-fill"></i> I-record ang Ani
                        </button>
                    @endif
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-flower3" style="font-size: 42px; color: var(--slate-300);"></i>
                    <h6 class="mt-3 mb-1" style="color: var(--slate-700); font-weight: 700;">
                        Wala kang pananim sa {{ $focusFarm->name }}
                    </h6>
                    <p style="font-size: 13px; color: var(--slate-500); margin-bottom: 16px;">
                        Kapag nagtanim ka na, lalabas dito ang iyong pananim at ang tantyang ani.
                    </p>
                    @if($isVerified)
                        <a href="{{ route('farmer.farm-records.index') }}" class="btn btn-success">
                            <i class="bi bi-plus-circle"></i> I-record ang Pananim
                        </a>
                    @else
                        <button class="btn btn-success" disabled title="Available after CAO verification">
                            <i class="bi bi-lock"></i> Hintayin ang CAO verification
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card-custom h-100">
            <div class="card-title" style="font-size: 14px;">
                <i class="bi bi-clock-history"></i>
                Huling Ani
            </div>

            @if($lastHarvest)
                @php
                    $actualCavan = t_ha_to_cavan_ha((float) $lastHarvest->actual_yield_tons_ha);
                    $predCavan   = $lastPred && $lastPred->predicted_yield_tons_ha !== null
                        ? t_ha_to_cavan_ha((float) $lastPred->predicted_yield_tons_ha)
                        : null;
                    $deltaCavan = $predCavan !== null ? ($actualCavan - $predCavan) : null;
                    $deltaColor = $deltaCavan === null ? 'var(--slate-400)' : ($deltaCavan > 0 ? 'var(--brand-green)' : ($deltaCavan < 0 ? '#991b1b' : 'var(--slate-400)'));
                    $deltaIcon  = $deltaCavan === null ? 'dash' : ($deltaCavan > 0.5 ? 'arrow-up' : ($deltaCavan < -0.5 ? 'arrow-down' : 'dash'));
                @endphp

                <div style="padding: 14px 16px; border-radius: 12px; border: 1px solid var(--slate-200); background: white;">
                    <div class="mb-2">
                        <div style="font-size: 15px; font-weight: 800; color: var(--slate-900); letter-spacing: -0.2px;">
                            {{ $lastHarvest->riceVariety->name ?? 'N/A' }}
                        </div>
                        <div style="font-size: 11px; color: var(--slate-500); margin-top: 2px;">
                            {{ $focusFarm->name }} · {{ $lastHarvest->season }} {{ $lastHarvest->year }}
                        </div>
                    </div>

                    <div class="row g-2 mt-3">
                        <div class="col-4">
                            <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700;">Aktwal</div>
                            <div style="font-size: 18px; font-weight: 800; color: var(--brand-green-dark); line-height: 1.1;">
                                {{ number_format($actualCavan, 0) }}
                                <span style="font-size: 10px; font-weight: 600;">cav/ha</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700;">Tantya</div>
                            <div style="font-size: 18px; font-weight: 800; color: var(--slate-700); line-height: 1.1;">
                                @if($predCavan !== null)
                                    {{ number_format($predCavan, 0) }}
                                    <span style="font-size: 10px; font-weight: 600;">cav/ha</span>
                                @else
                                    <span style="font-size: 12px; color: var(--slate-400);">—</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-4">
                            <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700;">Pagkakaiba</div>
                            <div style="font-size: 18px; font-weight: 800; color: {{ $deltaColor }}; line-height: 1.1;">
                                <i class="bi bi-{{ $deltaIcon }}"></i>
                                @if($deltaCavan !== null)
                                    {{ $deltaCavan > 0 ? '+' : '' }}{{ number_format($deltaCavan, 0) }}
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($totalTons)
                        <div class="mt-3" style="font-size: 11px; color: var(--slate-500);">
                            <i class="bi bi-info-circle"></i>
                            Kabuuang ani: <strong>{{ number_format($totalTons, 2) }} t</strong>
                            sa {{ number_format($focusFarm->land_area_ha, 2) }} ha
                        </div>
                    @endif
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 42px; color: var(--slate-300);"></i>
                    <h6 class="mt-3 mb-1" style="color: var(--slate-700); font-weight: 700;">
                        Wala pang naitalang ani
                    </h6>
                    <p style="font-size: 13px; color: var(--slate-500); margin: 0;">
                        Kapag nag-ani ka na, makikita dito ang resulta at maihahambing sa tantya.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ═══════════════ HARVEST MODAL ═══════════════ --}}
@if($isVerified)
    <div class="modal fade" id="farmerHarvestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--brand-green); color: white;">
                    <h5 class="modal-title"><i class="bi bi-basket-fill"></i> I-record ang Ani</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="farmerHarvestForm">
                    @csrf
                    <input type="hidden" name="actual_yield_tons_ha" id="farmerActualYieldTons">

                    <div class="modal-body">
                        <div id="farmerHarvestError"></div>

                        <div class="p-3 mb-3" style="background: var(--slate-50); border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
                            <div style="font-size: 11px; text-transform: uppercase; color: var(--slate-500); font-weight: 700;">Season</div>
                            <div id="farmerHarvestFarm" style="font-size: 16px; font-weight: 700; color: var(--brand-green-dark);"></div>
                            <div id="farmerHarvestMeta" class="text-muted" style="font-size: 13px;"></div>
                        </div>

                        <div id="farmerHarvestSuggestion"></div>

                        <div>
                            <label class="form-label fw-semibold">
                                Actual Yield (cavan/ha) <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="0.1" min="0" max="400"
                                   id="farmerActualYieldInput"
                                   class="form-control form-control-lg" placeholder="e.g., 90" required>
                            <small class="text-muted">
                                Ilang cavan ang inani mo per hectare? (1 cavan = 50 kg · 1 ton = 20 cavan)
                            </small>
                            <div id="farmerCavanPreview" style="font-size: 12px; color: var(--slate-500); margin-top: 6px;"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="farmerHarvestBtn">
                            <i class="bi bi-basket-fill"></i> Confirm Harvest
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@endsection

@push('scripts')
@if($isVerified)
<script>
    const CAVAN_TO_TONS = 0.05;
    function cavanHaToTonsHa(cavanHa) { return cavanHa * CAVAN_TO_TONS; }

    function openFarmerHarvestModal(btn) {
        const id             = btn.dataset.recordId;
        const farm           = btn.dataset.farm;
        const variety        = btn.dataset.variety;
        const season         = btn.dataset.season;
        const predictedTons  = btn.dataset.predictedTons;
        const predictedCavan = btn.dataset.predictedCavan;

        const form = document.getElementById('farmerHarvestForm');
        form.action = '/farmer/farm-records/' + id + '/mark-harvested';

        document.getElementById('farmerHarvestError').innerHTML = '';
        document.getElementById('farmerHarvestFarm').textContent = farm;
        document.getElementById('farmerHarvestMeta').textContent = variety + ' · ' + season;

        const suggestion = document.getElementById('farmerHarvestSuggestion');
        if (predictedCavan && predictedCavan !== '') {
            suggestion.innerHTML = `
                <div class="p-3 mb-3" style="background: var(--brand-green-light); border: 1px solid #a7f3d0; border-radius: var(--radius-md);">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <div style="font-size: 11px; text-transform: uppercase; color: var(--brand-green-dark); font-weight: 700;">
                                <i class="bi bi-cpu"></i> Tantya ng System
                            </div>
                            <div style="font-size: 22px; font-weight: 800; color: var(--brand-green-dark);">
                                ${predictedCavan} <span style="font-size: 14px;">cavan/ha</span>
                            </div>
                            <div style="font-size: 11px; color: var(--brand-green-dark); opacity: 0.8;">
                                ≈ ${predictedTons} t/ha
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-success" onclick="useFarmerSuggestedYield('${predictedCavan}')">
                            <i class="bi bi-arrow-down-circle"></i> Gamitin ito
                        </button>
                    </div>
                </div>`;
        } else {
            suggestion.innerHTML = '';
        }

        document.getElementById('farmerActualYieldInput').value = '';
        document.getElementById('farmerActualYieldTons').value = '';
        document.getElementById('farmerCavanPreview').innerHTML = '';

        new bootstrap.Modal(document.getElementById('farmerHarvestModal')).show();
    }

    function useFarmerSuggestedYield(cavanHa) {
        const input = document.getElementById('farmerActualYieldInput');
        if (input) {
            input.value = cavanHa;
            input.dispatchEvent(new Event('input'));
            input.focus();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const cavanInput = document.getElementById('farmerActualYieldInput');
        const tonsHidden = document.getElementById('farmerActualYieldTons');
        const preview    = document.getElementById('farmerCavanPreview');

        if (cavanInput) {
            cavanInput.addEventListener('input', function() {
                const cavanHa = parseFloat(this.value);
                if (!isNaN(cavanHa) && cavanHa >= 0) {
                    const tonsHa = cavanHaToTonsHa(cavanHa);
                    tonsHidden.value = tonsHa.toFixed(4);
                    preview.innerHTML = `<i class="bi bi-arrow-right-circle"></i> Katumbas ng <strong>${tonsHa.toFixed(2)} t/ha</strong>`;
                } else {
                    tonsHidden.value = '';
                    preview.innerHTML = '';
                }
            });
        }
    });

    document.getElementById('farmerHarvestForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const btn = document.getElementById('farmerHarvestBtn');
        const original = btn.innerHTML;

        const cavanVal = parseFloat(document.getElementById('farmerActualYieldInput')?.value);
        if (!isNaN(cavanVal) && cavanVal >= 0) {
            document.getElementById('farmerActualYieldTons').value = cavanHaToTonsHa(cavanVal).toFixed(4);
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
        document.getElementById('farmerHarvestError').innerHTML = '';

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('farmerHarvestModal')).hide();
                location.reload();
            } else {
                let msg = data.error || 'Failed to save.';
                if (data.errors) msg = Object.values(data.errors).flat().join('<br>');
                document.getElementById('farmerHarvestError').innerHTML =
                    '<div class="alert alert-danger">' + msg + '</div>';
                btn.disabled = false;
                btn.innerHTML = original;
            }
        })
        .catch(() => {
            document.getElementById('farmerHarvestError').innerHTML =
                '<div class="alert alert-danger">Network error. Please try again.</div>';
            btn.disabled = false;
            btn.innerHTML = original;
        });
    });
</script>
@endif
@endpush

@push('styles')
<style>
    .stat-card { transition: transform 0.15s ease; }
    .stat-card:hover { transform: translateY(-2px); }
</style>
@endpush