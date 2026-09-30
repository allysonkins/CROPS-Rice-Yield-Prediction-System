@extends('layouts.app')

@section('title', 'Rice Yield Predictions')

@section('content')
@php
    $hasPredictions = ($uniquePredictions->count() > 0);
    $cityAverage    = $hasPredictions ? number_format($uniquePredictions->avg('predicted_yield_tons_ha'), 2) : null;
    $avgConfidence  = $hasPredictions && $uniquePredictions->whereNotNull('confidence')->count() > 0
        ? $uniquePredictions->whereNotNull('confidence')->avg('confidence')
        : null;
    $totalRecords   = $uniquePredictions->count();
@endphp

{{-- STATS --}}
<div class="row g-2 mb-3">
    <div class="col-12 col-sm-4">
        <div class="stat-card" style="border-left-color: var(--green);">
            <div class="stat-info">
                <div class="label" style="text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;">Average City Yield</div>
                <div class="value" style="font-size: 26px;">
                    @if($hasPredictions)
                        {{ $cityAverage }} <span style="font-size: 15px; color: var(--gray-400);">t/ha</span>
                    @else
                        <span style="font-size: 18px; color: var(--gray-400);">No data</span>
                    @endif
                </div>
                @if($hasPredictions)
                    <div class="text-muted small">Based on {{ $totalRecords }} prediction{{ $totalRecords !== 1 ? 's' : '' }}</div>
                @endif
            </div>
            <div class="stat-icon"><i class="bi bi-graph-up-arrow" style="color: var(--green);"></i></div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="stat-card" style="border-left-color: var(--gold);">
            <div class="stat-info">
                <div class="label" style="text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;">Total Predictions</div>
                <div class="value" style="font-size: 26px;">{{ $totalRecords }}</div>
                <div class="text-muted small">XGBoost Regressor</div>
            </div>
            <div class="stat-icon"><i class="bi bi-cpu" style="color: var(--gold);"></i></div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="stat-card" style="border-left-color: #4f46e5;">
            <div class="stat-info">
                <div class="label" style="text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;">
                    Avg Confidence
                    <i class="bi bi-question-circle-fill"
                       style="font-size: 11px; color: var(--gray-400); cursor: help;"
                       data-bs-toggle="tooltip"
                       data-bs-placement="top"
                       data-bs-html="true"
                       title="<div style='text-align:left; font-size:12px; line-height:1.5;'>
                           <strong>What is confidence?</strong><br>
                           Every prediction includes an <strong>80% interval</strong> produced by two extra XGBoost models trained on the 10th and 90th percentile.<br><br>
                           <strong>Confidence = 1 − (interval width ÷ predicted yield)</strong><br>
                           A tight interval → high confidence.<br><br>
                           It is <em>not</em> accuracy — it measures how certain the model is about its own range.
                       </div>"></i>
                </div>
                <div class="value" style="font-size: 26px;">
                    @if($avgConfidence !== null)
                        {{ number_format($avgConfidence * 100, 1) }}<span style="font-size: 15px; color: var(--gray-400);">%</span>
                    @else
                        <span style="font-size: 18px; color: var(--gray-400);">—</span>
                    @endif
                </div>
                <div class="text-muted small">Across all predictions</div>
            </div>
            <div class="stat-icon"><i class="bi bi-activity" style="color: #4f46e5;"></i></div>
        </div>
    </div>
</div>

{{-- ACTIONS --}}
<div class="d-flex justify-content-end align-items-center gap-2 mb-3">
    @if($pendingFarmRecords > 0)
        <button type="button" class="btn btn-success" id="generateAllBtn" onclick="generateAll()">
            <i class="bi bi-lightning-charge-fill"></i>
            Generate All
            <span class="badge bg-light text-dark ms-1">{{ $pendingFarmRecords }}</span>
        </button>
    @endif

    @if($hasPredictions)
        <button type="button" class="btn btn-warning" onclick="openRegenerateModal()">
            <i class="bi bi-arrow-repeat"></i>
            Regenerate All
            <span class="badge bg-dark text-white ms-1">{{ $totalRecords }}</span>
        </button>
    @endif
</div>

