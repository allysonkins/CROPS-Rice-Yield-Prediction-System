@extends('layouts.app')

@section('title', 'Rice Yield Predictions')

@section('content')

{{-- ═══════════════ HEADER ═══════════════ --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 style="font-weight: 800; color: var(--slate-900); margin: 0;">
            <i class="bi bi-graph-up-arrow" style="color: var(--brand-green);"></i>
            Yield Predictions
        </h4>
        <p class="text-muted mb-0" style="font-size: 12.5px;">
            @if($predictions->isEmpty() && $pendingFarmRecords === 0)
                Yield estimates are generated from farm records. Add one to get started.
            @else
                Yield estimates generated from your farm records. Compare across years and varieties.
            @endif
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.predictions.compare') }}" class="btn btn-outline-success">
            <i class="bi bi-bar-chart-line"></i> Compare Years
        </a>

        @if($pendingFarmRecords > 0)
            <button type="button" class="btn btn-success" id="generateAllBtn" onclick="generateAll()">
                <i class="bi bi-lightning-charge-fill"></i>
                Generate All
                <span class="badge bg-light text-dark ms-1">{{ $pendingFarmRecords }}</span>
            </button>
        @endif

        @if($totalPredictions > 0)
            <button type="button" class="btn btn-warning" onclick="openRegenerateModal()">
                <i class="bi bi-arrow-repeat"></i>
                Regenerate All
                <span class="badge bg-dark text-white ms-1">{{ $totalPredictions }}</span>
            </button>
        @endif
    </div>
</div>

{{-- ═══════════════ STATS ═══════════════ --}}
<div class="row g-2 mb-3">
    <div class="col-12 col-sm-4">
        <div class="stat-card" style="border-left-color: var(--brand-green);">
            <div class="stat-info">
                <div class="label" style="text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;">
                    Average City Yield
                </div>
                <div class="value" style="font-size: 26px;">
                    @if($avgCityYield !== null)
                        {{ number_format($avgCityYield, 2) }}
                        <span style="font-size: 15px; color: var(--slate-400);">t/ha</span>
                    @else
                        <span style="font-size: 18px; color: var(--slate-400);">No data</span>
                    @endif
                </div>
                @if($avgCityYield !== null)
                    <div class="text-muted small">
                        Based on {{ $totalPredictions }} prediction{{ $totalPredictions !== 1 ? 's' : '' }}
                    </div>
                @endif
            </div>
            <div class="stat-icon"><i class="bi bi-graph-up-arrow" style="color: var(--brand-green);"></i></div>
        </div>
    </div>

    <div class="col-12 col-sm-4">
        <div class="stat-card" style="border-left-color: var(--brand-gold);">
            <div class="stat-info">
                <div class="label" style="text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;">
                    Total Predictions
                </div>
                <div class="value" style="font-size: 26px;">{{ $totalPredictions }}</div>
                <div class="text-muted small">XGBoost Regressor</div>
            </div>
            <div class="stat-icon"><i class="bi bi-cpu" style="color: var(--brand-gold);"></i></div>
        </div>
    </div>

    <div class="col-12 col-sm-4">
        <div class="stat-card" style="border-left-color: #4f46e5;">
            <div class="stat-info">
                <div class="label" style="text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;">
                    Avg Confidence
                    <i class="bi bi-question-circle-fill"
                       style="font-size: 11px; color: var(--slate-400); cursor: help;"
                       data-bs-toggle="tooltip"
                       data-bs-placement="top"
                       data-bs-html="true"
                       title="<div style='text-align:left; font-size:12px; line-height:1.5;'>
                           <strong>What is confidence?</strong><br>
                           Every prediction includes an <strong>80% interval</strong> produced by two extra XGBoost models trained on the 10th and 90th percentile.<br><br>
                           <strong>Confidence = 1 − (half-width ÷ predicted yield)</strong><br>
                           A tight interval → high confidence.<br><br>
                           It is <em>not</em> accuracy — it measures how certain the model is about its own range.
                       </div>"></i>
                </div>
                <div class="value" style="font-size: 26px;">
                    @if($avgConfidence !== null)
                        {{ number_format($avgConfidence * 100, 1) }}<span style="font-size: 15px; color: var(--slate-400);">%</span>
                    @else
                        <span style="font-size: 18px; color: var(--slate-400);">—</span>
                    @endif
                </div>
                <div class="text-muted small">Across all predictions</div>
            </div>
            <div class="stat-icon"><i class="bi bi-activity" style="color: #4f46e5;"></i></div>
        </div>
    </div>
</div>

{{-- ═══════════════ FILTERS ═══════════════ --}}
<form method="GET" action="{{ route('admin.predictions.index') }}" id="predictionFilterForm">
<div class="card-custom mb-3" style="padding: 16px 20px;">

    {{-- ROW 1: Search + Year + Barangay + Reset --}}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">

        {{-- SEARCH --}}
        <div style="flex: 1; min-width: 240px;">
            <div class="input-group" style="border-radius: 12px; overflow: hidden; border: 1px solid var(--gray-200);">
                <span class="input-group-text" style="background: var(--gray-50); border: none; color: var(--gray-400);">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" name="q" class="form-control"
                       value="{{ request('q') }}"
                       placeholder="Search farmer, RSBSA, farm, variety, season..."
                       style="border: none; background: var(--gray-50); font-size: 13px;">
            </div>
        </div>

        {{-- YEAR (server-side reload) --}}
        <div style="min-width: 140px;">
            <select name="year" class="form-select"
                    onchange="this.form.submit()"
                    title="Filter by year"
                    style="border-radius: 12px; border: 1px solid var(--gray-200); background: var(--gray-50); font-size: 13px; padding: 0.45rem 2rem 0.45rem 1rem; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.7rem; cursor: pointer; appearance: none;">
                <option value="all" {{ request('year', 'all') === 'all' ? 'selected' : '' }}>All Years</option>
                @foreach($availableYears as $y)
                    <option value="{{ $y }}" {{ (string) request('year') === (string) $y ? 'selected' : '' }}>
                        {{ $y }}{{ (int) $y === (int) now()->year ? ' (Current)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- BARANGAY --}}
        <div style="min-width: 160px;">
            <select name="barangay" class="form-select"
                    title="Filter by barangay"
                    style="border-radius: 12px; border: 1px solid var(--gray-200); background: var(--gray-50); font-size: 13px; padding: 0.45rem 2rem 0.45rem 1rem; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.7rem; cursor: pointer; appearance: none; width: auto; min-width: 150px;">
                <option value="">All Barangays</option>
                @foreach($barangays as $b)
                    <option value="{{ $b }}" {{ request('barangay') === $b ? 'selected' : '' }}>{{ $b }}</option>
                @endforeach
            </select>
        </div>

        {{-- RESET --}}
        <a href="{{ route('admin.predictions.index') }}" class="btn btn-sm btn-outline-secondary"
           style="border-radius: 10px; font-size: 12px;"
           title="Clear all filters">
            <i class="bi bi-x-circle"></i> Reset
        </a>
    </div>

    {{-- ROW 2: Pills + Advanced filters (Farm Records style) --}}
    <div class="d-flex flex-wrap align-items-center gap-3 pt-3" style="border-top: 1px dashed var(--gray-200);">

        {{-- SEASON GROUP --}}
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted" style="font-size: 10px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase;">Season</span>
            <div class="d-flex" style="background: var(--gray-100); border-radius: 12px; padding: 4px; gap: 2px;">
                <button type="submit" name="season" value=""
                        class="filter-btn season-btn {{ !request('season') ? 'active' : '' }}"
                        style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; {{ !request('season') ? 'background: white; color: var(--gray-900); box-shadow: 0 1px 2px rgba(0,0,0,0.05);' : 'background: transparent; color: var(--gray-600);' }} white-space: nowrap;">
                    All
                </button>
                <button type="submit" name="season" value="Dry"
                        class="filter-btn season-btn {{ request('season') === 'Dry' ? 'active' : '' }}"
                        style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; {{ request('season') === 'Dry' ? 'background: white; color: var(--gray-900); box-shadow: 0 1px 2px rgba(0,0,0,0.05);' : 'background: transparent; color: var(--gray-600);' }} white-space: nowrap;">
                    Dry
                </button>
                <button type="submit" name="season" value="Wet"
                        class="filter-btn season-btn {{ request('season') === 'Wet' ? 'active' : '' }}"
                        style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; {{ request('season') === 'Wet' ? 'background: white; color: var(--gray-900); box-shadow: 0 1px 2px rgba(0,0,0,0.05);' : 'background: transparent; color: var(--gray-600);' }} white-space: nowrap;">
                    Wet
                </button>
            </div>
        </div>

        <div style="width: 1px; height: 28px; background: var(--gray-200);"></div>

        {{-- RECORD STATUS GROUP --}}
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted" style="font-size: 10px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase;">Status</span>
            <div class="d-flex" style="background: var(--gray-100); border-radius: 12px; padding: 4px; gap: 2px;">
                <button type="submit" name="record_status" value=""
                        class="filter-btn status-btn {{ !request('record_status') ? 'active' : '' }}"
                        style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; {{ !request('record_status') ? 'background: white; color: var(--gray-900); box-shadow: 0 1px 2px rgba(0,0,0,0.05);' : 'background: transparent; color: var(--gray-600);' }} white-space: nowrap;">
                    All
                </button>
                <button type="submit" name="record_status" value="Vegetative"
                        class="filter-btn status-btn {{ request('record_status') === 'Vegetative' ? 'active' : '' }}"
                        style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; {{ request('record_status') === 'Vegetative' ? 'background: white; color: var(--gray-900); box-shadow: 0 1px 2px rgba(0,0,0,0.05);' : 'background: transparent; color: var(--gray-600);' }} white-space: nowrap;">
                    Growing
                </button>
                <button type="submit" name="record_status" value="Harvested"
                        class="filter-btn status-btn {{ request('record_status') === 'Harvested' ? 'active' : '' }}"
                        style="padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; {{ request('record_status') === 'Harvested' ? 'background: white; color: var(--gray-900); box-shadow: 0 1px 2px rgba(0,0,0,0.05);' : 'background: transparent; color: var(--gray-600);' }} white-space: nowrap;">
                    Harvested
                </button>
            </div>
        </div>

        <div style="width: 1px; height: 28px; background: var(--gray-200);"></div>

        {{-- VARIETY --}}
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted" style="font-size: 10px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase;">Variety</span>
            <select name="variety" class="form-select"
                    style="border-radius: 10px; border: 1px solid var(--gray-200); background: var(--gray-50); font-size: 12px; padding: 0.4rem 2rem 0.4rem 0.85rem; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.65rem center; background-size: 0.65rem; cursor: pointer; appearance: none; width: auto; min-width: 160px;">
                <option value="">All Varieties</option>
                @foreach($varieties as $v)
                    <option value="{{ $v->id }}" {{ (string) request('variety') === (string) $v->id ? 'selected' : '' }}>
                        {{ $v->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- PER PAGE --}}
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted" style="font-size: 10px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase;">Per Page</span>
            <select name="per_page" class="form-select"
                    onchange="this.form.submit()"
                    style="border-radius: 10px; border: 1px solid var(--gray-200); background: var(--gray-50); font-size: 12px; padding: 0.4rem 2rem 0.4rem 0.85rem; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.65rem center; background-size: 0.65rem; cursor: pointer; appearance: none; width: auto; min-width: 100px;">
                @foreach([10, 25, 50, 100] as $n)
                    <option value="{{ $n }}" {{ (int) request('per_page', 25) === $n ? 'selected' : '' }}>{{ $n }}</option>
                @endforeach
            </select>
        </div>

        {{-- APPLY BUTTON --}}
        <button type="submit" class="btn btn-sm btn-success ms-auto" style="border-radius: 10px;">
            <i class="bi bi-funnel"></i> Apply Filters
        </button>
    </div>

    {{-- Active filters summary --}}
    @php
        $activeFilters = array_filter([
            'Year'          => request('year') && request('year') !== 'all' ? request('year') : null,
            'Farmer'        => request('farmer'),
            'Variety'       => request('variety') ? optional($varieties->firstWhere('id', request('variety')))->name : null,
            'Season'        => request('season'),
            'Record Status' => request('record_status') ? (request('record_status') === 'Vegetative' ? 'Growing' : 'Harvested') : null,
            'Barangay'      => request('barangay'),
            'Search'        => request('q'),
        ]);
    @endphp
    @if(count($activeFilters))
        <div class="mt-2 pt-2 d-flex flex-wrap gap-1 align-items-center" style="border-top: 1px dashed var(--gray-200); font-size: 11px;">
            <span class="text-muted fw-bold">Active:</span>
            @foreach($activeFilters as $label => $value)
                <span class="badge bg-light text-dark border">{{ $label }}: {{ $value }}</span>
            @endforeach
        </div>
    @endif

</div>
</form>

@if($pendingFarmRecords > 0)
    <div class="alert alert-info d-flex align-items-center mb-3" style="border-radius: 12px; padding: 12px 18px;">
        <i class="bi bi-info-circle-fill me-2" style="font-size: 20px;"></i>
        <div style="font-size: 13px;">
            <strong>{{ $pendingFarmRecords }}</strong> vegetative farm record{{ $pendingFarmRecords !== 1 ? 's' : '' }} don't have a prediction yet.
            Click <strong>Generate All</strong> to process them at once.
        </div>
    </div>
@endif

{{-- ═══════════════ RESULTS TABLE ═══════════════ --}}
<div class="card-custom">
    <div class="card-title d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-table"></i>
            {{ $predictions->total() }} prediction{{ $predictions->total() === 1 ? '' : 's' }}
        </span>
        <span class="text-muted" style="font-size: 11px;">
            Page {{ $predictions->currentPage() }} of {{ $predictions->lastPage() }}
        </span>
    </div>

    @if($predictions->isEmpty())
        @php
            $hasActiveFilters = count(request()->except(['page'])) > 0;
        @endphp

        @if($hasActiveFilters)
            <div class="text-center py-5 text-muted">
                <i class="bi bi-search" style="font-size: 42px; color: var(--slate-300);"></i>
                <h5 class="mt-3 mb-2" style="color: var(--slate-700); font-weight: 700;">
                    No predictions match your filters
                </h5>
                <p class="mb-0" style="font-size: 13px; color: var(--slate-500);">
                    Try loosening a filter or clearing them all.
                </p>
                <a href="{{ route('admin.predictions.index') }}" class="btn btn-sm btn-outline-secondary mt-3">
                    <i class="bi bi-arrow-counterclockwise"></i> Clear All Filters
                </a>
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-cpu" style="font-size: 42px; color: var(--slate-300);"></i>
                <h5 class="mt-3 mb-2" style="color: var(--slate-700); font-weight: 700;">
                    No predictions yet
                </h5>
                <p class="mb-1" style="font-size: 13px; color: var(--slate-500); max-width: 480px; margin-left: auto; margin-right: auto;">
                    Predictions are generated automatically from farm records.
                    Add a farm record with status <strong>Growing</strong> and the system will generate a yield prediction for it.
                </p>
                <p class="mb-3" style="font-size: 12px; color: var(--slate-400);">
                    Already have farm records? Use <strong>Generate All</strong> above to predict them.
                </p>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    @if(auth()->user()->role !== 'farmer')
                        <a href="{{ route('admin.farm-records.index') }}" class="btn btn-sm btn-success">
                            <i class="bi bi-clipboard-data-fill"></i> Go to Farm Records
                        </a>
                    @else
                        <a href="{{ route('farmer.farm-records.index') }}" class="btn btn-sm btn-success">
                            <i class="bi bi-clipboard-data-fill"></i> My Farm Records
                        </a>
                    @endif
                </div>
            </div>
        @endif
    @else
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Farmer</th>
                        <th>Farm</th>
                        <th>Variety</th>
                        <th>Season / Year</th>
                        <th>Predicted Yield</th>
                        <th>
                            Yield Class
                            <i class="bi bi-question-circle-fill"
                               style="font-size: 12px; color: var(--slate-400); cursor: help;"
                               data-bs-toggle="tooltip"
                               data-bs-placement="top"
                               data-bs-html="true"
                               title="<div style='text-align:left; font-size:12px; line-height:1.5;'>
                                   <strong>Variety-relative class</strong><br>
                                   Compared against the variety's own average yield for the chosen seeding method.<br><br>
                                   • <strong>High</strong> — ≥ 112.5% of variety average<br>
                                   • <strong>Medium</strong> — 87.5% – 112.5%<br>
                                   • <strong>Low</strong> — below 87.5%<br><br>
                                   <em>Derived in the app — not a model output.</em>
                               </div>"></i>
                        </th>
                        <th>
                            Confidence
                            <i class="bi bi-question-circle-fill"
                               style="font-size: 12px; color: var(--slate-400); cursor: help;"
                               data-bs-toggle="tooltip"
                               data-bs-placement="top"
                               data-bs-html="true"
                               title="<div style='text-align:left; font-size:12px; line-height:1.5;'>
                                   <strong>How confidence works</strong><br>
                                   The model predicts an 80% interval from two quantile models. Confidence = 1 − (half-width ÷ predicted yield).<br>
                                   Narrow interval → high confidence.
                               </div>"></i>
                        </th>
                        <th>Status</th>
                        <th>Actual Yield</th>
                        <th>Generated</th>
                        <th style="white-space: nowrap; min-width: 130px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($predictions as $p)
                        @php
                            $record  = $p->farmRecord;
                            $farm    = $record?->farm;
                            $farmer  = $farm?->user;
                            $variety = $record?->riceVariety;
                            $yield   = $p->predicted_yield_tons_ha;
                            $conf    = $p->confidence;
                            $lo      = $p->yield_lower;
                            $hi      = $p->yield_upper;

                            $yieldClass = null;
                            $ratio      = null;
                            $avgYield   = null;
                            if ($record && $variety && $yield !== null) {
                                $method   = $record->seeding_method ?? 'Transplanted';
                                $methodY  = $variety->getYieldForMethod($method);
                                $avgYield = $methodY->avg ?? ($variety->avg_yield ?? null);
                                if ($avgYield && $avgYield > 0) {
                                    $ratio = $yield / $avgYield;
                                    if     ($ratio >= 1.125) $yieldClass = 'High';
                                    elseif ($ratio >= 0.875) $yieldClass = 'Medium';
                                    else                     $yieldClass = 'Low';
                                }
                            }
                            $clsBadge = match($yieldClass) {
                                'High'   => 'high',
                                'Medium' => 'medium',
                                'Low'    => 'low',
                                default  => 'medium',
                            };
                        @endphp
                        <tr>
                            <td>
                                @if($farmer)
                                    <span style="color: var(--brand-green); font-weight: 600;">{{ $farmer->name }}</span>
                                    @if($farmer->rsbsa_number || $farmer->phone)
                                        <br><small class="text-muted">{{ $farmer->rsbsa_number ?: $farmer->phone }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($farm)
                                    <strong>{{ $farm->name }}</strong>
                                    <br><small class="text-muted">{{ $farm->barangay ?? '' }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($variety)
                                    <span style="cursor:pointer; color: var(--brand-green); text-decoration: underline;"
                                          onclick="viewVarietyDetails({{ $variety->id }})"
                                          title="View variety details">
                                        {{ $variety->name }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($record)
                                    <span class="badge bg-light text-dark" style="font-weight: 600;">{{ $record->year }}</span>
                                    <br><small class="text-muted">{{ $record->season }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($yield !== null)
                                    <strong style="color: var(--brand-green);">{{ number_format($yield, 2) }}</strong>
                                    <small class="text-muted">t/ha</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($yieldClass)
                                    <span class="badge-status {{ $clsBadge }}"
                                          style="cursor: help;"
                                          data-bs-toggle="tooltip"
                                          data-bs-placement="top"
                                          data-bs-html="true"
                                          title="<div style='text-align:left; font-size:12px; line-height:1.5;'>
                                              <strong>Variety-relative class</strong><br>
                                              Predicted: <strong>{{ number_format($yield, 2) }} t/ha</strong><br>
                                              Variety average ({{ $record->seeding_method ?? 'Transplanted' }}): <strong>{{ number_format($avgYield, 2) }} t/ha</strong><br>
                                              Ratio: <strong>{{ number_format($ratio * 100, 1) }}%</strong>
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
                            <td>
                                @if($record && $record->status === 'Harvested')
                                    <span class="badge-status" style="background: var(--brand-green-light); color: var(--brand-green-dark); border-color: #c7e6d2;">
                                        <span class="dot" style="background: var(--brand-green);"></span> Harvested
                                    </span>
                                @else
                                    <span class="badge-status" style="background: var(--brand-gold-light); color: #92400e; border-color: #fde68a;">
                                        <span class="dot" style="background: var(--brand-gold);"></span> Growing
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($record && $record->status === 'Harvested' && $record->actual_yield_tons_ha !== null)
                                    @php
                                        $actual = (float) $record->actual_yield_tons_ha;
                                        $delta  = $yield !== null ? ($actual - $yield) : null;
                                        $deltaColor = $delta === null
                                            ? 'var(--slate-400)'
                                            : ($delta > 0.05 ? '#15803d' : ($delta < -0.05 ? '#b91c1c' : 'var(--slate-400)'));
                                        $deltaIcon = $delta === null
                                            ? 'dash'
                                            : ($delta > 0.05 ? 'arrow-up' : ($delta < -0.05 ? 'arrow-down' : 'dash'));
                                    @endphp
                                    <strong style="color: var(--slate-800);">{{ number_format($actual, 2) }}</strong>
                                    <small class="text-muted">t/ha</small>
                                    @if($delta !== null)
                                        <br>
                                        <span style="font-size: 10px; font-weight: 700; color: {{ $deltaColor }};">
                                            <i class="bi bi-{{ $deltaIcon }}"></i>
                                            {{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 2) }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ $p->created_at?->format('M d, Y g:i A') ?? '—' }}
                                    <br>
                                    <span style="font-size: 10px;">{{ $p->created_at?->diffForHumans() ?? '' }}</span>
                                </small>
                            </td>
                            <td style="white-space: nowrap;">
                                @if($record)
                                    <button type="button"
                                            class="btn btn-sm btn-secondary"
                                            title="View season record"
                                            onclick="openSeasonModal({{ $record->id }})">
                                        <i class="bi bi-clipboard-data"></i>
                                    </button>
                                    <a href="{{ route('admin.predictions.history', $record->id) }}"
                                       class="btn btn-sm btn-secondary"
                                       title="Prediction history">
                                        <i class="bi bi-clock-history"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $predictions->withQueryString()->links() }}
        </div>
    @endif
</div>

{{-- ═══════════════ SEASON DETAIL MODAL ═══════════════ --}}
<div class="modal fade" id="seasonDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-clipboard-data-fill"></i> Season Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-4" id="seasonDetailLoading">
                    <div class="spinner-border text-success" role="status"></div>
                    <p class="mt-2">Loading season record...</p>
                </div>
                <div id="seasonDetailContent" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════ REGENERATE MODAL ═══════════════ --}}
<div class="modal fade" id="regenerateConfirmModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-warning">
                <h5 class="modal-title"><i class="bi bi-arrow-repeat"></i> Regenerate All Predictions</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="regenerateCloseBtn"></button>
            </div>

            <div class="modal-body">
                <div id="regenerateConfirmBody">
                    <h6 class="fw-bold mb-2">
                        Regenerate all {{ $totalPredictions }} prediction{{ $totalPredictions !== 1 ? 's' : '' }}?
                    </h6>
                    <p class="text-muted small mb-0">
                        Refreshes every prediction using the latest weather data.
                        A new entry is added to each farm record's history.
                    </p>
                </div>

                <div id="regenerateLoadingBody" class="text-center py-4" style="display: none;">
                    <div class="spinner-border" style="color: var(--brand-green);" role="status"></div>
                    <p class="mt-3 mb-0" style="font-size: 13px; font-weight: 600;">Regenerating predictions…</p>
                    <p class="text-muted small mb-0">Please don't close this window.</p>
                </div>

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

                <div id="regenerateErrorBody" class="text-center py-3" style="display: none;">
                    <i class="bi bi-x-circle-fill" style="font-size: 52px; color: var(--brand-danger);"></i>
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

{{-- ═══════════════ VARIETY MODAL ═══════════════ --}}
<div class="modal fade" id="varietyDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-flower1"></i> Rice Variety Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
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
async function safeFetchJson(url, options = {}) {
    let response;
    try {
        response = await fetch(url, options);
    } catch (netErr) {
        return { ok: false, status: 0, error: 'Network error. Please check your connection and try again.' };
    }
    const ct = response.headers.get('content-type') || '';
    if (!ct.includes('application/json')) {
        const raw = await response.text();
        console.error('[safeFetchJson] Non-JSON from', url, raw.slice(0, 500));
        let hint = 'The server returned an unexpected response.';
        if (response.status === 419)      hint = 'Your session expired. Refresh the page and try again.';
        else if (response.status === 500) hint = 'The server hit an error processing your request.';
        else if (response.status === 504) hint = 'The request timed out.';
        else if (response.status === 503) hint = 'The service is temporarily unavailable.';
        return { ok: false, status: response.status, error: hint, raw };
    }
    try {
        const data = await response.json();
        return { ok: response.ok, status: response.status, data };
    } catch (e) {
        return { ok: false, status: response.status, error: 'Server sent malformed JSON.' };
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el, { container: 'body' });
    });
});

