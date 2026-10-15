@extends('layouts.app')

@section('title', 'Rice Yield Predictions')

@section('content')

{{-- ═══════════════ HEADER ═══════════════ --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 style="font-weight: 800; color: var(--slate-900); margin: 0;"></h4>
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
            <button type="button" class="btn btn-success" id="generateAllBtn" onclick="openGenerateModal()">
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
@php
    $selectArrow = "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\")";

    $selectLg = "border-radius: 12px; border: 1px solid var(--gray-200); background-color: var(--gray-50); font-size: 13px; padding: 0.45rem 2rem 0.45rem 1rem; background-image: {$selectArrow}; background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.7rem; cursor: pointer; appearance: none; width: auto;";
    $selectSm = "border-radius: 10px; border: 1px solid var(--gray-200); background-color: var(--gray-50); font-size: 12px; padding: 0.4rem 2rem 0.4rem 0.85rem; background-image: {$selectArrow}; background-repeat: no-repeat; background-position: right 0.65rem center; background-size: 0.65rem; cursor: pointer; appearance: none; width: auto;";

    $pill = fn (bool $active) => 'padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; white-space: nowrap; '
        . ($active
            ? 'background: white; color: var(--gray-900); box-shadow: 0 1px 2px rgba(0,0,0,0.05);'
            : 'background: transparent; color: var(--gray-600);');

    $labelStyle = 'font-size: 10px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase;';

    $seasonOptions = ['' => 'All', 'Dry' => 'Dry', 'Wet' => 'Wet', 'Off-Season' => 'Off-Season'];
    $statusOptions = ['' => 'All', 'Vegetative' => 'Growing', 'Harvested' => 'Harvested'];
    $waterOptions  = ['' => 'All', 'Rainfed' => 'Rainfed', 'Irrigated' => 'Irrigated', 'Both' => 'Both'];

    $anyFilterActive =
        trim((string) request('q')) !== '' ||
        (request('year') && request('year') !== 'all') ||
        request('barangay') ||
        request('season') ||
        request('record_status') ||
        request('water_source') ||
        request('variety') ||
        (request('confidence') && request('confidence') !== 'all');
@endphp

<form method="GET" action="{{ route('admin.predictions.index') }}" id="predictionFilterForm">
<div class="row g-2 mb-4">
    <div class="col-12">
        <div class="card-custom" style="padding: 16px 20px;">

            {{-- ROW 1 --}}
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <div style="flex: 1; min-width: 240px;">
                    <div class="input-group" style="border-radius: 12px; overflow: hidden; border: 1px solid var(--gray-200);">
                        <span class="input-group-text" style="background: var(--gray-50); border: none; color: var(--gray-400);">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="q" id="searchPrediction" class="form-control"
                               value="{{ request('q') }}"
                               onchange="this.form.submit()"
                               placeholder="Search by farmer, RSBSA, farm, variety, season..."
                               style="border: none; background: var(--gray-50); font-size: 13px;">
                    </div>
                </div>

                <div style="min-width: 140px;">
                    <select name="year" id="filterYear" class="form-select"
                            onchange="this.form.submit()"
                            title="Filter by year"
                            style="{{ $selectLg }}">
                        <option value="all" {{ request('year', 'all') === 'all' ? 'selected' : '' }}>All Years</option>
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}" {{ (string) request('year') === (string) $y ? 'selected' : '' }}>
                                {{ $y }}{{ (int) $y === (int) now()->year ? ' (Current)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="min-width: 160px;">
                    <select name="barangay" id="filterBarangay" class="form-select"
                            onchange="this.form.submit()"
                            title="Filter by barangay"
                            style="{{ $selectLg }} min-width: 150px;">
                        <option value="">All Barangays</option>
                        @foreach($barangays as $b)
                            <option value="{{ $b }}" {{ request('barangay') === $b ? 'selected' : '' }}>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>

                @if($anyFilterActive)
                    <a href="{{ route('admin.predictions.index', array_filter(['per_page' => request('per_page')])) }}"
                       class="btn btn-sm btn-outline-secondary"
                       style="border-radius: 10px; font-size: 12px;"
                       title="Clear all filters">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>
                @endif
            </div>

            {{-- ROW 2 --}}
            <div class="d-flex flex-wrap align-items-center gap-3 pt-3" style="border-top: 1px dashed var(--gray-200);">

                <div class="d-flex" style="background: var(--gray-100); border-radius: 12px; padding: 4px; gap: 2px;">
                    @foreach($seasonOptions as $value => $label)
                        @php $isActive = (string) request('season') === (string) $value; @endphp
                        <button type="submit" name="season" value="{{ $value }}"
                                class="filter-btn season-btn {{ $isActive ? 'active' : '' }}"
                                style="{{ $pill($isActive) }}">{{ $label }}</button>
                    @endforeach
                </div>

                <div style="width: 1px; height: 28px; background: var(--gray-200);"></div>

                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="{{ $labelStyle }}">Status</span>
                    <div class="d-flex" style="background: var(--gray-100); border-radius: 12px; padding: 4px; gap: 2px;">
                        @foreach($statusOptions as $value => $label)
                            @php $isActive = (string) request('record_status') === (string) $value; @endphp
                            <button type="submit" name="record_status" value="{{ $value }}"
                                    class="filter-btn status-btn {{ $isActive ? 'active' : '' }}"
                                    style="{{ $pill($isActive) }}">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>

                <div style="width: 1px; height: 28px; background: var(--gray-200);"></div>

                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="{{ $labelStyle }}">Water</span>
                    <div class="d-flex" style="background: var(--gray-100); border-radius: 12px; padding: 4px; gap: 2px;">
                        @foreach($waterOptions as $value => $label)
                            @php $isActive = (string) request('water_source') === (string) $value; @endphp
                            <button type="submit" name="water_source" value="{{ $value }}"
                                    class="filter-btn water-btn {{ $isActive ? 'active' : '' }}"
                                    style="{{ $pill($isActive) }}">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>

                <div style="width: 1px; height: 28px; background: var(--gray-200);"></div>

                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="{{ $labelStyle }}">Confidence</span>
                    <select name="confidence" id="filterConfidence" class="form-select"
                            onchange="this.form.submit()"
                            title="Filter by confidence level"
                            style="{{ $selectSm }} min-width: 130px;">
                        <option value="all"    {{ request('confidence', 'all') === 'all' ? 'selected' : '' }}>Any</option>
                        <option value="high"   {{ request('confidence') === 'high'   ? 'selected' : '' }}>High (≥90%)</option>
                        <option value="medium" {{ request('confidence') === 'medium' ? 'selected' : '' }}>Medium (70–90%)</option>
                        <option value="low"    {{ request('confidence') === 'low'    ? 'selected' : '' }}>Low (&lt;70%)</option>
                    </select>
                </div>

                <div style="width: 1px; height: 28px; background: var(--gray-200);"></div>

                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="{{ $labelStyle }}">Variety</span>
                    <select name="variety" id="filterVariety" class="form-select"
                            onchange="this.form.submit()"
                            title="Filter by variety"
                            style="{{ $selectSm }} min-width: 160px;">
                        <option value="">All Varieties</option>
                        @foreach($varieties as $v)
                            <option value="{{ $v->id }}" {{ (string) request('variety') === (string) $v->id ? 'selected' : '' }}>
                                {{ $v->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="width: 1px; height: 28px; background: var(--gray-200);"></div>

                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="{{ $labelStyle }}">Per Page</span>
                    <select name="per_page" id="perPageSelect" class="form-select"
                            onchange="this.form.submit()"
                            title="Records per page"
                            style="{{ $selectSm }} min-width: 100px;">
                        @foreach([10, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ (int) request('per_page', 25) === $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>

            </div>
        </div>
    </div>
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
    <div class="card-title">
        <i class="bi bi-table"></i> Predictions
        @if(request('year') && request('year') !== 'all')
            <span class="badge bg-light text-muted ms-1" style="font-weight: 400; font-size: 11px;">{{ request('year') }}</span>
        @endif
        <span class="badge bg-light text-muted ms-1" style="font-weight: 400; font-size: 10px;">
            {{ $predictions->total() }} total
        </span>
    </div>

    @if($predictions->isEmpty())
        @if($anyFilterActive)
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

        @if($predictions->hasPages())
            @php
                $curPage   = $predictions->currentPage();
                $lastPage  = $predictions->lastPage();
                $pageItems = [];
                if ($lastPage <= 7) {
                    $pageItems = range(1, $lastPage);
                } else {
                    $pageItems[] = 1;
                    if ($curPage > 3) $pageItems[] = '...';
                    for ($i = max(2, $curPage - 1); $i <= min($lastPage - 1, $curPage + 1); $i++) $pageItems[] = $i;
                    if ($curPage < $lastPage - 2) $pageItems[] = '...';
                    $pageItems[] = $lastPage;
                }
                $pageUrl = fn (int $n) => $predictions->withQueryString()->url($n);
            @endphp
            <div id="paginationBar">
                <div class="d-flex align-items-center gap-2" style="font-size: 12px; color: var(--slate-500);">
                    <span>— showing {{ $predictions->firstItem() }} to {{ $predictions->lastItem() }} of {{ $predictions->total() }} entries</span>
                </div>
                <nav aria-label="Prediction pagination">
                    <ul id="paginationList" class="pagination mb-0">
                        <li>
                            @if($predictions->onFirstPage())
                                <span class="pg-btn pg-disabled"><i class="bi bi-chevron-left"></i></span>
                            @else
                                <a class="pg-btn" href="{{ $pageUrl($curPage - 1) }}" aria-label="Previous"><i class="bi bi-chevron-left"></i></a>
                            @endif
                        </li>

                        @foreach($pageItems as $item)
                            <li>
                                @if($item === '...')
                                    <span class="pg-dots">…</span>
                                @elseif($item === $curPage)
                                    <span class="pg-btn pg-active" aria-current="page">{{ $item }}</span>
                                @else
                                    <a class="pg-btn" href="{{ $pageUrl($item) }}">{{ $item }}</a>
                                @endif
                            </li>
                        @endforeach

                        <li>
                            @if($predictions->hasMorePages())
                                <a class="pg-btn" href="{{ $pageUrl($curPage + 1) }}" aria-label="Next"><i class="bi bi-chevron-right"></i></a>
                            @else
                                <span class="pg-btn pg-disabled"><i class="bi bi-chevron-right"></i></span>
                            @endif
                        </li>
                    </ul>
                </nav>
            </div>
        @endif
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

{{-- ═══════════════ GENERATE ALL MODAL ═══════════════ --}}
<div class="modal fade" id="generateModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #16a34a, #15803d); color: white;">
                <h5 class="modal-title"><i class="bi bi-lightning-charge-fill"></i> Generate All Predictions</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="generateCloseBtn"></button>
            </div>

            <div class="modal-body">
                {{-- CONFIRM --}}
                <div id="generateConfirmBody">
                    <div class="text-center mb-3">
                        <div style="width: 64px; height: 64px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: inline-flex; align-items: center; justify-content: center; font-size: 28px;">
                            <i class="bi bi-cpu"></i>
                        </div>
                    </div>
                    <h6 class="fw-bold text-center mb-2">
                        Generate predictions for {{ $pendingFarmRecords }} record{{ $pendingFarmRecords !== 1 ? 's' : '' }}?
                    </h5>
                    <p class="text-muted small mb-0 text-center">
                        Each vegetative farm record without a prediction will be processed.
                        Records with an <strong>Off-Season</strong> season will be predicted using
                        <strong>Dry Season</strong> as a proxy (flagged in the report).
                    </p>
                </div>

                {{-- LOADING --}}
                <div id="generateLoadingBody" class="text-center py-4" style="display: none;">
                    <div style="width: 72px; height: 72px; margin: 0 auto 16px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 32px; animation: pulse 1.6s infinite;">
                        <i class="bi bi-cpu-fill"></i>
                    </div>
                    <h6 class="fw-bold mb-1" style="color: var(--slate-800);">Generating predictions…</h6>
                    <p class="text-muted small mb-3">
                        Please don't close this window. This may take a few moments.
                    </p>

                    <div class="progress" style="height: 10px; border-radius: 10px; background: var(--slate-100); max-width: 420px; margin: 0 auto;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar"
                             style="width: 100%;"></div>
                    </div>

                    <div class="d-flex justify-content-center gap-3 mt-3" style="font-size: 12px;">
                        <span class="text-muted"><i class="bi bi-hourglass-split"></i> Elapsed: <strong id="generateElapsed">0s</strong></span>
                        <span class="text-muted"><i class="bi bi-list-task"></i> Processing {{ $pendingFarmRecords }} record{{ $pendingFarmRecords !== 1 ? 's' : '' }}</span>
                    </div>
                </div>

                {{-- SUCCESS --}}
                <div id="generateSuccessBody" class="py-2" style="display: none;">
                    <div class="text-center mb-3">
                        <div style="width: 72px; height: 72px; margin: 0 auto; border-radius: 50%; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 38px;">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <h6 class="mt-3 mb-1" style="font-weight: 700; color: var(--slate-800);">Generation Complete</h6>
                        <p class="mb-0" style="font-size: 14px; color: var(--slate-600);" id="generateSuccessMessage"></p>
                    </div>

                    <div class="row g-2 mt-3" id="generateSuccessStats">
                        <div class="col-4">
                            <div class="p-2 text-center" style="background: #dcfce7; border-radius: 10px;">
                                <div style="font-size: 22px; font-weight: 800; color: #15803d;" id="statGenerated">0</div>
                                <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; color: #16a34a;">Generated</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 text-center" style="background: #fef3c7; border-radius: 10px;">
                                <div style="font-size: 22px; font-weight: 800; color: #92400e;" id="statProxied">0</div>
                                <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; color: #a16207;">Proxy</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 text-center" style="background: #fee2e2; border-radius: 10px;">
                                <div style="font-size: 22px; font-weight: 800; color: #991b1b;" id="statFailed">0</div>
                                <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; color: #b91c1c;">Failed</div>
                            </div>
                        </div>
                    </div>

                    <div id="generateProxiedBlock" class="mt-3" style="display: none;">
                        <div class="alert alert-warning small mb-0" style="border-radius: 10px;">
                            <strong><i class="bi bi-info-circle"></i> Proxy season used:</strong>
                            <div class="text-muted mb-2" style="font-size: 11px;">
                                These records use Off-Season and were predicted with Dry Season as a proxy.
                            </div>
                            <ul class="mb-0 mt-1 ps-3" id="generateProxiedList"></ul>
                        </div>
                    </div>

                    <div id="generateErrorsBlock" class="mt-3" style="display: none;">
                        <div class="alert alert-danger small mb-0" style="border-radius: 10px;">
                            <strong><i class="bi bi-x-octagon"></i> Records that failed:</strong>
                            <ul class="mb-0 mt-1 ps-3" id="generateErrorsList"></ul>
                        </div>
                    </div>
                </div>

                {{-- ERROR --}}
                <div id="generateErrorBody" class="text-center py-3" style="display: none;">
                    <div style="width: 72px; height: 72px; margin: 0 auto; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 38px;">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                    <h6 class="mt-3 mb-1" style="font-weight: 700;">Generation Failed</h6>
                    <p class="mb-0" style="font-size: 13px; color: var(--slate-600);" id="generateErrorMessage"></p>
                </div>
            </div>

            <div class="modal-footer" id="generateFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="generateCancelBtn">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
                <button type="button" class="btn btn-success" id="confirmGenerateBtn" onclick="confirmGenerate()">
                    <i class="bi bi-lightning-charge-fill"></i> Yes, Generate All
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════ REGENERATE ALL MODAL ═══════════════ --}}
<div class="modal fade" id="regenerateConfirmModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header modal-header-warning">
                <h5 class="modal-title"><i class="bi bi-arrow-repeat"></i> Regenerate All Predictions</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="regenerateCloseBtn"></button>
            </div>

            <div class="modal-body">
                {{-- CONFIRM --}}
                <div id="regenerateConfirmBody">
                    <div class="text-center mb-3">
                        <div style="width: 64px; height: 64px; border-radius: 50%; background: #fef3c7; color: #d97706; display: inline-flex; align-items: center; justify-content: center; font-size: 28px;">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>
                    </div>
                    <h6 class="fw-bold text-center mb-2">
                        Regenerate all {{ $totalPredictions }} prediction{{ $totalPredictions !== 1 ? 's' : '' }}?
                    </h6>
                    <p class="text-muted small mb-0 text-center">
                        Refreshes every prediction using the latest weather data.
                        A new entry is added to each farm record's history.
                    </p>
                </div>

                {{-- LOADING --}}
                <div id="regenerateLoadingBody" class="text-center py-4" style="display: none;">
                    <div style="width: 72px; height: 72px; margin: 0 auto 16px; border-radius: 50%; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 32px; animation: pulse 1.6s infinite;">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                    <h6 class="fw-bold mb-1" style="color: var(--slate-800);">Regenerating predictions…</h6>
                    <p class="text-muted small mb-3">
                        Please don't close this window. This may take a few moments.
                    </p>

                    <div class="progress" style="height: 10px; border-radius: 10px; background: var(--slate-100); max-width: 420px; margin: 0 auto;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar"
                             style="width: 100%;"></div>
                    </div>

                    <div class="d-flex justify-content-center gap-3 mt-3" style="font-size: 12px;">
                        <span class="text-muted"><i class="bi bi-hourglass-split"></i> Elapsed: <strong id="regenerateElapsed">0s</strong></span>
                        <span class="text-muted"><i class="bi bi-list-task"></i> Processing {{ $totalPredictions }} prediction{{ $totalPredictions !== 1 ? 's' : '' }}</span>
                    </div>
                </div>

                {{-- SUCCESS --}}
                <div id="regenerateSuccessBody" class="py-2" style="display: none;">
                    <div class="text-center mb-3">
                        <div style="width: 72px; height: 72px; margin: 0 auto; border-radius: 50%; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 38px;">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <h6 class="mt-3 mb-1" style="font-weight: 700; color: var(--slate-800);">Regeneration Complete</h6>
                        <p class="mb-0" style="font-size: 14px; color: var(--slate-600);" id="regenerateSuccessMessage"></p>
                    </div>

                    <div class="row g-2 mt-3" id="regenerateSuccessStats">
                        <div class="col-4">
                            <div class="p-2 text-center" style="background: #dcfce7; border-radius: 10px;">
                                <div style="font-size: 22px; font-weight: 800; color: #15803d;" id="regenStatRegenerated">0</div>
                                <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; color: #16a34a;">Regenerated</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 text-center" style="background: #fef3c7; border-radius: 10px;">
                                <div style="font-size: 22px; font-weight: 800; color: #92400e;" id="regenStatProxied">0</div>
                                <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; color: #a16207;">Proxy</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 text-center" style="background: #fee2e2; border-radius: 10px;">
                                <div style="font-size: 22px; font-weight: 800; color: #991b1b;" id="regenStatFailed">0</div>
                                <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; color: #b91c1c;">Failed</div>
                            </div>
                        </div>
                    </div>

                    <div id="regenerateProxiedBlock" class="mt-3" style="display: none;">
                        <div class="alert alert-warning small mb-0" style="border-radius: 10px;">
                            <strong><i class="bi bi-info-circle"></i> Proxy season used:</strong>
                            <div class="text-muted mb-2" style="font-size: 11px;">
                                These records use Off-Season and were regenerated with Dry Season as a proxy.
                            </div>
                            <ul class="mb-0 mt-1 ps-3" id="regenerateProxiedList"></ul>
                        </div>
                    </div>

                    <div id="regenerateErrorsBlock" class="mt-3" style="display: none;">
                        <div class="alert alert-danger small mb-0" style="border-radius: 10px;">
                            <strong><i class="bi bi-x-octagon"></i> Records that failed:</strong>
                            <ul class="mb-0 mt-1 ps-3" id="regenerateErrorsList"></ul>
                        </div>
                    </div>
                </div>

                {{-- ERROR --}}
                <div id="regenerateErrorBody" class="text-center py-3" style="display: none;">
                    <div style="width: 72px; height: 72px; margin: 0 auto; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 38px;">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
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
// ═══════════════════════════════════════════════════════════
// Shared helpers
// ═══════════════════════════════════════════════════════════
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
let generateInProgress = false;
let generateModal = null;
let generateTicker = null;

function openGenerateModal() {
    document.getElementById('generateConfirmBody').style.display  = 'block';
    document.getElementById('generateLoadingBody').style.display  = 'none';
    document.getElementById('generateSuccessBody').style.display  = 'none';
    document.getElementById('generateErrorBody').style.display    = 'none';

    const footer = document.getElementById('generateFooter');
    footer.style.display = 'flex';
    footer.innerHTML = `
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="generateCancelBtn">
            <i class="bi bi-x-circle"></i> Cancel
        </button>
        <button type="button" class="btn btn-success" id="confirmGenerateBtn" onclick="confirmGenerate()">
            <i class="bi bi-lightning-charge-fill"></i> Yes, Generate All
        </button>
    `;

    document.getElementById('generateCloseBtn').disabled = false;
    generateInProgress = false;

    generateModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('generateModal'));
    generateModal.show();
}

async function confirmGenerate() {
    if (generateInProgress) return;
    generateInProgress = true;

    // Show loading state
    document.getElementById('generateConfirmBody').style.display = 'none';
    document.getElementById('generateLoadingBody').style.display = 'block';
    document.getElementById('generateSuccessBody').style.display = 'none';
    document.getElementById('generateErrorBody').style.display   = 'none';
    document.getElementById('generateFooter').style.display      = 'none';
    document.getElementById('generateCloseBtn').disabled         = true;

    // Elapsed timer
    const startedAt = Date.now();
    const elapsedEl = document.getElementById('generateElapsed');
    elapsedEl.textContent = '0s';
    generateTicker = setInterval(() => {
        const sec = Math.floor((Date.now() - startedAt) / 1000);
        elapsedEl.textContent = sec + 's';
    }, 1000);

    // Fire request
    const result = await safeFetchJson('{{ route("admin.predictions.generate-all") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    });

    clearInterval(generateTicker);
    document.getElementById('generateLoadingBody').style.display = 'none';
    document.getElementById('generateCloseBtn').disabled = false;

    const data = result.data || {};
    const wasSuccess = result.ok && data.success === true;

    if (wasSuccess) {
        document.getElementById('generateSuccessBody').style.display = 'block';
        document.getElementById('generateSuccessMessage').textContent = data.message || 'Predictions generated.';

        document.getElementById('statGenerated').textContent = data.generated ?? 0;
        document.getElementById('statProxied').textContent   = data.proxied_count ?? 0;
        document.getElementById('statFailed').textContent    = data.failed ?? 0;

        // Proxied list
        const proxiedBlock = document.getElementById('generateProxiedBlock');
        const proxiedList  = document.getElementById('generateProxiedList');
        proxiedList.innerHTML = '';
        if (Array.isArray(data.proxied) && data.proxied.length > 0) {
            data.proxied.forEach(p => {
                const li = document.createElement('li');
                li.innerHTML = `<strong>${escapeHtml(p.farm)}</strong> — ${escapeHtml(p.farmer)} <span class="badge bg-secondary ms-1">${escapeHtml(p.original)} → ${escapeHtml(p.used || 'Dry')}</span>`;
                proxiedList.appendChild(li);
            });
            proxiedBlock.style.display = 'block';
        } else {
            proxiedBlock.style.display = 'none';
        }

        // Failed list
        const errorsBlock = document.getElementById('generateErrorsBlock');
        const errorsList  = document.getElementById('generateErrorsList');
        errorsList.innerHTML = '';
        if (Array.isArray(data.errors) && data.errors.length > 0) {
            data.errors.forEach(e => {
                const li = document.createElement('li');
                li.innerHTML = `<strong>${escapeHtml(e.farm)}</strong> — ${escapeHtml(e.farmer)}: ${escapeHtml(e.reason)}`;
                errorsList.appendChild(li);
            });
            errorsBlock.style.display = 'block';
        } else {
            errorsBlock.style.display = 'none';
        }

        const footer = document.getElementById('generateFooter');
        footer.style.display = 'flex';
        footer.innerHTML = `<button type="button" class="btn btn-success" onclick="location.reload()"><i class="bi bi-check-circle"></i> Done</button>`;
    } else {
        let message = data.error || result.error || 'Generation failed. Please try again.';
        if (data.errors && typeof data.errors === 'object' && !Array.isArray(data.errors)) {
            const flat = Object.values(data.errors).flat().join(' ');
            if (flat) message = flat;
        }
        showGenerateError(message);
    }

    generateInProgress = false;
}

function showGenerateError(message) {
    document.getElementById('generateErrorBody').style.display = 'block';
    document.getElementById('generateErrorMessage').textContent = message;

    const footer = document.getElementById('generateFooter');
    footer.style.display = 'flex';
    footer.innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeGenerateModal()">
            <i class="bi bi-x-circle"></i> Close
        </button>
        <button type="button" class="btn btn-success" onclick="openGenerateModal()">
            <i class="bi bi-arrow-repeat"></i> Try Again
        </button>
    `;
}

function closeGenerateModal() {
    if (generateModal) generateModal.hide();
}

// ═══════════════════════════════════════════════════════════
// REGENERATE ALL
// ═══════════════════════════════════════════════════════════
let regenerateInProgress = false;
let regenerateModal = null;
let regenerateTicker = null;

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

    const startedAt = Date.now();
    const elapsedEl = document.getElementById('regenerateElapsed');
    elapsedEl.textContent = '0s';
    regenerateTicker = setInterval(() => {
        const sec = Math.floor((Date.now() - startedAt) / 1000);
        elapsedEl.textContent = sec + 's';
    }, 1000);

    const result = await safeFetchJson('{{ route("admin.predictions.regenerate-all") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    });

    clearInterval(regenerateTicker);
    document.getElementById('regenerateLoadingBody').style.display = 'none';
    document.getElementById('regenerateCloseBtn').disabled = false;

    const data = result.data || {};
    const wasSuccess = result.ok && data.success === true;

    if (wasSuccess) {
        document.getElementById('regenerateSuccessBody').style.display = 'block';
        document.getElementById('regenerateSuccessMessage').textContent = data.message || 'All predictions regenerated.';

        document.getElementById('regenStatRegenerated').textContent = data.regenerated ?? 0;
        document.getElementById('regenStatProxied').textContent     = data.proxied_count ?? 0;
        document.getElementById('regenStatFailed').textContent      = data.failed ?? 0;

        // Proxied list
        const proxiedBlock = document.getElementById('regenerateProxiedBlock');
        const proxiedList  = document.getElementById('regenerateProxiedList');
        proxiedList.innerHTML = '';
        if (Array.isArray(data.proxied) && data.proxied.length > 0) {
            data.proxied.forEach(p => {
                const li = document.createElement('li');
                li.innerHTML = `<strong>${escapeHtml(p.farm)}</strong> — ${escapeHtml(p.farmer)} <span class="badge bg-secondary ms-1">${escapeHtml(p.original)} → ${escapeHtml(p.used || 'Dry')}</span>`;
                proxiedList.appendChild(li);
            });
            proxiedBlock.style.display = 'block';
        } else {
            proxiedBlock.style.display = 'none';
        }

        // Failed list
        const errorsBlock = document.getElementById('regenerateErrorsBlock');
        const errorsList  = document.getElementById('regenerateErrorsList');
        errorsList.innerHTML = '';
        if (Array.isArray(data.errors) && data.errors.length > 0) {
            data.errors.forEach(e => {
                const li = document.createElement('li');
                li.innerHTML = `<strong>${escapeHtml(e.farm)}</strong> — ${escapeHtml(e.farmer)}: ${escapeHtml(e.reason)}`;
                errorsList.appendChild(li);
            });
            errorsBlock.style.display = 'block';
        } else {
            errorsBlock.style.display = 'none';
        }

        const footer = document.getElementById('regenerateFooter');
        footer.style.display = 'flex';
        footer.innerHTML = `<button type="button" class="btn btn-success" onclick="location.reload()"><i class="bi bi-check-circle"></i> Done</button>`;
    } else {
        let message = data.error || result.error || 'Regeneration failed. Please try again.';
        if (data.errors && typeof data.errors === 'object' && !Array.isArray(data.errors)) {
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

// ═══════════════════════════════════════════════════════════
// Small util
// ═══════════════════════════════════════════════════════════
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
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

    @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50%      { transform: scale(1.08); opacity: 0.85; }
    }

    #paginationBar {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 12px;
        padding-top: 16px; margin-top: 8px;
        border-top: 1px solid var(--slate-200);
    }
    #paginationList { display: flex; align-items: center; gap: 4px; list-style: none; padding: 0; margin: 0; }
    #paginationList .pg-btn {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 34px; height: 34px; padding: 0 10px;
        border-radius: 8px; border: 1px solid var(--slate-200);
        background: #fff; color: var(--slate-700);
        font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer;
    }
    #paginationList a.pg-btn:hover {
        background: var(--brand-green-light);
        border-color: var(--brand-green);
        color: var(--brand-green-dark);
        transform: translateY(-1px);
    }
    #paginationList .pg-btn.pg-active {
        background: var(--brand-green); border-color: var(--brand-green);
        color: #fff; font-weight: 700; cursor: default;
    }
    #paginationList .pg-btn.pg-disabled {
        background: var(--slate-50); color: var(--slate-300); cursor: not-allowed;
    }
    #paginationList .pg-dots {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 34px; height: 34px; color: var(--slate-400); font-weight: 700;
    }
    @media (max-width: 575.98px) {
        #paginationBar { justify-content: center; }
        #paginationList .pg-btn { min-width: 30px; height: 30px; padding: 0 8px; font-size: 12px; }
    }
</style>
@endpush