@if($pendingFarmRecords > 0)
    <div class="alert alert-info d-flex align-items-center mb-3" style="border-radius: 12px; padding: 12px 18px;">
        <i class="bi bi-info-circle-fill me-2" style="font-size: 20px;"></i>
        <div style="font-size: 13px;">
            <strong>{{ $pendingFarmRecords }}</strong> vegetative farm record{{ $pendingFarmRecords !== 1 ? 's' : '' }} don't have a prediction yet.
            Click <strong>Generate All</strong> to process them at once.
        </div>
    </div>
@endif

{{-- FILTER CONTROLS --}}
<div class="row g-2 mb-3">
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
                        <input type="text" id="searchPrediction" class="form-control" placeholder="Search by farm, variety, season..." style="border: none; background: var(--gray-50); font-size: 13px;">
                    </div>
                </div>

                {{-- BARANGAY --}}
                <div style="min-width: 160px;">
                    <select id="filterBarangay" class="form-select"
                            title="Filter by barangay"
                            style="border-radius: 12px; border: 1px solid var(--gray-200); background: var(--gray-50); font-size: 13px; padding: 0.45rem 2rem 0.45rem 1rem; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.7rem; cursor: pointer; appearance: none; width: auto; min-width: 150px;">
                        <option value="all">All Barangays</option>
                        @foreach($uniquePredictions->pluck('farmRecord.farm.barangay')->unique()->filter()->values() as $barangay)
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

            {{-- ─────────── ROW 2: Pills ─────────── --}}
            <div class="d-flex flex-wrap align-items-center gap-3 pt-3" style="border-top: 1px dashed var(--gray-200);">

                {{-- SEASON GROUP --}}
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="font-size: 10px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase;">Season</span>
                    <div class="d-flex" style="background: var(--gray-100); border-radius: 12px; padding: 4px; gap: 2px;">
                        <button class="filter-btn season-btn active" data-season="all" style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: white; color: var(--gray-900); box-shadow: 0 1px 2px rgba(0,0,0,0.05); white-space: nowrap;">All</button>
                        @foreach($uniquePredictions->pluck('farmRecord.season')->filter()->map(fn($s) => str_replace(' Season', '', $s))->unique()->values() as $seasonOpt)
                            <button class="filter-btn season-btn" data-season="{{ $seasonOpt }}" style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: transparent; color: var(--gray-600); white-space: nowrap;">{{ $seasonOpt }}</button>
                        @endforeach
                    </div>
                </div>

                <div style="width: 1px; height: 28px; background: var(--gray-200);"></div>

                {{-- YIELD CLASS GROUP --}}
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="font-size: 10px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase;">Yield Class</span>
                    <div class="d-flex" style="background: var(--gray-100); border-radius: 12px; padding: 4px; gap: 2px;">
                        <button class="filter-btn class-btn active" data-class="all"    style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: white; color: var(--gray-900); box-shadow: 0 1px 2px rgba(0,0,0,0.05); white-space: nowrap;">All</button>
                        <button class="filter-btn class-btn"        data-class="High"   style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: transparent; color: var(--gray-600); white-space: nowrap;">High</button>
                        <button class="filter-btn class-btn"        data-class="Medium" style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: transparent; color: var(--gray-600); white-space: nowrap;">Medium</button>
                        <button class="filter-btn class-btn"        data-class="Low"    style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: transparent; color: var(--gray-600); white-space: nowrap;">Low</button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- TABLE --}}
