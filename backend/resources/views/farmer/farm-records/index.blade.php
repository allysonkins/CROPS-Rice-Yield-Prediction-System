@extends('layouts.app')

@section('title', 'My Seasons')

@php
    $isVerified = auth()->user()->verified_by_cao_at !== null;
    $activeYear = $yearFilter ?? (string) now()->year;
@endphp

@section('content')

{{-- ═══════════ HEADER ═══════════ --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <p class="text-muted mb-0" style="font-size: 13px;">
            Every planting season you have recorded, across all your farms.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        @if($isVerified)
            <button type="button" class="btn btn-success" onclick="openFarmerFarmRecordModal()">
                <i class="bi bi-plus-circle"></i> Add Season
            </button>
        @else
            <button class="btn btn-success" disabled title="Available after CAO verification">
                <i class="bi bi-lock"></i> Add Season
            </button>
        @endif
        <span class="badge bg-secondary">{{ $farmRecords->count() }} Season{{ $farmRecords->count() === 1 ? '' : 's' }}</span>
    </div>
</div>

{{-- ═══════════ STATS ═══════════ --}}
<div class="row g-2 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Total Seasons</div>
                <div class="value">{{ $farmRecords->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-clipboard-data-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Growing</div>
                <div class="value">{{ $farmRecords->where('status', 'Vegetative')->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-flower1"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Harvested</div>
                <div class="value">{{ $farmRecords->where('status', 'Harvested')->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-basket-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Farms</div>
                <div class="value">{{ $farmRecords->pluck('farm_id')->unique()->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-geo-alt-fill"></i></div>
        </div>
    </div>
</div>

{{-- ═══════════ INFO BANNER ═══════════ --}}
<div class="card-custom mb-3" style="padding: 14px 20px;">
    <div class="d-flex align-items-start gap-3 flex-wrap">
        <i class="bi bi-info-circle-fill" style="color: var(--brand-green); font-size: 18px; flex-shrink: 0; margin-top: 2px;"></i>
        <div style="flex: 1; min-width: 260px;">
            <div style="font-size: 13px; font-weight: 700; color: var(--slate-800); margin-bottom: 4px;">
                How to read your yields
            </div>
            <div style="font-size: 12px; color: var(--slate-600); line-height: 1.6;">
                <strong>Predicted</strong> is the estimated yield from the XGBoost model.
                <strong>Actual</strong> is the real yield you recorded after harvest.
                All yields are shown in <strong>cavan per hectare</strong> (1 cavan ≈ 50 kg · 1 ton = 20 cavan).
                Tap a variety name to see its full details.
            </div>
        </div>
    </div>
</div>

{{-- ═══════════ FILTER CONTROLS ═══════════ --}}
<div class="row g-2 mb-4">
    <div class="col-12">
        <div class="card-custom" style="padding: 16px 20px;">

            {{-- ROW 1: Search + Year + Farm + Barangay --}}
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">

                {{-- SEARCH --}}
                <div style="flex: 1; min-width: 240px;">
                    <div class="input-group" style="border-radius: 12px; overflow: hidden; border: 1px solid var(--slate-200);">
                        <span class="input-group-text" style="background: var(--slate-50); border: none; color: var(--slate-400);">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="searchFarmRecord" class="form-control"
                               placeholder="Search by farm, variety, season..."
                               style="border: none; background: var(--slate-50); font-size: 13px;">
                    </div>
                </div>

                {{-- YEAR (server-side) --}}
                <div style="min-width: 140px;">
                    <select id="filterYear" class="form-select"
                            onchange="window.location.href = this.value"
                            title="Filter by year"
                            style="border-radius: 12px; border: 1px solid var(--slate-200); background: var(--slate-50); font-size: 13px; padding: 0.45rem 2rem 0.45rem 1rem; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.7rem; cursor: pointer; appearance: none;">
                        <option value="{{ request()->fullUrlWithQuery(['year' => 'all']) }}"
                                {{ $activeYear === 'all' ? 'selected' : '' }}>
                            All Years
                        </option>
                        @foreach($availableYears as $yr)
                            <option value="{{ request()->fullUrlWithQuery(['year' => $yr]) }}"
                                    {{ (string) $activeYear === (string) $yr ? 'selected' : '' }}>
                                {{ $yr }}{{ (int) $yr === (int) now()->year ? ' (Current)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- FARM --}}
                <div style="min-width: 180px;">
                    <select id="filterFarm" class="form-select"
                            title="Filter by farm"
                            style="border-radius: 12px; border: 1px solid var(--slate-200); background: var(--slate-50); font-size: 13px; padding: 0.45rem 2rem 0.45rem 1rem; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.7rem; cursor: pointer; appearance: none; width: auto; min-width: 170px;">
                        <option value="all">All Farms</option>
                        @foreach($farmRecords->pluck('farm.name')->unique()->filter()->values() as $farmName)
                            <option value="{{ $farmName }}">{{ $farmName }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- BARANGAY --}}
                <div style="min-width: 160px;">
                    <select id="filterBarangay" class="form-select"
                            title="Filter by barangay"
                            style="border-radius: 12px; border: 1px solid var(--slate-200); background: var(--slate-50); font-size: 13px; padding: 0.45rem 2rem 0.45rem 1rem; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.7rem; cursor: pointer; appearance: none; width: auto; min-width: 150px;">
                        <option value="all">All Barangays</option>
                        @foreach($farmRecords->pluck('farm.barangay')->unique()->filter()->values() as $barangay)
                            <option value="{{ $barangay }}">{{ $barangay }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- RESET --}}
                <button type="button" id="resetFiltersBtn" class="btn btn-sm btn-outline-secondary"
                        style="border-radius: 10px; font-size: 12px; display: none;"
                        title="Clear all filters">
                    <i class="bi bi-x-circle"></i> Reset
                </button>
            </div>

            {{-- ROW 2: Pills + Advanced filters --}}
            <div class="d-flex flex-wrap align-items-center gap-3 pt-3" style="border-top: 1px dashed var(--slate-200);">

                {{-- SEASON GROUP --}}
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="font-size: 10px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase;">Season</span>
                    <div class="d-flex" style="background: var(--slate-100); border-radius: 12px; padding: 4px; gap: 2px;">
                        <button class="filter-btn season-btn active" data-season="all" style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: white; color: var(--slate-900); box-shadow: 0 1px 2px rgba(0,0,0,0.05); white-space: nowrap;">All</button>
                        <button class="filter-btn season-btn"        data-season="Dry" style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: transparent; color: var(--slate-600); white-space: nowrap;">Dry</button>
                        <button class="filter-btn season-btn"        data-season="Wet" style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: transparent; color: var(--slate-600); white-space: nowrap;">Wet</button>
                    </div>
                </div>

                <div style="width: 1px; height: 28px; background: var(--slate-200);"></div>

                {{-- STATUS GROUP --}}
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="font-size: 10px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase;">Status</span>
                    <div class="d-flex" style="background: var(--slate-100); border-radius: 12px; padding: 4px; gap: 2px;">
                        <button class="filter-btn status-btn active" data-status="all"        style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: white; color: var(--slate-900); box-shadow: 0 1px 2px rgba(0,0,0,0.05); white-space: nowrap;">All</button>
                        <button class="filter-btn status-btn"        data-status="Vegetative" style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: transparent; color: var(--slate-600); white-space: nowrap;">Growing</button>
                        <button class="filter-btn status-btn"        data-status="Harvested"  style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: transparent; color: var(--slate-600); white-space: nowrap;">Harvested</button>
                    </div>
                </div>

                <div style="width: 1px; height: 28px; background: var(--slate-200);"></div>

                {{-- PREDICTION FILTER --}}
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="font-size: 10px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase;">Prediction</span>
                    <select id="filterPrediction" class="form-select" title="Filter by prediction availability"
                            style="border-radius: 10px; border: 1px solid var(--slate-200); background: var(--slate-50); font-size: 12px; padding: 0.4rem 2rem 0.4rem 0.85rem; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.65rem center; background-size: 0.65rem; cursor: pointer; appearance: none; width: auto; min-width: 130px;">
                        <option value="all">Any</option>
                        <option value="with">Available</option>
                        <option value="without">Missing</option>
                    </select>
                </div>

                {{-- ACTUAL YIELD FILTER --}}
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="font-size: 10px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase;">Actual Yield</span>
                    <select id="filterActual" class="form-select" title="Filter by actual yield availability"
                            style="border-radius: 10px; border: 1px solid var(--slate-200); background: var(--slate-50); font-size: 12px; padding: 0.4rem 2rem 0.4rem 0.85rem; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.65rem center; background-size: 0.65rem; cursor: pointer; appearance: none; width: auto; min-width: 130px;">
                        <option value="all">Any</option>
                        <option value="with">Recorded</option>
                        <option value="without">Pending</option>
                    </select>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- ═══════════ SEASONS TABLE ═══════════ --}}
<div class="card-custom">
    <div class="card-title">
        <i class="bi bi-table"></i> Planting Seasons
        @if($activeYear !== 'all')
            <span class="badge bg-light text-muted ms-1" style="font-weight: 400; font-size: 11px;">{{ $activeYear }}</span>
        @endif
        <span class="badge bg-light text-muted ms-1" id="visibleCount" style="font-weight: 400; font-size: 10px;">
            {{ $farmRecords->count() }} total
        </span>
    </div>

    @if($farmRecords->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox" style="font-size: 42px; color: var(--slate-300);"></i>
            <h5 class="mt-3 mb-2" style="color: var(--slate-700); font-weight: 700;">
                @if($activeYear !== 'all')
                    No seasons for <strong>{{ $activeYear }}</strong>
                @else
                    No seasons recorded yet
                @endif
            </h5>
            <p class="mb-4" style="font-size: 14px; color: var(--slate-500);">
                @if($activeYear !== 'all')
                    Try switching to a different year or clearing the filter.
                @else
                    Record your first planting season to get a yield prediction.
                @endif
            </p>
            @if($isVerified && $activeYear === 'all')
                <button type="button" class="btn btn-success" onclick="openFarmerFarmRecordModal()">
                    <i class="bi bi-plus-circle"></i> Add My First Season
                </button>
            @endif
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Farm</th>
                        <th>Variety</th>
                        <th>Season / Year</th>
                        <th>
                            Predicted
                            <i class="bi bi-question-circle-fill"
                               style="font-size: 12px; color: var(--slate-400); cursor: help;"
                               data-bs-toggle="tooltip"
                               data-bs-placement="top"
                               data-bs-html="true"
                               title="<div style='text-align:left; font-size:12px; line-height:1.5;'>
                                   <strong>XGBoost prediction</strong><br>
                                   Point yield estimated by the ML model.<br><br>
                                   <strong>Class is variety-relative</strong> — compared against the variety's own average yield for the chosen seeding method.<br><br>
                                   • <strong>High</strong> — ≥ 112.5% of variety average<br>
                                   • <strong>Medium</strong> — 87.5% – 112.5%<br>
                                   • <strong>Low</strong> — below 87.5%
                               </div>"></i>
                        </th>
                        <th>Actual</th>
                        <th>Status</th>
                        @if($isVerified)
                            <th style="min-width: 150px;">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="farmRecordsBody">
                    @foreach($farmRecords as $record)
                        @php
                            $latest = $record->predictions->sortByDesc('created_at')->first();
                            $predTons  = $latest?->predicted_yield_tons_ha;
                            $predCavan = $predTons !== null ? t_ha_to_cavan_ha((float) $predTons) : null;
                            $conf      = $latest?->confidence;

                            $actualTons  = $record->actual_yield_tons_ha;
                            $actualCavan = $actualTons !== null ? t_ha_to_cavan_ha((float) $actualTons) : null;

                            // Variety-relative class
                            $class = null;
                            if ($latest && $record->riceVariety && $predTons !== null) {
                                $methodYield = $record->riceVariety->getYieldForMethod($record->seeding_method ?? 'Transplanted');
                                $avgTons = $methodYield->avg ?? $record->riceVariety->avg_yield;
                                if ($avgTons && $avgTons > 0) {
                                    $ratio = $predTons / $avgTons;
                                    if     ($ratio >= 1.125) $class = 'High';
                                    elseif ($ratio >= 0.875) $class = 'Medium';
                                    else                     $class = 'Low';
                                }
                            }
                            $badge = match($class) {
                                'High' => 'high',
                                'Low'  => 'low',
                                'Medium' => 'medium',
                                default => null,
                            };

                            $hasPrediction = $predCavan !== null;
                            $hasActual     = $actualTons !== null && $actualTons > 0;

                            $barangay   = $record->farm->barangay ?? '';
                            $seasonNorm = str_replace(' Season', '', $record->season ?? '');

                            $searchData = strtolower(
                                ($record->farm->name ?? '') . ' ' .
                                ($record->riceVariety->name ?? '') . ' ' .
                                ($record->season ?? '') . ' ' .
                                ($record->year ?? '') . ' ' .
                                $barangay
                            );
                        @endphp
                        <tr class="record-row"
                            data-search="{{ $searchData }}"
                            data-farm="{{ $record->farm->name ?? '' }}"
                            data-barangay="{{ $barangay }}"
                            data-status="{{ $record->status }}"
                            data-season="{{ $seasonNorm }}"
                            data-has-prediction="{{ $hasPrediction ? '1' : '0' }}"
                            data-has-actual="{{ $hasActual ? '1' : '0' }}">
                            <td class="row-index">{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $record->farm->name ?? 'N/A' }}</strong>
                                <br><small class="text-muted">{{ $barangay }}</small>
                            </td>
                            <td>
                                @if($record->rice_variety_id)
                                    <span style="cursor:pointer; color: var(--brand-green); text-decoration: underline;"
                                          onclick="viewVarietyDetails({{ $record->rice_variety_id }})"
                                          title="Tap to view variety details">
                                        {{ $record->riceVariety->name ?? 'N/A' }}
                                    </span>
                                @else
                                    {{ $record->riceVariety->name ?? 'N/A' }}
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark" style="font-weight: 600;">
                                    {{ $record->year ?? '—' }}
                                </span>
                                <br><small class="text-muted">{{ $record->season ?? '' }}</small>
                            </td>
                            <td>
                                @if($predCavan !== null)
                                    <span class="fw-bold" style="color: var(--brand-green);">
                                        {{ number_format($predCavan, 0) }}
                                    </span>
                                    <small class="text-muted">cavan/ha</small>
                                    <br>
                                    <small class="text-muted" style="font-size: 10px;">
                                        ({{ number_format($predTons, 2) }} t/ha)
                                    </small>
                                    @if($badge)
                                        <br>
                                        <span class="badge-status {{ $badge }}" style="font-size: 10px; margin-top: 4px;">
                                            <span class="dot"></span> {{ $class }}
                                            @if($conf)
                                                · {{ number_format($conf * 100, 0) }}%
                                            @endif
                                        </span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($actualCavan !== null)
                                    <span class="fw-bold" style="color: var(--slate-800);">
                                        {{ number_format($actualCavan, 0) }}
                                    </span>
                                    <small class="text-muted">cavan/ha</small>
                                    <br>
                                    <small class="text-muted" style="font-size: 10px;">
                                        ({{ number_format($actualTons, 2) }} t/ha)
                                    </small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($record->status === 'Harvested')
                                    <span class="badge-status" style="background: var(--brand-green-light); color: var(--brand-green-dark); border-color: #c7e6d2;">
                                        <span class="dot" style="background: var(--brand-green);"></span>
                                        Harvested
                                    </span>
                                @else
                                    <span class="badge-status" style="background: var(--brand-gold-light); color: #92400e; border-color: #fde68a;">
                                        <span class="dot" style="background: var(--brand-gold);"></span>
                                        Growing
                                    </span>
                                @endif
                            </td>
                            @if($isVerified)
                                <td style="white-space: nowrap;">
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="viewFarmerFarmRecord({{ $record->id }})" title="View">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="editFarmerFarmRecord({{ $record->id }})" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    @if($record->status === 'Vegetative')
                                        <button type="button"
                                                class="btn btn-sm btn-success harvest-btn"
                                                data-record-id="{{ $record->id }}"
                                                data-farm="{{ $record->farm->name ?? 'N/A' }}"
                                                data-variety="{{ $record->riceVariety->name ?? 'N/A' }}"
                                                data-season="{{ $record->season }} {{ $record->year }}"
                                                data-predicted-tons="{{ $predTons !== null ? number_format($predTons, 2, '.', '') : '' }}"
                                                data-predicted-cavan="{{ $predCavan !== null ? number_format($predCavan, 0, '.', '') : '' }}"
                                                onclick="openFarmerHarvestModal(this)"
                                                title="Mark Harvested">
                                            <i class="bi bi-basket-fill"></i>
                                        </button>
                                    @endif
                                    <form action="{{ route('farmer.farm-records.destroy', $record->id) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this season record? This cannot be undone.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @endforeach

                    <tr id="noFilterResults" style="display: none;">
                        <td colspan="{{ $isVerified ? 8 : 7 }}" class="text-center py-4 text-muted">
                            <i class="bi bi-search" style="font-size: 28px;"></i>
                            <p class="mt-2 mb-0">No seasons match your filters.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- ═══════════ PAGINATION ═══════════ --}}
        <div id="paginationBar" class="d-none" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding-top: 16px; margin-top: 8px; border-top: 1px solid var(--slate-200);">
            <div class="d-flex align-items-center gap-2" style="font-size: 12px; color: var(--slate-500);">
                <span>Show</span>
                <select id="perPageSelect" class="form-select form-select-sm" style="width: auto; border-radius: 8px; border: 1px solid var(--slate-200); font-size: 12px; padding: 4px 26px 4px 10px; appearance: none; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 8px center; background-size: 10px;">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>entries</span>
                <span id="paginationInfo" style="margin-left: 6px;"></span>
            </div>
            <nav aria-label="Season pagination">
                <ul id="paginationList" class="pagination mb-0" style="display: flex; align-items: center; gap: 4px; list-style: none; padding: 0; margin: 0;"></ul>
            </nav>
        </div>
    @endif
</div>

{{-- ═══════════ MODALS ═══════════ --}}

{{-- Variety Detail Modal — available to ALL farmers --}}
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

@if($isVerified)

{{-- Farm Record modal --}}
<div class="modal fade" id="farmerFarmRecordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--brand-green); color: white;">
                <h5 class="modal-title">
                    <i class="bi bi-clipboard-data-fill"></i>
                    <span id="farmerFarmRecordModalTitle">Add Season</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="overflow: hidden;">
                <div class="text-center py-4" id="farmerFarmRecordModalLoading">
                    <div class="spinner-border text-success" role="status"></div>
                    <p class="mt-2" style="color: var(--slate-500);">Loading form...</p>
                </div>
                <div id="farmerFarmRecordModalContent" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

{{-- View details modal --}}
<div class="modal fade" id="farmerFarmRecordViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--brand-green); color: white;">
                <h5 class="modal-title"><i class="bi bi-eye"></i> Season Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-4" id="farmerViewLoading">
                    <div class="spinner-border text-success" role="status"></div>
                </div>
                <div id="farmerViewContent" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

{{-- Harvest modal --}}
<div class="modal fade" id="farmerHarvestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--brand-green); color: white;">
                <h5 class="modal-title"><i class="bi bi-basket-fill"></i> Mark as Harvested</h5>
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
{{-- ═══════════════════════════════════════════════════════════
     SHARED HELPERS — available to ALL farmers
     ═══════════════════════════════════════════════════════════ --}}
<script>
    // ── Execute <script> tags inside HTML injected via innerHTML ──
    // Browsers do not run scripts inserted this way, so we clone them.
    window.executeInjectedScripts = function(container) {
        if (!container) return;
        container.querySelectorAll('script').forEach(oldScript => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => {
                newScript.setAttribute(attr.name, attr.value);
            });
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    };
</script>

{{-- ═══════════════════════════════════════════════════════════
     VARIETY DETAIL MODAL — available to ALL farmers
     ═══════════════════════════════════════════════════════════ --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el, { container: 'body' });
        });
    });

    function viewVarietyDetails(id) {
        const loading = document.getElementById('varietyDetailLoading');
        const content = document.getElementById('varietyDetailContent');
        if (!loading || !content) return;

        loading.style.display = 'block';
        loading.innerHTML =
            '<div class="spinner-border text-success" role="status" style="width: 28px; height: 28px;"></div>' +
            '<p class="mt-2 mb-0" style="color: var(--slate-500); font-size: 12px;">Loading...</p>';
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
        .catch(() => {
            loading.innerHTML =
                '<div class="text-center py-3">' +
                '<i class="bi bi-exclamation-triangle-fill" style="font-size: 28px; color: var(--brand-danger);"></i>' +
                '<p class="mt-2 mb-0" style="color: var(--brand-danger); font-size: 12px;">Failed to load variety.</p>' +
                '</div>';
        });
    }
</script>

{{-- ═══════════════════════════════════════════════════════════
     FILTERING + PAGINATION — available to ALL farmers
     ═══════════════════════════════════════════════════════════ --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput      = document.getElementById('searchFarmRecord');
        const farmSelect       = document.getElementById('filterFarm');
        const barangaySelect   = document.getElementById('filterBarangay');
        const predictionSelect = document.getElementById('filterPrediction');
        const actualSelect     = document.getElementById('filterActual');
        const statusBtns       = document.querySelectorAll('.status-btn');
        const seasonBtns       = document.querySelectorAll('.season-btn');
        const allRows          = Array.from(document.querySelectorAll('#farmRecordsBody .record-row'));
        const noResults        = document.getElementById('noFilterResults');
        const visibleCount     = document.getElementById('visibleCount');
        const paginationBar    = document.getElementById('paginationBar');
        const paginationList   = document.getElementById('paginationList');
        const paginationInfo   = document.getElementById('paginationInfo');
        const perPageSelect    = document.getElementById('perPageSelect');
        const resetBtn         = document.getElementById('resetFiltersBtn');

        if (!searchInput) return;

        let currentPage = 1;
        let perPage = parseInt(perPageSelect?.value || '25', 10);

        function hasActiveFilters() {
            const activeStatus = document.querySelector('.status-btn.active');
            const activeSeason = document.querySelector('.season-btn.active');
            return (
                searchInput.value.trim() !== '' ||
                (farmSelect && farmSelect.value !== 'all') ||
                barangaySelect.value !== 'all' ||
                (predictionSelect && predictionSelect.value !== 'all') ||
                (actualSelect && actualSelect.value !== 'all') ||
                (activeStatus && activeStatus.dataset.status !== 'all') ||
                (activeSeason && activeSeason.dataset.season !== 'all')
            );
        }

        function getMatchingRows() {
            const search     = searchInput.value.toLowerCase().trim();
            const farm       = farmSelect ? farmSelect.value : 'all';
            const barangay   = barangaySelect.value;
            const predFilter = predictionSelect ? predictionSelect.value : 'all';
            const actFilter  = actualSelect ? actualSelect.value : 'all';
            const activeStatus = document.querySelector('.status-btn.active');
            const status = activeStatus ? activeStatus.dataset.status : 'all';
            const activeSeason = document.querySelector('.season-btn.active');
            const season = activeSeason ? activeSeason.dataset.season : 'all';

            return allRows.filter(row => {
                const sd  = row.dataset.search || '';
                const rf  = row.dataset.farm || '';
                const rb  = row.dataset.barangay || '';
                const rs  = row.dataset.status || '';
                const rsn = row.dataset.season || '';
                const hp  = row.dataset.hasPrediction || '0';
                const ha  = row.dataset.hasActual || '0';

                const matchesSearch   = sd.includes(search);
                const matchesFarm     = (farm === 'all' || rf === farm);
                const matchesBarangay = (barangay === 'all' || rb === barangay);
                const matchesStatus   = (status === 'all' || rs === status);
                const matchesSeason   = (season === 'all' || rsn === season);

                let matchesPrediction = true;
                if (predFilter === 'with')    matchesPrediction = (hp === '1');
                if (predFilter === 'without') matchesPrediction = (hp === '0');

                let matchesActual = true;
                if (actFilter === 'with')    matchesActual = (ha === '1');
                if (actFilter === 'without') matchesActual = (ha === '0');

                return matchesSearch && matchesFarm && matchesBarangay && matchesStatus
                    && matchesSeason && matchesPrediction && matchesActual;
            });
        }

        function renderPagination() {
            const matching = getMatchingRows();
            const total = matching.length;
            const totalPages = Math.max(1, Math.ceil(total / perPage));

            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            allRows.forEach(r => r.style.display = 'none');

            const start = (currentPage - 1) * perPage;
            const end = start + perPage;
            matching.slice(start, end).forEach((row, i) => {
                row.style.display = '';
                const c = row.querySelector('.row-index');
                if (c) c.textContent = start + i + 1;
            });

            if (noResults) noResults.style.display = (total === 0 && allRows.length > 0) ? '' : 'none';
            if (visibleCount) visibleCount.textContent = total + ' visible';

            if (paginationInfo) {
                paginationInfo.textContent = total === 0
                    ? '— no entries'
                    : `— showing ${start + 1} to ${Math.min(end, total)} of ${total} entries`;
            }

            if (paginationBar) paginationBar.classList.toggle('d-none', totalPages <= 1);
            if (resetBtn) resetBtn.style.display = hasActiveFilters() ? '' : 'none';

            if (!paginationList) return;
            paginationList.innerHTML = '';

            const btnStyle = 'display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 10px;border-radius:8px;border:1px solid var(--slate-200);background:#fff;color:var(--slate-700);font-size:13px;font-weight:600;cursor:pointer;';
            const activeStyle = 'display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 10px;border-radius:8px;border:1px solid var(--brand-green);background:var(--brand-green);color:#fff;font-size:13px;font-weight:700;';
            const disabledStyle = 'display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 10px;border-radius:8px;border:1px solid var(--slate-200);background:var(--slate-50);color:var(--slate-300);font-size:13px;font-weight:600;cursor:not-allowed;';

            const addBtn = (label, page, opts = {}) => {
                const li = document.createElement('li');
                const a = document.createElement('button');
                a.type = 'button';
                a.innerHTML = label;
                a.setAttribute('style', opts.active ? activeStyle : (opts.disabled ? disabledStyle : btnStyle));
                if (!opts.active && !opts.disabled && page !== null) {
                    a.addEventListener('click', () => {
                        currentPage = page;
                        renderPagination();
                        document.querySelector('.card-custom')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                } else a.disabled = true;
                li.appendChild(a);
                paginationList.appendChild(li);
            };

            addBtn('<i class="bi bi-chevron-left"></i>', currentPage - 1, { disabled: currentPage === 1 });

            const pages = [];
            if (totalPages <= 7) {
                for (let i = 1; i <= totalPages; i++) pages.push(i);
            } else {
                pages.push(1);
                if (currentPage > 3) pages.push('...');
                const s = Math.max(2, currentPage - 1);
                const e = Math.min(totalPages - 1, currentPage + 1);
                for (let i = s; i <= e; i++) pages.push(i);
                if (currentPage < totalPages - 2) pages.push('...');
                pages.push(totalPages);
            }

            pages.forEach(p => {
                if (p === '...') {
                    const li = document.createElement('li');
                    const span = document.createElement('span');
                    span.textContent = '…';
                    span.setAttribute('style', 'display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;color:var(--slate-400);font-weight:700;');
                    li.appendChild(span);
                    paginationList.appendChild(li);
                } else {
                    addBtn(String(p), p, { active: p === currentPage });
                }
            });

            addBtn('<i class="bi bi-chevron-right"></i>', currentPage + 1, { disabled: currentPage === totalPages });
        }

        function resetAndRender() { currentPage = 1; renderPagination(); }

        searchInput.addEventListener('input', resetAndRender);
        farmSelect?.addEventListener('change', resetAndRender);
        barangaySelect.addEventListener('change', resetAndRender);
        predictionSelect?.addEventListener('change', resetAndRender);
        actualSelect?.addEventListener('change', resetAndRender);

        statusBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                statusBtns.forEach(b => {
                    b.style.background = 'transparent';
                    b.style.color = 'var(--slate-600)';
                    b.style.boxShadow = 'none';
                    b.classList.remove('active');
                });
                this.style.background = 'white';
                this.style.color = 'var(--slate-900)';
                this.style.boxShadow = '0 1px 2px rgba(0,0,0,0.05)';
                this.classList.add('active');
                resetAndRender();
            });
        });

        seasonBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                seasonBtns.forEach(b => {
                    b.style.background = 'transparent';
                    b.style.color = 'var(--slate-600)';
                    b.style.boxShadow = 'none';
                    b.classList.remove('active');
                });
                this.style.background = 'white';
                this.style.color = 'var(--slate-900)';
                this.style.boxShadow = '0 1px 2px rgba(0,0,0,0.05)';
                this.classList.add('active');
                resetAndRender();
            });
        });

        perPageSelect?.addEventListener('change', function() {
            perPage = parseInt(this.value, 10) || 25;
            resetAndRender();
        });

        resetBtn?.addEventListener('click', function() {
            searchInput.value = '';
            if (farmSelect) farmSelect.value = 'all';
            barangaySelect.value = 'all';
            if (predictionSelect) predictionSelect.value = 'all';
            if (actualSelect) actualSelect.value = 'all';

            statusBtns.forEach(b => {
                b.style.background = 'transparent';
                b.style.color = 'var(--slate-600)';
                b.style.boxShadow = 'none';
                b.classList.remove('active');
            });
            const statusAll = document.querySelector('.status-btn[data-status="all"]');
            if (statusAll) {
                statusAll.style.background = 'white';
                statusAll.style.color = 'var(--slate-900)';
                statusAll.style.boxShadow = '0 1px 2px rgba(0,0,0,0.05)';
                statusAll.classList.add('active');
            }

            seasonBtns.forEach(b => {
                b.style.background = 'transparent';
                b.style.color = 'var(--slate-600)';
                b.style.boxShadow = 'none';
                b.classList.remove('active');
            });
            const seasonAll = document.querySelector('.season-btn[data-season="all"]');
            if (seasonAll) {
                seasonAll.style.background = 'white';
                seasonAll.style.color = 'var(--slate-900)';
                seasonAll.style.boxShadow = '0 1px 2px rgba(0,0,0,0.05)';
                seasonAll.classList.add('active');
            }

            resetAndRender();
        });

        renderPagination();
    });