// ═══════════════════════════════════════════════════════════
// GENERATE ALL
// ═══════════════════════════════════════════════════════════
async function generateAll() {
    const btn = document.getElementById('generateAllBtn');
    if (!btn) return;

    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generating...';

    const result = await safeFetchJson('{{ route("admin.predictions.generate-all") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    });

    const data = result.data || {};

    if (result.ok && data.success === true) {
        if (data.generated === 0) {
            alert('No pending farm records to predict.');
            btn.innerHTML = original;
            btn.disabled = false;
            return;
        }
        let msg = data.message || `Generated ${data.generated} prediction(s).`;
        if (data.failed > 0 && Array.isArray(data.errors) && data.errors.length) {
            msg += '\n\nFailed:\n' + data.errors.join('\n');
        }
        alert(msg);
        location.reload();
    } else {
        const message = data.error || result.error || 'Rice yield prediction failed. Please try again.';
        alert('Prediction failed:\n\n' + message + '\n\nPlease try again.');
        btn.innerHTML = original;
        btn.disabled = false;
    }
}

// ═══════════════════════════════════════════════════════════
// REGENERATE ALL
// ═══════════════════════════════════════════════════════════
let regenerateInProgress = false;
let regenerateModal = null;

function openRegenerateModal() {
    document.getElementById('regenerateConfirmBody').style.display = 'block';
    document.getElementById('regenerateLoadingBody').style.display = 'none';
    document.getElementById('regenerateSuccessBody').style.display = 'none';
    document.getElementById('regenerateErrorBody').style.display   = 'none';

    const footer = document.getElementById('regenerateFooter');
    footer.style.display = 'flex';
    footer.innerHTML = `
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="regenerateCancelBtn">
            <i class="bi bi-x-circle"></i> Cancel
        </button>
        <button type="button" class="btn btn-warning" id="confirmRegenerateBtn" onclick="confirmRegenerate()">
            <i class="bi bi-arrow-repeat"></i> Yes, Regenerate All
        </button>
    `;

    document.getElementById('regenerateCloseBtn').disabled = false;
    regenerateInProgress = false;

    regenerateModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('regenerateConfirmModal'));
    regenerateModal.show();
}