<div class="row g-3">
    <div class="col-12">
        <div class="card-custom">
            <div class="card-title">
                <i class="bi bi-table"></i> Prediction Records
                <span class="badge bg-light text-muted ms-1" id="visibleCount" style="font-weight: 400; font-size: 10px;">
                    {{ $totalRecords }} total
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Farm</th>
                            <th>Barangay</th>
                            <th>Variety</th>
                            <th>Season</th>
                            <th>Predicted Yield</th>
                            <th>
                                Yield Class
                                <i class="bi bi-question-circle-fill"
                                   style="font-size: 12px; color: var(--gray-400); cursor: help;"
                                   data-bs-toggle="tooltip"
                                   data-bs-placement="top"
                                   data-bs-html="true"
                                   title="<div style='text-align:left; font-size:12px; line-height:1.5;'>
                                       <strong>Variety-relative class</strong><br>
                                       Compared against the variety's <em>own</em> average yield for the chosen seeding method.<br><br>
                                       <strong>Bands:</strong><br>
                                       • <strong>High</strong> — ≥ 112.5% of variety average<br>
                                       • <strong>Medium</strong> — 87.5% – 112.5%<br>
                                       • <strong>Low</strong> — below 87.5%<br><br>
                                       <em>Derived in the application — not a model output.</em>
                                   </div>"></i>
                            </th>
                            <th>
                                Confidence
                                <i class="bi bi-question-circle-fill"
                                   style="font-size: 12px; color: var(--gray-400); cursor: help;"
                                   data-bs-toggle="tooltip"
                                   data-bs-placement="top"
                                   data-bs-html="true"
                                   title="<div style='text-align:left; font-size:12px; line-height:1.5;'>
                                       <strong>How confidence works</strong><br>
                                       The model predicts a <strong>range</strong>: a 10th-percentile lower bound and a 90th-percentile upper bound, forming an <em>80% prediction interval</em>.<br><br>
                                       <strong>Confidence = 1 − (interval width ÷ predicted yield)</strong><br>
                                       Narrow interval → high confidence. Wide → low confidence.
                                   </div>"></i>
                            </th>
                            <th>Last Updated</th>
                            @if(auth()->user()->role !== 'farmer')
                                <th style="white-space: nowrap; min-width: 100px;">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody id="predictionsBody">
                        @forelse($uniquePredictions as $prediction)
                            @php
                                $farmName   = $prediction->farmRecord->farm->name ?? 'N/A';
                                $barangay   = $prediction->farmRecord->farm->barangay ?? 'N/A';
                                $variety    = $prediction->farmRecord->riceVariety->name ?? 'N/A';
                                $season     = $prediction->farmRecord->season ?? 'N/A';
                                $seasonNorm = str_replace(' Season', '', $season);
                                $yield      = $prediction->predicted_yield_tons_ha;
                                $conf       = $prediction->confidence;
                                $lo         = $prediction->yield_lower;
                                $hi         = $prediction->yield_upper;
                                $varietyId  = $prediction->farmRecord->rice_variety_id ?? null;

                                // ── App-level yield class (NOT from ML model) ──
                                $method       = $prediction->farmRecord->seeding_method ?? 'Transplanted';
                                $varietyModel = $prediction->farmRecord->riceVariety;
                                $methodYield  = $varietyModel ? $varietyModel->getYieldForMethod($method) : null;
                                $avgYield     = $methodYield->avg ?? ($varietyModel->avg_yield ?? null);

                                $yieldClass = null;
                                $ratio      = null;
                                if ($avgYield && $avgYield > 0) {
                                    $ratio = $yield / $avgYield;
                                    if ($ratio >= 1.125)     $yieldClass = 'High';
                                    elseif ($ratio >= 0.875) $yieldClass = 'Medium';
                                    else                     $yieldClass = 'Low';
                                }

                                $clsBadge = match($yieldClass) {
                                    'High'   => 'high',
                                    'Medium' => 'medium',
                                    'Low'    => 'low',
                                    default  => 'medium',
                                };

                                $searchData = strtolower($farmName . ' ' . $variety . ' ' . $season . ' ' . $barangay);
                            @endphp
                            <tr class="prediction-row"
                                data-search="{{ $searchData }}"
                                data-barangay="{{ $barangay }}"
                                data-class="{{ $yieldClass ?? '' }}"
                                data-season="{{ $seasonNorm }}">
                                <td class="row-index">{{ $loop->iteration }}</td>
                                <td><strong>{{ $farmName }}</strong></td>
                                <td>{{ $barangay }}</td>
                                <td>
                                    @if($varietyId)
                                        <span style="cursor:pointer; color: var(--green); text-decoration: underline;" onclick="viewVarietyDetails({{ $varietyId }})">
                                            {{ $variety }}
                                        </span>
                                    @else
                                        {{ $variety }}
                                    @endif
                                </td>
                                <td>{{ $season }}</td>
                                <td><strong style="color: #0f4c2b;">{{ number_format($yield, 2) }}</strong></td>
                                <td>
                                    @if($yieldClass)
                                        <span class="badge-status {{ $clsBadge }}"
                                              style="cursor: help;"
                                              data-bs-toggle="tooltip"
                                              data-bs-placement="top"
                                              data-bs-html="true"
                                              title="<div style='text-align:left; font-size:12px; line-height:1.5;'>
                                                  <strong>Variety-relative class</strong><br>
                                                  Predicted yield: <strong>{{ number_format($yield, 2) }} t/ha</strong><br>
                                                  Variety average ({{ $method }}): <strong>{{ number_format($avgYield, 2) }} t/ha</strong><br>
                                                  Ratio: <strong>{{ number_format($ratio * 100, 1) }}%</strong> of average<br><br>
                                                  <strong>Bands:</strong><br>
                                                  • High — ≥ 112.5%<br>
                                                  • Medium — 87.5% – 112.5%<br>
                                                  • Low — below 87.5%
                                              </div>">
                                            <span class="dot"></span> {{ $yieldClass }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($conf !== null)
                                        <span class="small"
                                              data-bs-toggle="tooltip"
                                              data-bs-placement="left"
                                              title="{{ $lo !== null && $hi !== null ? '80% interval: ' . number_format($lo, 2) . ' – ' . number_format($hi, 2) . ' t/ha' : '' }}">
                                            <strong style="color: var(--slate-700);">{{ number_format($conf * 100, 1) }}%</strong>
                                            @if($lo !== null && $hi !== null)
                                                <br><span class="text-muted" style="font-size: 10px;">
                                                    {{ number_format($lo, 2) }}–{{ number_format($hi, 2) }}
                                                </span>
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $prediction->updated_at->diffForHumans() }}</td>

                                @if(auth()->user()->role !== 'farmer')
                                    <td style="white-space: nowrap;">
                                        <button type="button"
                                                class="btn btn-sm btn-secondary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#viewPredictionModal"
                                                data-farm-record-id="{{ $prediction->farm_record_id }}"
                                                title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        <a href="{{ route('admin.predictions.history', $prediction->farm_record_id) }}"
                                           class="btn btn-sm btn-secondary"
                                           title="Full History">
                                            <i class="bi bi-clock-history"></i>
                                        </a>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->role !== 'farmer' ? 10 : 9 }}" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 28px;"></i>
                                    <p class="mt-2 mb-0">No predictions generated yet.</p>
                                </td>
                            </tr>
                        @endforelse
                        <tr id="noFilterResults" style="display: none;">
                            <td colspan="{{ auth()->user()->role !== 'farmer' ? 10 : 9 }}" class="text-center py-4 text-muted">
                                <i class="bi bi-search" style="font-size: 28px;"></i>
                                <p class="mt-2 mb-0">No predictions match your filters.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

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
                <nav aria-label="Prediction pagination">
                    <ul id="paginationList" class="pagination mb-0" style="display: flex; align-items: center; gap: 4px; list-style: none; padding: 0; margin: 0;"></ul>
                </nav>
            </div>
        </div>
    </div>
