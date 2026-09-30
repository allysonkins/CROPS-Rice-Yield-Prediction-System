@extends('layouts.app')

@section('title', 'Farm Records')

@section('content')
@php
    $activeYear = $yearFilter ?? (string) now()->year;
@endphp

<div class="d-flex justify-content-end align-items-center mb-4">
    @if(auth()->user()->role !== 'farmer')
        <button type="button" class="btn btn-success" onclick="openFarmRecordModal()">
            <i class="bi bi-plus-circle"></i> Add Farm Record
        </button>
    @endif
    <span class="badge bg-secondary ms-2">{{ $farmRecords->count() }} Records</span>
</div>

<!-- Stats Cards -->
<div class="row g-2 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Total Records</div>
                <div class="value">{{ $farmRecords->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-clipboard-data-fill"></i></div>
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
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Varieties</div>
                <div class="value">{{ $farmRecords->pluck('rice_variety_id')->unique()->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-flower1"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Avg Fertilizer</div>
                <div class="value">{{ number_format($farmRecords->avg('fertilizer_kg_ha'), 1) }} kg/ha</div>
            </div>
            <div class="stat-icon"><i class="bi bi-droplet"></i></div>
        </div>
    </div>
</div>

<!-- Filter Controls -->
<div class="row g-2 mb-4">
    <div class="col-12">
        <div class="card-custom" style="padding: 16px 20px;">

            {{-- ─────────── ROW 1: Search + Location ─────────── --}}
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">

                {{-- SEARCH --}}
                <div style="flex: 1; min-width: 240px;">
                    <div class="input-group" style="border-radius: 12px; overflow: hidden; border: 1px solid var(--gray-200);">
                        <span class="input-group-text" style="background: var(--gray-50); border: none; color: var(--gray-400);">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="searchFarmRecord" class="form-control" placeholder="Search by farmer, variety, season..." style="border: none; background: var(--gray-50); font-size: 13px;">
                    </div>
                </div>

                {{-- YEAR --}}
                <div style="min-width: 140px;">
                    <select id="filterYear" class="form-select"
                            onchange="window.location.href = this.value"
                            title="Filter by year"
                            style="border-radius: 12px; border: 1px solid var(--gray-200); background: var(--gray-50); font-size: 13px; padding: 0.45rem 2rem 0.45rem 1rem; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.7rem; cursor: pointer; appearance: none;">
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

                {{-- BARANGAY --}}
                <div style="min-width: 160px;">
                    <select id="filterBarangay" class="form-select"
                            title="Filter by barangay"
                            style="border-radius: 12px; border: 1px solid var(--gray-200); background: var(--gray-50); font-size: 13px; padding: 0.45rem 2rem 0.45rem 1rem; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.7rem; cursor: pointer; appearance: none; width: auto; min-width: 150px;">
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

            {{-- ─────────── ROW 2: Pills + Advanced filters ─────────── --}}
            <div class="d-flex flex-wrap align-items-center gap-3 pt-3" style="border-top: 1px dashed var(--gray-200);">

                {{-- SEASON GROUP --}}
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="font-size: 10px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase;">Season</span>
                    <div class="d-flex" style="background: var(--gray-100); border-radius: 12px; padding: 4px; gap: 2px;">
                        <button class="filter-btn season-btn active" data-season="all" style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: white; color: var(--gray-900); box-shadow: 0 1px 2px rgba(0,0,0,0.05); white-space: nowrap;">All</button>
                        <button class="filter-btn season-btn"        data-season="Dry" style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: transparent; color: var(--gray-600); white-space: nowrap;">Dry</button>
                        <button class="filter-btn season-btn"        data-season="Wet" style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: transparent; color: var(--gray-600); white-space: nowrap;">Wet</button>
                    </div>
                </div>

                <div style="width: 1px; height: 28px; background: var(--gray-200);"></div>

                {{-- STATUS GROUP --}}
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="font-size: 10px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase;">Status</span>
                    <div class="d-flex" style="background: var(--gray-100); border-radius: 12px; padding: 4px; gap: 2px;">
                        <button class="filter-btn status-btn active" data-status="all"        style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: white; color: var(--gray-900); box-shadow: 0 1px 2px rgba(0,0,0,0.05); white-space: nowrap;">All</button>
                        <button class="filter-btn status-btn"        data-status="Vegetative" style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: transparent; color: var(--gray-600); white-space: nowrap;">Vegetative</button>
                        <button class="filter-btn status-btn"        data-status="Harvested"  style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: transparent; color: var(--gray-600); white-space: nowrap;">Harvested</button>
                    </div>
                </div>

                <div style="width: 1px; height: 28px; background: var(--gray-200);"></div>

                {{-- PREDICTION FILTER --}}
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="font-size: 10px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase;">Prediction</span>
                    <select id="filterPrediction" class="form-select" title="Filter by prediction availability"
                            style="border-radius: 10px; border: 1px solid var(--gray-200); background: var(--gray-50); font-size: 12px; padding: 0.4rem 2rem 0.4rem 0.85rem; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.65rem center; background-size: 0.65rem; cursor: pointer; appearance: none; width: auto; min-width: 130px;">
                        <option value="all">Any</option>
                        <option value="with">Available</option>
                        <option value="without">Missing</option>
                    </select>
                </div>

                {{-- ACTUAL YIELD FILTER --}}
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="font-size: 10px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase;">Actual Yield</span>
                    <select id="filterActual" class="form-select" title="Filter by actual yield availability"
                            style="border-radius: 10px; border: 1px solid var(--gray-200); background: var(--gray-50); font-size: 12px; padding: 0.4rem 2rem 0.4rem 0.85rem; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.65rem center; background-size: 0.65rem; cursor: pointer; appearance: none; width: auto; min-width: 130px;">
                        <option value="all">Any</option>
                        <option value="with">Recorded</option>
                        <option value="without">Pending</option>
                    </select>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Farm Records Table -->
<div class="card-custom">
    <div class="card-title">
        <i class="bi bi-table"></i> Farm Records
        @if($activeYear !== 'all')
            <span class="badge bg-light text-muted ms-1" style="font-weight: 400; font-size: 11px;">{{ $activeYear }}</span>
        @endif
        <span class="badge bg-light text-muted ms-1" id="visibleCount" style="font-weight: 400; font-size: 10px;">
            {{ $farmRecords->count() }} total
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Farm</th>
                    <th>Variety</th>
                    <th>Season / Year</th>
                    <th>Fertilizer (kg/ha)</th>
                    <th>Actual Yield</th>
                    <th>Expected Yield (t/ha)</th>
                    <th>
                        Predicted Yield
                        <i class="bi bi-question-circle-fill"
                           style="font-size: 12px; color: var(--gray-400); cursor: help;"
                           data-bs-toggle="tooltip"
                           data-bs-placement="top"
                           data-bs-html="true"
                           title="<div style='text-align:left; font-size:12px; line-height:1.5;'>
                               <strong>XGBoost prediction</strong><br>
                               Point yield estimated by the ML model.<br><br>
                               <strong>Class is variety-relative</strong> — compared against the variety's own average yield for the chosen seeding method.<br><br>
                               • <strong>High</strong> — ≥ 112.5% of variety average<br>
                               • <strong>Medium</strong> — 87.5% – 112.5%<br>
                               • <strong>Low</strong> — below 87.5%<br><br>
                               <em>Class is derived in the application — not a model output.</em>
                           </div>"></i>
                    </th>
                    <th>Status</th>
                    @if(auth()->user()->role !== 'farmer')
                        <th style="white-space: nowrap; min-width: 170px;">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody id="farmRecordsBody">
                @forelse($farmRecords as $record)
                    @php
                        $expected = null;
                        if ($record->riceVariety && $record->seeding_method) {
                            $expected = $record->riceVariety->getYieldForMethod($record->seeding_method);
                        }

                        $searchData = strtolower(
                            ($record->farm->user->name ?? '') . ' ' .
                            ($record->riceVariety->name ?? '') . ' ' .
                            ($record->season ?? '') . ' ' .
                            ($record->year ?? '')
                        );
                        $barangay = $record->farm->barangay ?? '';
                        $status   = $record->status ?? '';
                        $seasonNorm = str_replace(' Season', '', $record->season ?? '');

                        $prediction  = $record->latest_xgboost_prediction;
                        $predicted   = $prediction?->predicted_yield_tons_ha;
                        $confidence  = $prediction?->confidence;
                        $lo          = $prediction?->yield_lower;
                        $hi          = $prediction?->yield_upper;
                        $yieldClass  = $record->predicted_yield_class;
                        $badge       = $record->predicted_yield_badge;
                        $ratio       = $record->predicted_yield_ratio;
                        $avgYield    = $record->variety_avg_yield;

                        $hasPrediction = $predicted !== null;
                        $hasActual     = $record->actual_yield_tons_ha !== null && $record->actual_yield_tons_ha > 0;
                    @endphp
                    <tr class="record-row"
                        data-search="{{ $searchData }}"
                        data-barangay="{{ $barangay }}"
                        data-status="{{ $status }}"
                        data-season="{{ $seasonNorm }}"
                        data-has-prediction="{{ $hasPrediction ? '1' : '0' }}"
                        data-has-actual="{{ $hasActual ? '1' : '0' }}">
                        <td class="row-index">{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $record->farm->name ?? 'N/A' }}</strong>
                            <br><small class="text-muted">{{ $record->farm->barangay ?? '' }}</small>
                        </td>
                        <td>
                            <span class="variety-link" style="cursor: pointer; color: var(--green); text-decoration: underline;" onclick="viewVarietyDetails({{ $record->rice_variety_id }})">
                                {{ $record->riceVariety->name ?? 'N/A' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark" style="font-weight: 600;">
                                {{ $record->year ?? '—' }}
                            </span>
                            <br><small class="text-muted">{{ $record->season ?? '' }}</small>
                        </td>
                        <td>{{ number_format($record->fertilizer_kg_ha, 2) }}</td>
                        <td>
                            @if($record->status === 'Harvested')
                                {{ $record->actual_yield_tons_ha ? number_format($record->actual_yield_tons_ha, 2) : 'N/A' }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($expected && $expected->avg !== null && $expected->max !== null)
                                <span class="fw-bold text-success">{{ number_format($expected->avg, 2) }}</span>
                                <small class="text-muted">(max: {{ number_format($expected->max, 2) }})</small>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($hasPrediction)
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <strong style="color: #0f4c2b;">{{ number_format($predicted, 2) }}</strong>
                                    @if($yieldClass)
                                        <span class="badge-status {{ $badge }}"
                                              style="cursor: help;"
                                              data-bs-toggle="tooltip"
                                              data-bs-placement="top"
                                              data-bs-html="true"
                                              title="<div style='text-align:left; font-size:12px; line-height:1.5;'>
                                                  <strong>Variety-relative class</strong><br>
                                                  Predicted yield: <strong>{{ number_format($predicted, 2) }} t/ha</strong><br>
                                                  Variety average ({{ $record->seeding_method ?? 'Transplanted' }}): <strong>{{ number_format($avgYield, 2) }} t/ha</strong><br>
                                                  Ratio: <strong>{{ number_format($ratio * 100, 1) }}%</strong> of average<br>
                                                  @if($confidence !== null)
                                                      Confidence: <strong>{{ number_format($confidence * 100, 1) }}%</strong><br>
                                                  @endif
                                                  @if($lo !== null && $hi !== null)
                                                      80% interval: <strong>{{ number_format($lo, 2) }} – {{ number_format($hi, 2) }} t/ha</strong>
                                                  @endif
                                              </div>">
                                            <span class="dot"></span> {{ $yieldClass }}
                                        </span>
                                    @endif
                                </div>
                                @if($confidence !== null)
                                    <small class="text-muted d-block" style="font-size: 10px; margin-top: 2px;">
                                        {{ number_format($confidence * 100, 1) }}% confidence
                                    </small>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $record->status_badge_class }}">
                                <i class="bi {{ $record->status_icon }}"></i>
                                {{ $record->status ?? 'N/A' }}
                            </span>
                        </td>
                        @if(auth()->user()->role !== 'farmer')
                            <td style="white-space: nowrap;">
                                <button type="button" class="btn btn-sm btn-secondary" onclick="viewFarmRecord({{ $record->id }})" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="editFarmRecord({{ $record->id }})" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                @if($record->status === 'Vegetative')
                                    <button type="button"
                                            class="btn btn-sm btn-success harvest-btn"
                                            data-record-id="{{ $record->id }}"
                                            data-farm="{{ $record->farm->name ?? 'N/A' }}"
                                            data-variety="{{ $record->riceVariety->name ?? 'N/A' }}"
                                            data-season="{{ $record->season ?? 'N/A' }}"
                                            data-predicted="{{ $predicted !== null ? number_format($predicted, 2, '.', '') : '' }}"
                                            data-confidence="{{ $confidence !== null ? number_format($confidence, 4, '.', '') : '' }}"
                                            data-yield-class="{{ $yieldClass ?? '' }}"
                                            data-expected-avg="{{ $expected && $expected->avg !== null ? number_format($expected->avg, 2, '.', '') : '' }}"
                                            data-expected-max="{{ $expected && $expected->max !== null ? number_format($expected->max, 2, '.', '') : '' }}"
                                            onclick="openHarvestModal(this)"
                                            title="Mark as Harvested">
                                        <i class="bi bi-basket-fill"></i>
                                    </button>
                                @endif

                                <form action="{{ route('admin.farm-records.destroy', $record->id) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('{{ $record->predictions->count() > 0
                                          ? 'Delete this farm record? This will also remove ' . $record->predictions->count() . ' associated prediction(s). This action cannot be undone.'
                                          : 'Delete this farm record? This action cannot be undone.' }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->role !== 'farmer' ? 10 : 9 }}" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 28px;"></i>
                            <p class="mt-2 mb-0">
                                @if($activeYear !== 'all')
                                    No farm records for <strong>{{ $activeYear }}</strong>.
                                @else
                                    No farm records yet.
                                @endif
                            </p>
                        </td>
                    </tr>
                @endforelse

                <tr id="noFilterResults" style="display: none;">
                    <td colspan="{{ auth()->user()->role !== 'farmer' ? 10 : 9 }}" class="text-center py-4 text-muted">
                        <i class="bi bi-search" style="font-size: 28px;"></i>
                        <p class="mt-2 mb-0">No farm records match your filters.</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
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
        <nav aria-label="Farm record pagination">
            <ul id="paginationList" class="pagination mb-0" style="display: flex; align-items: center; gap: 4px; list-style: none; padding: 0; margin: 0;"></ul>
        </nav>
    </div>
</div>

<!-- Include the Add/Edit Modal -->
@if(auth()->user()->role !== 'farmer')
    @include('admin.farm-records.partials.modal')
@endif

<!-- VIEW FARM RECORD DETAILS MODAL -->
<div class="modal fade" id="viewFarmRecordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--green); color: white;">
                <h5 class="modal-title"><i class="bi bi-eye"></i> Farm Record Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewFarmRecordModalBody" style="overflow: hidden;">
                <div class="text-center py-4" id="viewModalLoading">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading record details...</p>
                </div>
                <div id="viewModalContent" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

<!-- RICE VARIETY DETAILS MODAL -->
<div class="modal fade" id="varietyDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--green); color: white;">
                <h5 class="modal-title"><i class="bi bi-flower1"></i> Rice Variety Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="varietyDetailModalBody" style="overflow: hidden;">
                <div class="text-center py-4" id="varietyDetailLoading">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading variety details...</p>
                </div>
                <div id="varietyDetailContent" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

@if(auth()->user()->role !== 'farmer')
<!-- MARK AS HARVESTED MODAL -->
<div class="modal fade" id="markHarvestedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--green); color: white;">
                <h5 class="modal-title">
                    <i class="bi bi-basket-fill"></i> Mark as Harvested
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="markHarvestedForm">
                @csrf
                <div class="modal-body">
                    <div id="harvestModalError"></div>

                    <div class="card mb-3" style="background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200);">
                        <div class="card-body py-3">
                            <div style="font-size: 11px; text-transform: uppercase; color: var(--gray-500); font-weight: 700; letter-spacing: 0.5px;">Record</div>
                            <div id="harvestFarmName" style="font-size: 16px; font-weight: 700; color: var(--green-dark);"></div>
                            <div id="harvestRecordMeta" class="text-muted" style="font-size: 13px;"></div>
                        </div>
                    </div>

                    <div id="harvestSuggestion"></div>

                    <div>
                        <label class="form-label fw-semibold">Actual Yield (t/ha) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" max="20"
                               name="actual_yield_tons_ha" id="actualYieldInput"
                               class="form-control form-control-lg" placeholder="e.g., 4.80" required>
                        <small class="text-muted">Enter the actual harvested yield from the field.</small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success" id="confirmHarvestBtn">
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
@if(auth()->user()->role !== 'farmer')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el, { container: 'body' });
        });
    });

    // ═══════════════════════════════════════════════════════════
    // FILTERING + PAGINATION
    // ═══════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput      = document.getElementById('searchFarmRecord');
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
                barangaySelect.value !== 'all' ||
                (predictionSelect && predictionSelect.value !== 'all') ||
                (actualSelect && actualSelect.value !== 'all') ||
                (activeStatus && activeStatus.dataset.status !== 'all') ||
                (activeSeason && activeSeason.dataset.season !== 'all')
            );
        }

        function getMatchingRows() {
            const search     = searchInput.value.toLowerCase().trim();
            const barangay   = barangaySelect.value;
            const predFilter = predictionSelect ? predictionSelect.value : 'all';
            const actFilter  = actualSelect ? actualSelect.value : 'all';
            const activeStatus = document.querySelector('.status-btn.active');
            const status = activeStatus ? activeStatus.dataset.status : 'all';
            const activeSeason = document.querySelector('.season-btn.active');
            const season = activeSeason ? activeSeason.dataset.season : 'all';

            return allRows.filter(row => {
                const sd  = row.dataset.search || '';
                const rb  = row.dataset.barangay || '';
                const rs  = row.dataset.status || '';
                const rsn = row.dataset.season || '';
                const hp  = row.dataset.hasPrediction || '0';
                const ha  = row.dataset.hasActual || '0';

                const matchesSearch   = sd.includes(search);
                const matchesBarangay = (barangay === 'all' || rb === barangay);
                const matchesStatus   = (status === 'all' || rs === status);
                const matchesSeason   = (season === 'all' || rsn === season);

                let matchesPrediction = true;
                if (predFilter === 'with')    matchesPrediction = (hp === '1');
                if (predFilter === 'without') matchesPrediction = (hp === '0');

                let matchesActual = true;
                if (actFilter === 'with')    matchesActual = (ha === '1');
                if (actFilter === 'without') matchesActual = (ha === '0');

                return matchesSearch && matchesBarangay && matchesStatus
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
        barangaySelect.addEventListener('change', resetAndRender);
        predictionSelect?.addEventListener('change', resetAndRender);
        actualSelect?.addEventListener('change', resetAndRender);

        statusBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                statusBtns.forEach(b => {
                    b.style.background = 'transparent';
                    b.style.color = 'var(--gray-600)';
                    b.style.boxShadow = 'none';
                    b.classList.remove('active');
                });
                this.style.background = 'white';
                this.style.color = 'var(--gray-900)';
                this.style.boxShadow = '0 1px 2px rgba(0,0,0,0.05)';
                this.classList.add('active');
                resetAndRender();
            });
        });

        seasonBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                seasonBtns.forEach(b => {
                    b.style.background = 'transparent';
                    b.style.color = 'var(--gray-600)';
                    b.style.boxShadow = 'none';
                    b.classList.remove('active');
                });
                this.style.background = 'white';
                this.style.color = 'var(--gray-900)';
                this.style.boxShadow = '0 1px 2px rgba(0,0,0,0.05)';
                this.classList.add('active');
                resetAndRender();
            });
        });

        perPageSelect?.addEventListener('change', function() {
            perPage = parseInt(this.value, 10) || 25;
            resetAndRender();
        });

        // ── Reset button ──
        resetBtn?.addEventListener('click', function() {
            searchInput.value = '';
            barangaySelect.value = 'all';
            if (predictionSelect) predictionSelect.value = 'all';
            if (actualSelect) actualSelect.value = 'all';

            statusBtns.forEach(b => {
                b.style.background = 'transparent';
                b.style.color = 'var(--gray-600)';
                b.style.boxShadow = 'none';
                b.classList.remove('active');
            });
            const statusAll = document.querySelector('.status-btn[data-status="all"]');
            if (statusAll) {
                statusAll.style.background = 'white';
                statusAll.style.color = 'var(--gray-900)';
                statusAll.style.boxShadow = '0 1px 2px rgba(0,0,0,0.05)';
                statusAll.classList.add('active');
            }

            seasonBtns.forEach(b => {
                b.style.background = 'transparent';
                b.style.color = 'var(--gray-600)';
                b.style.boxShadow = 'none';
                b.classList.remove('active');
            });
            const seasonAll = document.querySelector('.season-btn[data-season="all"]');
            if (seasonAll) {
                seasonAll.style.background = 'white';
                seasonAll.style.color = 'var(--gray-900)';
                seasonAll.style.boxShadow = '0 1px 2px rgba(0,0,0,0.05)';
                seasonAll.classList.add('active');
            }

            resetAndRender();
        });

        renderPagination();
    });

    // ═══════════════════════════════════════════════════════════
    // FARM RECORD MODAL HANDLERS
    // ═══════════════════════════════════════════════════════════
    function openFarmRecordModal() {
        document.getElementById('modalTitle').textContent = 'Add Farm Record';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        fetch('{{ route("admin.farm-records.create") }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.text())
        .then(html => {
            document.getElementById('modalLoading').style.display = 'none';
            document.getElementById('modalContent').style.display = 'block';
            document.getElementById('modalContent').innerHTML = html;
            const form = document.getElementById('farmRecordForm');
            if (form) form.addEventListener('submit', handleFormSubmit);
        })
        .catch(() => {
            document.getElementById('modalLoading').style.display = 'none';
            document.getElementById('modalContent').style.display = 'block';
            document.getElementById('modalContent').innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Failed to load form. Please try again.</div>`;
        });

        new bootstrap.Modal(document.getElementById('farmRecordModal')).show();
    }

    function editFarmRecord(id) {
        document.getElementById('modalTitle').textContent = 'Edit Farm Record';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        fetch('/admin/farm-records/' + id + '/edit', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.text())
        .then(html => {
            document.getElementById('modalLoading').style.display = 'none';
            document.getElementById('modalContent').style.display = 'block';
            document.getElementById('modalContent').innerHTML = html;
            const form = document.getElementById('farmRecordForm');
            if (form) form.addEventListener('submit', handleFormSubmit);
        })
        .catch(() => {
            document.getElementById('modalLoading').style.display = 'none';
            document.getElementById('modalContent').style.display = 'block';
            document.getElementById('modalContent').innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Failed to load form. Please try again.</div>`;
        });

        new bootstrap.Modal(document.getElementById('farmRecordModal')).show();
    }

    function viewFarmRecord(id) {
        document.getElementById('viewModalLoading').style.display = 'block';
        document.getElementById('viewModalContent').style.display = 'none';
        document.getElementById('viewModalContent').innerHTML = '';

        fetch('/admin/farm-records/' + id, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.text())
        .then(html => {
            document.getElementById('viewModalLoading').style.display = 'none';
            document.getElementById('viewModalContent').style.display = 'block';
            document.getElementById('viewModalContent').innerHTML = html;
            document.querySelectorAll('#viewModalContent [data-bs-toggle="tooltip"]').forEach(el => {
                new bootstrap.Tooltip(el, { container: 'body' });
            });
        })
        .catch(() => {
            document.getElementById('viewModalLoading').style.display = 'none';
            document.getElementById('viewModalContent').style.display = 'block';
            document.getElementById('viewModalContent').innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Failed to load record details. Please try again.</div>`;
        });

        new bootstrap.Modal(document.getElementById('viewFarmRecordModal')).show();
    }

    function viewVarietyDetails(id) {
        document.getElementById('varietyDetailLoading').style.display = 'block';
        document.getElementById('varietyDetailContent').style.display = 'none';
        document.getElementById('varietyDetailContent').innerHTML = '';

        fetch('/admin/rice-varieties/' + id + '/details', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.text())
        .then(html => {
            document.getElementById('varietyDetailLoading').style.display = 'none';
            document.getElementById('varietyDetailContent').style.display = 'block';
            document.getElementById('varietyDetailContent').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('varietyDetailLoading').style.display = 'none';
            document.getElementById('varietyDetailContent').style.display = 'block';
            document.getElementById('varietyDetailContent').innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Failed to load variety details. Please try again.</div>`;
        });

        new bootstrap.Modal(document.getElementById('varietyDetailModal')).show();
    }

    function handleFormSubmit(e) {
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
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('farmRecordModal')).hide();
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

    // ═══════════════════════════════════════════════════════════
    // MARK AS HARVESTED
    // ═══════════════════════════════════════════════════════════
    function openHarvestModal(btn) {
        const id          = btn.dataset.recordId;
        const farm        = btn.dataset.farm;
        const variety     = btn.dataset.variety;
        const season      = btn.dataset.season;
        const predicted   = btn.dataset.predicted;
        const confidence  = btn.dataset.confidence;
        const yieldClass  = btn.dataset.yieldClass;
        const expectedAvg = btn.dataset.expectedAvg;
        const expectedMax = btn.dataset.expectedMax;

        const form = document.getElementById('markHarvestedForm');
        form.action = '/admin/farm-records/' + id + '/mark-harvested';

        document.getElementById('harvestModalError').innerHTML = '';
        document.getElementById('harvestFarmName').textContent = farm;
        document.getElementById('harvestRecordMeta').textContent = variety + ' · ' + season;

        const suggestionEl = document.getElementById('harvestSuggestion');

        if (predicted) {
            const confPct = confidence ? (parseFloat(confidence) * 100).toFixed(1) : null;
            const clsBadge = yieldClass
                ? `<span class="badge-status ${yieldClass.toLowerCase()}" style="margin-left: 6px;"><span class="dot"></span> ${yieldClass}</span>`
                : '';

            suggestionEl.innerHTML = `
                <div class="card mb-3" style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px;">
                    <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <div style="font-size: 11px; text-transform: uppercase; color: #047857; font-weight: 700; letter-spacing: 0.5px;">
                                <i class="bi bi-cpu"></i> XGBoost Predicted Yield ${clsBadge}
                            </div>
                            <div style="font-size: 22px; font-weight: 800; color: #065f46;">
                                ${predicted} <span style="font-size: 14px; font-weight: 600;">t/ha</span>
                            </div>
                            ${confPct ? `<div style="font-size: 11px; color: #047857;"><i class="bi bi-activity"></i> ${confPct}% confidence</div>` : ''}
                        </div>
                        <button type="button" class="btn btn-sm btn-success" onclick="useSuggestedYield('${predicted}')">
                            <i class="bi bi-arrow-down-circle"></i> Use this
                        </button>
                    </div>
                </div>
            `;
        } else if (expectedAvg) {
            suggestionEl.innerHTML = `
                <div class="card mb-3" style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px;">
                    <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <div style="font-size: 11px; text-transform: uppercase; color: #1d4ed8; font-weight: 700; letter-spacing: 0.5px;">
                                <i class="bi bi-flower1"></i> Variety Average
                            </div>
                            <div style="font-size: 22px; font-weight: 800; color: #1e3a8a;">
                                ${expectedAvg} <span style="font-size: 14px; font-weight: 600;">t/ha</span>
                            </div>
                            ${expectedMax ? `<div style="font-size: 11px; color: #1e40af; opacity: 0.8;">Max: ${expectedMax} t/ha</div>` : ''}
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="useSuggestedYield('${expectedAvg}')">
                            <i class="bi bi-arrow-down-circle"></i> Use this
                        </button>
                    </div>
                </div>
            `;
        } else {
            suggestionEl.innerHTML = '';
        }

        const input = document.getElementById('actualYieldInput');
        if (input) input.value = '';

        const modalEl = document.getElementById('markHarvestedModal');
        new bootstrap.Modal(modalEl).show();

        modalEl.addEventListener('shown.bs.modal', function focusHandler() {
            document.getElementById('actualYieldInput')?.focus();
            modalEl.removeEventListener('shown.bs.modal', focusHandler);
        });
    }

    function useSuggestedYield(value) {
        const input = document.getElementById('actualYieldInput');
        if (input) {
            input.value = value;
            input.focus();
        }
    }

    document.getElementById('markHarvestedForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const btn = document.getElementById('confirmHarvestBtn');
        const originalHTML = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
        document.getElementById('harvestModalError').innerHTML = '';

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('markHarvestedModal')).hide();
                location.reload();
            } else {
                let msg = data.error || 'Failed to save.';
                if (data.errors) msg = Object.values(data.errors).flat().join('<br>');
                document.getElementById('harvestModalError').innerHTML =
                    '<div class="alert alert-danger">' + msg + '</div>';
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        })
        .catch(err => {
            document.getElementById('harvestModalError').innerHTML =
                '<div class="alert alert-danger">Network error: ' + err + '</div>';
            btn.disabled = false;
            btn.innerHTML = originalHTML;
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