async function confirmRegenerate() {
    if (regenerateInProgress) return;
    regenerateInProgress = true;

    document.getElementById('regenerateConfirmBody').style.display = 'none';
    document.getElementById('regenerateLoadingBody').style.display = 'block';
    document.getElementById('regenerateSuccessBody').style.display = 'none';
    document.getElementById('regenerateErrorBody').style.display   = 'none';
    document.getElementById('regenerateFooter').style.display      = 'none';
    document.getElementById('regenerateCloseBtn').disabled         = true;

    const result = await safeFetchJson('{{ route("admin.predictions.regenerate-all") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    });

    document.getElementById('regenerateLoadingBody').style.display = 'none';
    document.getElementById('regenerateCloseBtn').disabled = false;

    const data = result.data || {};
    const wasSuccess = result.ok && data.success === true;

    if (wasSuccess) {
        document.getElementById('regenerateSuccessBody').style.display = 'block';
        document.getElementById('regenerateSuccessMessage').textContent = data.message || 'All predictions regenerated.';

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

        const footer = document.getElementById('regenerateFooter');
        footer.style.display = 'flex';
        footer.innerHTML = `<button type="button" class="btn btn-success" onclick="location.reload()"><i class="bi bi-check-circle"></i> Done</button>`;
    } else {
        let message = data.error || result.error || 'Rice yield prediction failed. Please try again.';
        if (data.errors && typeof data.errors === 'object') {
            const flat = Object.values(data.errors).flat().join(' ');
            if (flat) message = flat;
        }
        showRegenerateError(message);
    }

    regenerateInProgress = false;
}

function showRegenerateError(message) {
    document.getElementById('regenerateErrorBody').style.display = 'block';
    document.getElementById('regenerateErrorMessage').textContent = message;

    const footer = document.getElementById('regenerateFooter');
    footer.style.display = 'flex';
    footer.innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeRegenerateModal()">
            <i class="bi bi-x-circle"></i> Close
        </button>
        <button type="button" class="btn btn-warning" onclick="openRegenerateModal()">
            <i class="bi bi-arrow-repeat"></i> Try Again
        </button>
    `;
}

function closeRegenerateModal() {
    if (regenerateModal) regenerateModal.hide();
}

// ═══════════════════════════════════════════════════════════
// SEASON DETAIL MODAL
// ═══════════════════════════════════════════════════════════
function openSeasonModal(farmRecordId) {
    document.getElementById('seasonDetailLoading').style.display = 'block';
    document.getElementById('seasonDetailContent').style.display = 'none';
    document.getElementById('seasonDetailContent').innerHTML = '';

    fetch('/admin/farm-records/' + farmRecordId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.text())
    .then(html => {
        document.getElementById('seasonDetailLoading').style.display = 'none';
        document.getElementById('seasonDetailContent').style.display = 'block';
        document.getElementById('seasonDetailContent').innerHTML = html;
    })
    .catch(() => {
        document.getElementById('seasonDetailLoading').style.display = 'none';
        document.getElementById('seasonDetailContent').style.display = 'block';
        document.getElementById('seasonDetailContent').innerHTML =
            '<div class="alert alert-danger">Failed to load season details.</div>';
    });

    new bootstrap.Modal(document.getElementById('seasonDetailModal')).show();
}

// ═══════════════════════════════════════════════════════════
// VARIETY DETAILS
// ═══════════════════════════════════════════════════════════
function viewVarietyDetails(id) {
    document.getElementById('varietyDetailLoading').style.display = 'block';
    document.getElementById('varietyDetailContent').style.display = 'none';

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
        document.getElementById('varietyDetailContent').innerHTML =
            '<div class="alert alert-danger">Failed to load.</div>';
    });

    new bootstrap.Modal(document.getElementById('varietyDetailModal')).show();
}
</script>
@endpush

@push('styles')
<style>
    .badge-status.high   { background: var(--brand-green-light); color: var(--brand-green-dark); border-color: #c7e6d2; }
    .badge-status.medium { background: var(--brand-gold-light);  color: #92400e;                 border-color: #fde68a; }
    .badge-status.low    { background: var(--brand-danger-light); color: #991b1b;                border-color: #fecaca; }
    .badge-status .dot   { width: 6px; height: 6px; border-radius: 50%; display: inline-block; margin-right: 4px; }
    .badge-status.high   .dot { background: var(--brand-green); }
    .badge-status.medium .dot { background: var(--brand-gold); }
    .badge-status.low    .dot { background: var(--brand-danger); }
</style>
@endpush