</div>

{{-- VIEW MODAL --}}
@if(auth()->user()->role !== 'farmer')
    <div class="modal fade" id="viewPredictionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--green); color: white;">
                    <h5 class="modal-title"><i class="bi bi-eye"></i> Prediction Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewPredictionModalBody">
                    <div class="text-center py-4" id="viewModalLoading">
                        <div class="spinner-border text-success" role="status"></div>
                        <p class="mt-2">Loading prediction details...</p>
                    </div>
                    <div id="viewModalContent" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- REGENERATE MODAL --}}
    <div class="modal fade" id="regenerateConfirmModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--yellow); color: #5a4a00;">
                    <h5 class="modal-title"><i class="bi bi-arrow-repeat"></i> Regenerate All Predictions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" id="regenerateCloseBtn"></button>
                </div>

                <div class="modal-body">

                    {{-- ── DEFAULT (confirm) STATE ── --}}
                    <div id="regenerateConfirmBody">
                        <h6 class="fw-bold mb-2">Regenerate all {{ $totalRecords }} prediction{{ $totalRecords !== 1 ? 's' : '' }}?</h6>
                        <p class="text-muted small mb-0">
                            Refreshes every prediction using the latest weather data. Previous predictions stay in each farm record's history.
                        </p>
                    </div>

                    {{-- ── LOADING STATE ── --}}
                    <div id="regenerateLoadingBody" class="text-center py-4" style="display: none;">
                        <div class="spinner-border" style="color: var(--green);" role="status"></div>
                        <p class="mt-3 mb-0" style="font-size: 13px; font-weight: 600;">Regenerating predictions…</p>
                        <p class="text-muted small mb-0">Please don't close this window.</p>
                    </div>

                    {{-- ── SUCCESS STATE ── --}}
                    <div id="regenerateSuccessBody" class="text-center py-3" style="display: none;">
                        <i class="bi bi-check-circle-fill" style="font-size: 52px; color: #27ae60;"></i>
                        <h6 class="mt-3 mb-1" style="font-weight: 700;">Regeneration Complete</h6>
                        <p class="mb-0" style="font-size: 14px; color: var(--slate-600);" id="regenerateSuccessMessage"></p>

                        <div id="regenerateSuccessErrors" class="mt-3 text-start" style="display: none;">
                            <div class="alert alert-warning small mb-0" style="border-radius: 10px;">
                                <strong><i class="bi bi-exclamation-triangle"></i> Some records failed:</strong>
                                <ul class="mb-0 mt-1 ps-3" id="regenerateSuccessErrorsList"></ul>
                            </div>
                        </div>
                    </div>

                    {{-- ── ERROR STATE ── --}}
                    <div id="regenerateErrorBody" class="text-center py-3" style="display: none;">
                        <i class="bi bi-x-circle-fill" style="font-size: 52px; color: var(--red);"></i>
                        <h6 class="mt-3 mb-1" style="font-weight: 700;">Regeneration Failed</h6>
                        <p class="mb-0" style="font-size: 13px; color: var(--slate-600);" id="regenerateErrorMessage"></p>
                    </div>

                </div>

                <div class="modal-footer" id="regenerateFooter">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="regenerateCancelBtn">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-warning" id="confirmRegenerateBtn" onclick="confirmRegenerate()">
                        <i class="bi bi-arrow-repeat"></i> Yes, Regenerate All
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- VARIETY MODAL --}}
<div class="modal fade" id="varietyDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--green); color: white;">
                <h5 class="modal-title"><i class="bi bi-flower1"></i> Rice Variety Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="varietyDetailModalBody">
                <div class="text-center py-4" id="varietyDetailLoading">
                    <div class="spinner-border text-success" role="status"></div>
                    <p class="mt-2">Loading variety details...</p>
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
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el, { container: 'body' });
        });
    });

    // ─── FILTERING + PAGINATION ─────────────────────────────
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput    = document.getElementById('searchPrediction');
        const barangaySelect = document.getElementById('filterBarangay');
        const classBtns      = document.querySelectorAll('.class-btn');
        const seasonBtns     = document.querySelectorAll('.season-btn');
        const allRows        = Array.from(document.querySelectorAll('#predictionsBody .prediction-row'));
        const noResults      = document.getElementById('noFilterResults');
        const visibleCount   = document.getElementById('visibleCount');
        const paginationBar  = document.getElementById('paginationBar');
        const paginationList = document.getElementById('paginationList');
        const paginationInfo = document.getElementById('paginationInfo');
        const perPageSelect  = document.getElementById('perPageSelect');
        const resetBtn       = document.getElementById('resetFiltersBtn');

        if (!searchInput) return;

        let currentPage = 1;
        let perPage = parseInt(perPageSelect?.value || '25', 10);

        function hasActiveFilters() {
            const activeClass  = document.querySelector('.class-btn.active');
            const activeSeason = document.querySelector('.season-btn.active');
            return (
                searchInput.value.trim() !== '' ||
                barangaySelect.value !== 'all' ||
                (activeClass  && activeClass.dataset.class   !== 'all') ||
                (activeSeason && activeSeason.dataset.season !== 'all')
            );
        }

        function getMatchingRows() {
            const search   = searchInput.value.toLowerCase().trim();
            const barangay = barangaySelect.value;
            const activeClass  = document.querySelector('.class-btn.active');
            const classFilter  = activeClass  ? activeClass.dataset.class   : 'all';
            const activeSeason = document.querySelector('.season-btn.active');
            const seasonFilter = activeSeason ? activeSeason.dataset.season : 'all';

            return allRows.filter(row => {
                const sd = row.dataset.search   || '';
                const rb = row.dataset.barangay || '';
                const rc = row.dataset.class    || '';
                const rs = row.dataset.season   || '';
                const matchesSearch   = sd.includes(search);
                const matchesBarangay = (barangay === 'all' || rb === barangay);
                const matchesClass    = (classFilter === 'all' || rc === classFilter);
                const matchesSeason   = (seasonFilter === 'all' || rs === seasonFilter);
                return matchesSearch && matchesBarangay && matchesClass && matchesSeason;
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

        classBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                classBtns.forEach(b => {
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

            classBtns.forEach(b => {
                b.style.background = 'transparent';
                b.style.color = 'var(--gray-600)';
                b.style.boxShadow = 'none';
                b.classList.remove('active');
            });
            const classAll = document.querySelector('.class-btn[data-class="all"]');
            if (classAll) {
                classAll.style.background = 'white';
                classAll.style.color = 'var(--gray-900)';
                classAll.style.boxShadow = '0 1px 2px rgba(0,0,0,0.05)';
                classAll.classList.add('active');
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

    @if(auth()->user()->role !== 'farmer')

    // ─── REGENERATE ALL ─────────────────────────────────────
    let regenerateInProgress = false;
    let regenerateModal = null;

    function openRegenerateModal() {
        // Reset to default state every time the modal is opened
        document.getElementById('regenerateConfirmBody').style.display = 'block';
        document.getElementById('regenerateLoadingBody').style.display = 'none';
        document.getElementById('regenerateSuccessBody').style.display = 'none';
        document.getElementById('regenerateErrorBody').style.display  = 'none';

        document.getElementById('regenerateFooter').style.display = 'flex';
        document.getElementById('regenerateFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="regenerateCancelBtn">
                <i class="bi bi-x-circle"></i> Cancel
            </button>
            <button type="button" class="btn btn-warning" id="confirmRegenerateBtn" onclick="confirmRegenerate()">
                <i class="bi bi-arrow-repeat"></i> Yes, Regenerate All
            </button>
        `;

        document.getElementById('regenerateCloseBtn').disabled = false;
        regenerateInProgress = false;

        regenerateModal = bootstrap.Modal.getOrCreateInstance(
            document.getElementById('regenerateConfirmModal')
        );
        regenerateModal.show();
    }

    function confirmRegenerate() {
        if (regenerateInProgress) return;
        regenerateInProgress = true;

        // Switch to loading state
        document.getElementById('regenerateConfirmBody').style.display = 'none';
        document.getElementById('regenerateLoadingBody').style.display = 'block';
        document.getElementById('regenerateSuccessBody').style.display = 'none';
        document.getElementById('regenerateErrorBody').style.display   = 'none';
        document.getElementById('regenerateFooter').style.display      = 'none';
        document.getElementById('regenerateCloseBtn').disabled         = true;

        fetch('{{ route("admin.predictions.regenerate-all") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
            },
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('regenerateLoadingBody').style.display = 'none';
            document.getElementById('regenerateCloseBtn').disabled = false;

            if (data.success) {
                // ── SUCCESS ──
                document.getElementById('regenerateSuccessBody').style.display = 'block';
                document.getElementById('regenerateSuccessMessage').textContent =
                    data.message || 'All predictions regenerated.';

                const errBox  = document.getElementById('regenerateSuccessErrors');
                const errList = document.getElementById('regenerateSuccessErrorsList');
                errList.innerHTML = '';
                if (data.failed > 0 && Array.isArray(data.errors) && data.errors.length) {
                    data.errors.forEach(e => {
                        const li = document.createElement('li');
                        li.textContent = e;
                        errList.appendChild(li);
                    });
                    errBox.style.display = 'block';
                } else {
                    errBox.style.display = 'none';
                }

                // Footer gets a single "Done" action that reloads the page
                document.getElementById('regenerateFooter').style.display = 'flex';
                document.getElementById('regenerateFooter').innerHTML = `
                    <button type="button" class="btn btn-success" onclick="location.reload()">
                        <i class="bi bi-check-circle"></i> Done
                    </button>
                `;

            } else {
                // ── FAILURE ──
                document.getElementById('regenerateErrorBody').style.display = 'block';
                document.getElementById('regenerateErrorMessage').textContent =
                    data.error || 'Unknown error occurred.';

                document.getElementById('regenerateFooter').style.display = 'flex';
                document.getElementById('regenerateFooter').innerHTML = `
                    <button type="button" class="btn btn-secondary" onclick="closeRegenerateModal()">
                        <i class="bi bi-x-circle"></i> Close
                    </button>
                    <button type="button" class="btn btn-warning" onclick="openRegenerateModal()">
                        <i class="bi bi-arrow-repeat"></i> Try Again
                    </button>
                `;
                regenerateInProgress = false;
            }
        })
        .catch(err => {
            document.getElementById('regenerateLoadingBody').style.display = 'none';
            document.getElementById('regenerateErrorBody').style.display   = 'block';
            document.getElementById('regenerateErrorMessage').textContent  = 'Network error: ' + err;
            document.getElementById('regenerateCloseBtn').disabled         = false;

            document.getElementById('regenerateFooter').style.display = 'flex';
            document.getElementById('regenerateFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeRegenerateModal()">
                    <i class="bi bi-x-circle"></i> Close
                </button>
                <button type="button" class="btn btn-warning" onclick="openRegenerateModal()">
                    <i class="bi bi-arrow-repeat"></i> Try Again
                </button>
            `;
            regenerateInProgress = false;
        });
    }

    function closeRegenerateModal() {
        if (regenerateModal) regenerateModal.hide();
    }

    // ─── GENERATE ALL ───────────────────────────────────────
    function generateAll() {
        const btn = document.getElementById('generateAllBtn');
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generating...';

        fetch('{{ route("admin.predictions.generate-all") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (data.generated === 0) {
                    alert('No pending farm records to predict.');
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                } else {
                    let msg = `✅ ${data.message}`;
                    if (data.failed > 0 && data.errors?.length) msg += '\n\nFailed:\n' + data.errors.join('\n');
                    alert(msg);
                    location.reload();
                }
            } else {
                alert('Error: ' + (data.error || 'Unknown'));
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }
        })
        .catch(err => {
            alert('Network error: ' + err);
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        });
    }

    // ─── VIEW MODAL ─────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function() {
        const viewModal = document.getElementById('viewPredictionModal');
        if (!viewModal) return;

        viewModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button) return;
            const farmRecordId = button.getAttribute('data-farm-record-id');
            if (!farmRecordId) return;

            document.getElementById('viewModalLoading').style.display = 'block';
            document.getElementById('viewModalContent').style.display = 'none';

            fetch('/admin/predictions/' + farmRecordId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('viewModalLoading').style.display = 'none';
                    document.getElementById('viewModalContent').style.display = 'block';
                    document.getElementById('viewModalContent').innerHTML = html;
                })
                .catch(() => {
                    document.getElementById('viewModalLoading').style.display = 'none';
                    document.getElementById('viewModalContent').style.display = 'block';
                    document.getElementById('viewModalContent').innerHTML = '<div class="alert alert-danger">Failed to load.</div>';
                });
        });
    });

    // ─── VARIETY DETAILS ────────────────────────────────────
    function viewVarietyDetails(id) {
        document.getElementById('varietyDetailLoading').style.display = 'block';
        document.getElementById('varietyDetailContent').style.display = 'none';

        fetch('/admin/rice-varieties/' + id + '/details')
            .then(response => response.text())
            .then(html => {
                document.getElementById('varietyDetailLoading').style.display = 'none';
                document.getElementById('varietyDetailContent').style.display = 'block';
                document.getElementById('varietyDetailContent').innerHTML = html;
            })
            .catch(() => {
                document.getElementById('varietyDetailLoading').style.display = 'none';
                document.getElementById('varietyDetailContent').style.display = 'block';
                document.getElementById('varietyDetailContent').innerHTML = '<div class="alert alert-danger">Failed to load.</div>';
            });

        new bootstrap.Modal(document.getElementById('varietyDetailModal')).show();
    }
    @endif
</script>
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