</script>

@if($isVerified)
<script>
    // ═══════════════════════════════════════════════════════════
    // CAVAN → TONS CONVERSION HELPERS
    // ═══════════════════════════════════════════════════════════
    const CAVAN_TO_TONS = 0.05;

    function cavanHaToTonsHa(cavanHa) {
        return cavanHa * CAVAN_TO_TONS;
    }

    // ═══════════════════════════════════════════════════════════
    // FARM RECORD MODAL — ADD / EDIT
    // ═══════════════════════════════════════════════════════════
    function openFarmerFarmRecordModal(farmId) {
        document.getElementById('farmerFarmRecordModalTitle').textContent = 'Add Season';
        document.getElementById('farmerFarmRecordModalLoading').style.display = 'block';
        document.getElementById('farmerFarmRecordModalContent').style.display = 'none';
        document.getElementById('farmerFarmRecordModalContent').innerHTML = '';

        const url = '{{ route("farmer.farm-records.create") }}' + (farmId ? '?farm_id=' + farmId : '');

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => {
                const content = document.getElementById('farmerFarmRecordModalContent');
                document.getElementById('farmerFarmRecordModalLoading').style.display = 'none';
                content.style.display = 'block';
                content.innerHTML = html;

                // ← Run the inline <script> from the injected partial
                executeInjectedScripts(content);

                const form = document.getElementById('farmerFarmRecordForm');
                if (form) form.addEventListener('submit', submitFarmerFarmRecord);
            })
            .catch(() => {
                document.getElementById('farmerFarmRecordModalLoading').innerHTML =
                    '<p class="text-danger">Failed to load form.</p>';
            });

        new bootstrap.Modal(document.getElementById('farmerFarmRecordModal')).show();
    }

    function editFarmerFarmRecord(id) {
        document.getElementById('farmerFarmRecordModalTitle').textContent = 'Edit Season';
        document.getElementById('farmerFarmRecordModalLoading').style.display = 'block';
        document.getElementById('farmerFarmRecordModalContent').style.display = 'none';
        document.getElementById('farmerFarmRecordModalContent').innerHTML = '';

        fetch('/farmer/farm-records/' + id + '/edit', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => {
                const content = document.getElementById('farmerFarmRecordModalContent');
                document.getElementById('farmerFarmRecordModalLoading').style.display = 'none';
                content.style.display = 'block';
                content.innerHTML = html;

                // ← Run the inline <script> from the injected partial
                executeInjectedScripts(content);

                const form = document.getElementById('farmerFarmRecordForm');
                if (form) form.addEventListener('submit', submitFarmerFarmRecord);
            })
            .catch(() => {
                document.getElementById('farmerFarmRecordModalLoading').innerHTML =
                    '<p class="text-danger">Failed to load form.</p>';
            });

        new bootstrap.Modal(document.getElementById('farmerFarmRecordModal')).show();
    }

    function submitFarmerFarmRecord(e) {
        e.preventDefault();
        const form = e.target;

        // ── Belt-and-suspenders: sync cavan → tons right before submit ──
        // (In case the inline listener didn't attach for any reason.)
        const syncPairs = [
            ['farmerHistoricalCavan', 'farmerHistoricalTons'],
            ['farmerActualCavan',     'farmerActualTons'],
        ];
        syncPairs.forEach(([cavanId, tonsId]) => {
            const c = document.getElementById(cavanId);
            const t = document.getElementById(tonsId);
            if (!c || !t) return;
            const v = parseFloat(c.value);
            t.value = (!isNaN(v) && v >= 0) ? (v * CAVAN_TO_TONS).toFixed(4) : '';
        });

        const btn = form.querySelector('button[type="submit"]');
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

        const existing = form.querySelector('#formErrors');
        if (existing) existing.innerHTML = '';

        fetch(form.action, {
            method: form.method || 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('farmerFarmRecordModal')).hide();
                location.reload();
            } else {
                const div = document.getElementById('formErrors') || document.createElement('div');
                div.id = 'formErrors';
                div.className = 'alert alert-danger mb-3';
                let msg = data.error || 'An error occurred.';
                if (data.errors) msg = Object.values(data.errors).flat().join('<br>');
                div.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + msg;
                form.prepend(div);
                btn.disabled = false;
                btn.innerHTML = original;
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = original;
            alert('Network error. Please try again.');
        });
    }

    // ═══════════════════════════════════════════════════════════
    // VIEW SEASON DETAILS
    // ═══════════════════════════════════════════════════════════
    function viewFarmerFarmRecord(id) {
        document.getElementById('farmerViewLoading').style.display = 'block';
        document.getElementById('farmerViewContent').style.display = 'none';
        document.getElementById('farmerViewContent').innerHTML = '';

        fetch('/farmer/farm-records/' + id, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => {
                document.getElementById('farmerViewLoading').style.display = 'none';
                document.getElementById('farmerViewContent').style.display = 'block';
                document.getElementById('farmerViewContent').innerHTML = html;
            })
            .catch(() => {
                document.getElementById('farmerViewLoading').innerHTML = '<p class="text-danger">Failed to load.</p>';
            });

        new bootstrap.Modal(document.getElementById('farmerFarmRecordViewModal')).show();
    }

    // ═══════════════════════════════════════════════════════════
    // MARK AS HARVESTED
    // ═══════════════════════════════════════════════════════════
    function openFarmerHarvestModal(btn) {
        const id              = btn.dataset.recordId;
        const farm            = btn.dataset.farm;
        const variety         = btn.dataset.variety;
        const season          = btn.dataset.season;
        const predictedTons   = btn.dataset.predictedTons;
        const predictedCavan  = btn.dataset.predictedCavan;

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
                                <i class="bi bi-cpu"></i> System Predicted
                            </div>
                            <div style="font-size: 22px; font-weight: 800; color: var(--brand-green-dark);">
                                ${predictedCavan} <span style="font-size: 14px;">cavan/ha</span>
                            </div>
                            <div style="font-size: 11px; color: var(--brand-green-dark); opacity: 0.8;">
                                ≈ ${predictedTons} t/ha
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-success" onclick="useFarmerSuggestedYield('${predictedCavan}')">
                            <i class="bi bi-arrow-down-circle"></i> Use this
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

    // Live conversion preview
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
                    preview.innerHTML = `
                        <i class="bi bi-arrow-right-circle"></i>
                        Equivalent to <strong>${tonsHa.toFixed(2)} t/ha</strong>
                    `;
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

        const cavanInput = document.getElementById('farmerActualYieldInput');
        const tonsHidden = document.getElementById('farmerActualYieldTons');
        const cavanVal = parseFloat(cavanInput?.value);
        if (!isNaN(cavanVal) && cavanVal >= 0) {
            tonsHidden.value = cavanHaToTonsHa(cavanVal).toFixed(4);
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
    #paginationList button:not(:disabled):hover {
        background: var(--brand-green-light) !important;
        border-color: var(--brand-green) !important;
        color: var(--brand-green-dark) !important;
        transform: translateY(-1px);
    }
    @media (max-width: 575.98px) {
        #paginationBar { justify-content: center !important; }
        #paginationList button { min-width: 30px !important; height: 30px !important; padding: 0 8px !important; font-size: 12px !important; }
    }
</style>
@endpush