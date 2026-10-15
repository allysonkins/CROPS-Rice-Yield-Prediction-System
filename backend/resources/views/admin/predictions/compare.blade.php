@extends('layouts.app')

@section('title', 'Compare Predictions')

@section('content')

{{-- ═══════════ PRINT HEADER (only visible when printing) ═══════════ --}}
<div class="print-only print-header">
    <div class="print-header-brand">
        <div>
            <div class="print-header-agency">City Agriculture Office · Santiago City</div>
            <div class="print-header-title">CROPS — Yield Comparison Report</div>
        </div>
        <div class="print-header-meta">
            <div>Generated: <strong id="printGeneratedAt">—</strong></div>
            <div>Reference: <strong>CROPS-CMP-<span id="printRef"></span></strong></div>
        </div>
    </div>
    <div id="printFiltersLine" class="print-header-filters"></div>
    <hr class="print-rule">
</div>

{{-- ═══════════ HEADER ═══════════ --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
    <div>
        <h4 style="font-weight: 800; color: var(--slate-900); margin: 0;">
            Historical Comparison
        </h4>
        <p class="text-muted mb-0" style="font-size: 12.5px;">
            Compare predicted and actual yields across multiple years.
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-outline-secondary" onclick="exportExcel()" id="excelBtn" disabled>
            <i class="bi bi-file-earmark-excel-fill"></i> Export Excel
        </button>
        <button type="button" class="btn btn-outline-success" onclick="printComparison()" id="printBtn" disabled>
            <i class="bi bi-printer-fill"></i> Print Report
        </button>
        <a href="{{ route('admin.predictions.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Predictions
        </a>
    </div>
</div>

{{-- ═══════════ FILTERS ═══════════ --}}
<div class="card-custom mb-3 no-print" style="padding: 16px 20px;">
    <div class="row g-2">

        {{-- Years --}}
        <div class="col-lg-3 col-md-6">
            <label class="form-label fw-semibold" style="font-size: 12.5px;">
                Years to compare <span class="text-danger">*</span>
            </label>
            <select id="compareYears" class="form-select" multiple size="4" style="font-size: 13px;">
                @foreach($years as $y)
                    <option value="{{ $y }}" {{ $loop->index < 5 ? 'selected' : '' }}>
                        {{ $y }}{{ (int) $y === (int) now()->year ? ' (Current)' : '' }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted" style="font-size: 11px;">Ctrl / Cmd + click for multiple.</small>
        </div>

        {{-- Farmer + Farm --}}
        <div class="col-lg-3 col-md-6">
            <label class="form-label fw-semibold" style="font-size: 12.5px;">
                Farmer <span class="text-muted fw-normal">(optional)</span>
            </label>
            <select id="compareFarmer" class="form-select searchable-select">
                <option value="">Any Farmer</option>
                @foreach($farmers as $f)
                    <option value="{{ $f->id }}">
                        {{ $f->name }}{{ $f->rsbsa_number ? ' · ' . $f->rsbsa_number : '' }}
                    </option>
                @endforeach
            </select>

            <label class="form-label fw-semibold mt-2" style="font-size: 12.5px;">
                Farm <span class="text-muted fw-normal">(optional)</span>
            </label>
            <select id="compareFarm" class="form-select searchable-select">
                <option value="">Any Farm</option>
                @foreach($farms as $f)
                    <option value="{{ $f->id }}">
                        {{ $f->name }}{{ $f->barangay ? ' · ' . $f->barangay : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Variety + Barangay --}}
        <div class="col-lg-3 col-md-6">
            <label class="form-label fw-semibold" style="font-size: 12.5px;">
                Variety <span class="text-muted fw-normal">(optional)</span>
            </label>
            <select id="compareVariety" class="form-select searchable-select">
                <option value="">Any Variety</option>
                @foreach($varieties as $v)
                    <option value="{{ $v->id }}">{{ $v->name }}</option>
                @endforeach
            </select>

            <label class="form-label fw-semibold mt-2" style="font-size: 12.5px;">
                Barangay <span class="text-muted fw-normal">(optional)</span>
            </label>
            <select id="compareBarangay" class="form-select searchable-select">
                <option value="">Any Barangay</option>
                @foreach($barangays as $b)
                    <option value="{{ $b }}">{{ $b }}</option>
                @endforeach
            </select>
        </div>

        {{-- Season + Seeding --}}
        <div class="col-lg-3 col-md-6">
            <label class="form-label fw-semibold" style="font-size: 12.5px;">
                Season <span class="text-muted fw-normal">(optional)</span>
            </label>
            <select id="compareSeason" class="form-select searchable-select">
                <option value="">Any Season</option>
                <option value="Dry">Dry</option>
                <option value="Wet">Wet</option>
                <option value="Off-Season">Off-Season</option>
            </select>

            <label class="form-label fw-semibold mt-2" style="font-size: 12.5px;">
                Seeding Method <span class="text-muted fw-normal">(optional)</span>
            </label>
            <select id="compareSeedingMethod" class="form-select searchable-select">
                <option value="">Any Method</option>
                @foreach($seedingMethods as $sm)
                    <option value="{{ $sm }}">{{ $sm }}</option>
                @endforeach
            </select>
        </div>

        {{-- Water + Soil --}}
        <div class="col-lg-3 col-md-6">
            <label class="form-label fw-semibold" style="font-size: 12.5px;">
                Water Source <span class="text-muted fw-normal">(optional)</span>
            </label>
            <select id="compareWaterSource" class="form-select searchable-select">
                <option value="">Any Water</option>
                @foreach($waterSources as $ws)
                    <option value="{{ $ws }}">{{ $ws }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-lg-3 col-md-6">
            <label class="form-label fw-semibold" style="font-size: 12.5px;">
                Soil Type <span class="text-muted fw-normal">(optional)</span>
            </label>
            <select id="compareSoilType" class="form-select searchable-select">
                <option value="">Any Soil</option>
                @foreach($soilTypes as $st)
                    <option value="{{ $st }}">{{ $st }}</option>
                @endforeach
            </select>
        </div>

        {{-- Actions --}}
        <div class="col-lg-6 col-md-12 d-flex align-items-end gap-2">
            <button type="button" class="btn btn-success" onclick="runComparison()">
                <i class="bi bi-play-fill"></i> Compare
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="resetCompareFilters()">
                <i class="bi bi-arrow-counterclockwise"></i> Reset
            </button>
        </div>

    </div>
</div>

{{-- ═══════════ SUMMARY STRIP ═══════════ --}}
<div id="compareStats" class="row g-2 mb-3" style="display: none;">
    <div class="col-6 col-md-4">
        <div class="card-custom" style="padding: 14px 18px; border-left: 4px solid var(--brand-gold);">
            <div class="text-muted" style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Avg. Difference</div>
            <div id="statDiff" style="font-size: 24px; font-weight: 800; color: var(--brand-gold-dark); line-height: 1.1; margin-top: 4px;">—</div>
            <div class="text-muted" style="font-size: 11px;">Average t/ha between predicted &amp; actual</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card-custom" style="padding: 14px 18px; border-left: 4px solid #4f46e5;">
            <div class="text-muted" style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Records Compared</div>
            <div id="statMatched" style="font-size: 24px; font-weight: 800; color: #4f46e5; line-height: 1.1; margin-top: 4px;">—</div>
            <div class="text-muted" style="font-size: 11px;">Have both predicted &amp; actual yield</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card-custom" style="padding: 14px 18px; border-left: 4px solid var(--slate-400);">
            <div class="text-muted" style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Years Compared</div>
            <div id="statYears" style="font-size: 24px; font-weight: 800; color: var(--slate-700); line-height: 1.1; margin-top: 4px;">—</div>
            <div class="text-muted" style="font-size: 11px;">Years included in this report</div>
        </div>
    </div>
</div>

{{-- ═══════════ CHART ═══════════ --}}
<div class="card-custom mb-3 print-block">
    <div class="card-title d-flex justify-content-between align-items-center">
        <span>Predicted vs. Actual Yield by Year</span>
        <span class="text-muted no-print" id="chartStatus" style="font-size: 11px;"></span>
    </div>

    <div id="compareEmpty" class="text-center py-5 text-muted" style="display:none;">
        <i class="bi bi-bar-chart" style="font-size: 42px; color: var(--slate-300);"></i>
        <h5 class="mt-3 mb-2" style="color: var(--slate-700); font-weight: 700;">No data to compare</h5>
        <p class="mb-0" style="font-size: 13px; color: var(--slate-500);">
            Adjust the filters above and click <strong>Compare</strong>.
        </p>
    </div>

    <div id="compareChartWrap" style="height: 420px; position: relative;">
        <canvas id="compareChart"></canvas>
        <img id="compareChartPrint" alt="Predicted vs Actual Yield chart" style="display:none;">
    </div>

    <div id="missingYearsWarning" class="alert alert-warning mt-3" style="display:none; font-size: 12.5px; border-radius: 10px;">
        <strong><i class="bi bi-exclamation-triangle"></i> Missing data:</strong>
        <span id="missingYearsList"></span>
    </div>
</div>

{{-- ═══════════ PER-YEAR TABLE ═══════════ --}}
<div id="compareTableCard" class="card-custom print-block" style="display: none;">
    <div class="card-title"><i class="bi bi-table"></i> Year-by-Year Breakdown</div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Year</th>
                    <th>Records</th>
                    <th>Avg. Predicted</th>
                    <th>Avg. Actual</th>
                    <th>Difference</th>
                    <th>Matched</th>
                </tr>
            </thead>
            <tbody id="compareTableBody"></tbody>
        </table>
    </div>
    <p class="text-muted mb-0 mt-3 no-print" style="font-size: 11.5px;">
        <strong>Difference</strong> shows the year's average predicted vs. average actual yield —
        a positive value means the actual harvest came in higher than the prediction.
    </p>
</div>

{{-- ═══════════ ALERT MODAL ═══════════ --}}
<div class="modal fade no-print" id="compareAlertModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="cmpModalHeader">
                <h5 class="modal-title">
                    <i class="bi bi-info-circle-fill" id="cmpModalIcon"></i>
                    <span id="cmpModalTitleText">Notice</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-start gap-3">
                    <div style="flex-shrink:0;">
                        <div id="cmpModalIconBox" style="width:52px; height:52px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:24px;">
                            <i class="bi bi-info-circle-fill" id="cmpModalIconGlyph"></i>
                        </div>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div id="cmpModalMessage" style="font-size:14px; color: var(--slate-700); line-height:1.6; word-wrap: break-word;"></div>
                        <div id="cmpModalDetail" style="display:none; margin-top:12px; font-size:12px; color: var(--slate-500); background: var(--slate-50); padding:10px 12px; border-radius:10px; border:1px solid var(--slate-200); white-space: pre-wrap;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="cmpModalConfirm" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
{{-- Tom Select — searchable dropdowns without jQuery --}}
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ═══════════════════════════════════════════════════════════
// MODAL
// ═══════════════════════════════════════════════════════════
(function () {
    const TYPES = {
        info:    { header: 'linear-gradient(135deg, var(--brand-green-dark), var(--brand-green))', icon: 'bi-info-circle-fill',           iconBg: 'var(--brand-green-light)',   iconColor: 'var(--brand-green-dark)',  btn: 'btn-success' },
        success: { header: 'linear-gradient(135deg, #15803d, #22c55e)',                            icon: 'bi-check-circle-fill',          iconBg: '#dcfce7',                    iconColor: '#15803d',                  btn: 'btn-success' },
        warning: { header: 'linear-gradient(135deg, #b45309, #d97706)',                            icon: 'bi-exclamation-triangle-fill',  iconBg: 'var(--brand-gold-light)',    iconColor: '#92400e',                  btn: 'btn-warning' },
        error:   { header: 'linear-gradient(135deg, #991b1b, #dc2626)',                            icon: 'bi-x-circle-fill',              iconBg: 'var(--brand-danger-light)',  iconColor: '#991b1b',                  btn: 'btn-danger'  },
    };

    window.CompareModal = {
        show(message, opts = {}) {
            const type = opts.type || 'info';
            const cfg = TYPES[type] || TYPES.info;

            document.getElementById('cmpModalHeader').style.background = cfg.header;
            document.getElementById('cmpModalTitleText').textContent =
                opts.title || ({ info: 'Notice', success: 'Success', warning: 'Warning', error: 'Error' }[type] || 'Notice');
            document.getElementById('cmpModalIconGlyph').className = 'bi ' + cfg.icon;

            const box = document.getElementById('cmpModalIconBox');
            box.style.background = cfg.iconBg;
            box.style.color = cfg.iconColor;

            document.getElementById('cmpModalMessage').textContent = message || '';

            const detail = document.getElementById('cmpModalDetail');
            if (opts.detail) { detail.textContent = opts.detail; detail.style.display = 'block'; }
            else             { detail.style.display = 'none'; }

            const btn = document.getElementById('cmpModalConfirm');
            btn.className = 'btn ' + cfg.btn;
            btn.textContent = opts.confirmText || 'OK';

            bootstrap.Modal.getOrCreateInstance(document.getElementById('compareAlertModal')).show();
        }
    };
})();

// ═══════════════════════════════════════════════════════════
// SEARCHABLE DROPDOWNS (Tom Select)
// ═══════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.searchable-select').forEach(function (el) {
        new TomSelect(el, {
            allowEmptyOption: true,
            maxItems: 1,
            placeholder: el.querySelector('option[value=""]')?.textContent?.trim() || 'Select…',
            sortField: { field: 'text', direction: 'asc' },
            render: {
                option: function (data, escape) {
                    return '<div>' + escape(data.text) + '</div>';
                },
            },
        });
    });
});

// ═══════════════════════════════════════════════════════════
// COMPARISON
// ═══════════════════════════════════════════════════════════
let compareChart     = null;
let lastComparisonData = null;
let chartPngDataUrl  = null;
let chartPngReady    = false;

function resetCompareFilters() {
    document.getElementById('compareFarmer').tomselect?.clear();
    document.getElementById('compareFarm').tomselect?.clear();
    document.getElementById('compareVariety').tomselect?.clear();
    document.getElementById('compareBarangay').tomselect?.clear();
    document.getElementById('compareSeason').tomselect?.clear();
    document.getElementById('compareSeedingMethod').tomselect?.clear();
    document.getElementById('compareWaterSource').tomselect?.clear();
    document.getElementById('compareSoilType').tomselect?.clear();
    runComparison();
}

function getSelectValue(id) {
    const el = document.getElementById(id);
    // Tom Select keeps the original <select>.value in sync
    return el ? el.value || null : null;
}

function getSelectText(id) {
    const el = document.getElementById(id);
    if (!el || !el.value) return null;
    const opt = el.options[el.selectedIndex];
    return opt ? opt.textContent.trim() : null;
}

function buildFilterSummary() {
    const years = Array.from(document.getElementById('compareYears').selectedOptions).map(o => o.value);
    const parts = [];

    if (years.length)                  parts.push('Years: ' + years.join(', '));

    const farmerText = getSelectText('compareFarmer');
    if (farmerText)                    parts.push('Farmer: ' + farmerText);

    const farmText   = getSelectText('compareFarm');
    if (farmText)                      parts.push('Farm: ' + farmText);

    const varietyText = getSelectText('compareVariety');
    if (varietyText)                   parts.push('Variety: ' + varietyText);

    const barangayText = getSelectText('compareBarangay');
    if (barangayText)                  parts.push('Barangay: ' + barangayText);

    const seasonText = getSelectText('compareSeason');
    if (seasonText)                    parts.push('Season: ' + seasonText);

    const seedingText = getSelectText('compareSeedingMethod');
    if (seedingText)                   parts.push('Seeding: ' + seedingText);

    const waterText = getSelectText('compareWaterSource');
    if (waterText)                     parts.push('Water: ' + waterText);

    const soilText = getSelectText('compareSoilType');
    if (soilText)                      parts.push('Soil: ' + soilText);

    return parts.length ? parts.join('  ·  ') : 'No filters applied — showing all records';
}

async function runComparison(silent = false) {
    const years = Array.from(document.getElementById('compareYears').selectedOptions)
        .map(o => parseInt(o.value, 10));

    if (years.length < 2) {
        if (!silent) {
            CompareModal.show('Please select at least 2 years to compare.', {
                type: 'warning',
                title: 'Not enough years selected',
            });
        }
        clearCompareUI();
        return;
    }

    const payload = {
        years:          years,
        farmer_id:      getSelectValue('compareFarmer'),
        farm_id:        getSelectValue('compareFarm'),
        variety:        getSelectValue('compareVariety'),
        barangay:       getSelectValue('compareBarangay'),
        season:         getSelectValue('compareSeason'),
        seeding_method: getSelectValue('compareSeedingMethod'),
        water_source:   getSelectValue('compareWaterSource'),
        soil_type:      getSelectValue('compareSoilType'),
    };

    document.getElementById('chartStatus').textContent = 'Loading...';

    try {
        const res = await fetch('{{ route("admin.predictions.compare.data") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        const ct = res.headers.get('content-type') || '';
        if (!ct.includes('application/json')) {
            throw new Error('Server returned an unexpected response.');
        }

        const data = await res.json();
        lastComparisonData = data;
        renderChart(data);
    } catch (err) {
        document.getElementById('chartStatus').textContent = 'Error';
        if (!silent) {
            CompareModal.show('Comparison failed. Please try again.', {
                type: 'error',
                title: 'Comparison error',
                detail: err.message,
            });
        }
    }
}

function clearCompareUI() {
    document.getElementById('compareEmpty').style.display = 'block';
    document.getElementById('compareChartWrap').style.display = 'none';
    document.getElementById('chartStatus').textContent = '';
    document.getElementById('missingYearsWarning').style.display = 'none';
    document.getElementById('compareStats').style.display = 'none';
    document.getElementById('compareTableCard').style.display = 'none';
    document.getElementById('printBtn').disabled  = true;
    document.getElementById('excelBtn').disabled  = true;
    lastComparisonData = null;
    chartPngReady = false;
    chartPngDataUrl = null;
}

function renderChart(data) {
    const empty = document.getElementById('compareEmpty');
    const wrap  = document.getElementById('compareChartWrap');
    const stats = document.getElementById('compareStats');
    const tableCard = document.getElementById('compareTableCard');

    if (!data.labels || data.labels.length === 0) {
        clearCompareUI();
        return;
    }

    empty.style.display = 'none';
    wrap.style.display = 'block';
    document.getElementById('printBtn').disabled  = false;
    document.getElementById('excelBtn').disabled  = false;

    const overall = data.overall || {};
    const matched = overall.matched || 0;

    stats.style.display = 'flex';

    document.getElementById('statDiff').textContent =
        (matched > 0 && overall.mae !== null)
            ? overall.mae.toFixed(2) + ' t/ha'
            : '—';

    document.getElementById('statMatched').textContent = matched;
    document.getElementById('statYears').textContent   = overall.years ?? data.labels.length;

    const ctx = document.getElementById('compareChart').getContext('2d');
    if (compareChart) compareChart.destroy();

    chartPngReady = false;
    chartPngDataUrl = null;

    compareChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Predicted Yield (t/ha)',
                    data: data.predicted,
                    borderColor: '#165b33',
                    backgroundColor: 'rgba(22, 91, 51, 0.10)',
                    borderWidth: 3,
                    pointRadius: 6,
                    pointBackgroundColor: '#165b33',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    tension: 0.25,
                    spanGaps: false,
                },
                {
                    label: 'Actual Yield (t/ha)',
                    data: data.actual,
                    borderColor: '#d97706',
                    backgroundColor: 'rgba(217, 119, 6, 0.10)',
                    borderWidth: 3,
                    borderDash: [6, 4],
                    pointRadius: 6,
                    pointBackgroundColor: '#d97706',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    tension: 0.25,
                    spanGaps: false,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const i = context.dataIndex;
                            const meta = data.meta[i];
                            const lines = [`${context.dataset.label}: ${context.parsed.y ?? '—'} t/ha`];
                            if (meta) {
                                lines.push(`n = ${meta.records} record(s)`);
                                if (meta.matched > 0) lines.push(`Matched: ${meta.matched}`);
                            }
                            return lines;
                        },
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Yield (t/ha)' },
                    grid: { color: '#e2e8f0' },
                },
                x: {
                    title: { display: true, text: 'Year' },
                    grid: { display: false },
                },
            },
        },
    });

    document.getElementById('chartStatus').textContent = `${data.labels.length} year(s) compared`;

    prerenderChartToPng();

    const warn = document.getElementById('missingYearsWarning');
    const list = document.getElementById('missingYearsList');
    if (data.missingYears && data.missingYears.length > 0) {
        list.textContent = 'No records found for ' + data.missingYears.join(', ') + '. Those points appear as gaps.';
        warn.style.display = 'block';
    } else {
        warn.style.display = 'none';
    }

    renderPerYearTable(data.perYear);
    tableCard.style.display = 'block';
}

function prerenderChartToPng() {
    if (!compareChart) return;
    setTimeout(() => {
        try {
            const png = compareChart.toBase64Image('image/png', 1);
            const preloader = new Image();
            preloader.onload = () => {
                chartPngDataUrl = png;
                chartPngReady = true;
                const img = document.getElementById('compareChartPrint');
                if (img) img.src = png;
            };
            preloader.src = png;
        } catch (e) {
            console.error('[print] toBase64Image failed', e);
        }
    }, 300);
}

function renderPerYearTable(perYear) {
    const tbody = document.getElementById('compareTableBody');
    tbody.innerHTML = '';

    if (!perYear || perYear.length === 0) return;

    perYear.forEach(row => {
        const tr = document.createElement('tr');

        let deltaHtml = '<span class="text-muted">—</span>';
        if (row.delta !== null) {
            const color = row.delta > 0.05 ? '#15803d' : (row.delta < -0.05 ? '#b91c1c' : 'var(--slate-500)');
            const icon  = row.delta > 0.05 ? 'arrow-up' : (row.delta < -0.05 ? 'arrow-down' : 'dash');
            deltaHtml = `<span style="color:${color}; font-weight:700;">
                <i class="bi bi-${icon}"></i> ${row.delta > 0 ? '+' : ''}${row.delta.toFixed(2)}
            </span>`;
        }

        const recBadge = row.records === 0
            ? '<span class="text-muted">0</span>'
            : (row.records < 3
                ? `<span title="Very small sample — treat with caution" style="color:#92400e; font-weight:700;">${row.records} <i class="bi bi-exclamation-triangle-fill" style="font-size:10px;"></i></span>`
                : `<strong>${row.records}</strong>`);

        tr.innerHTML = `
            <td><strong>${row.year}</strong></td>
            <td>${recBadge}</td>
            <td>${row.avgPred !== null ? row.avgPred.toFixed(2) + ' t/ha' : '<span class="text-muted">—</span>'}</td>
            <td>${row.avgAct  !== null ? row.avgAct.toFixed(2)  + ' t/ha' : '<span class="text-muted">—</span>'}</td>
            <td>${deltaHtml}</td>
            <td>${row.matched > 0 ? row.matched : '<span class="text-muted">0</span>'}</td>
        `;
        tbody.appendChild(tr);
    });
}

// ═══════════════════════════════════════════════════════════
// PRINT
// ═══════════════════════════════════════════════════════════
async function snapshotChartForPrint() {
    if (!compareChart) return;
    const canvas = document.getElementById('compareChart');
    if (!canvas) return;

    const existing = document.getElementById('compareChartPrint');
    if (existing) existing.remove();

    try {
        compareChart.update('none');
        compareChart.render();
    } catch (e) {}

    const png = compareChart.toBase64Image('image/png', 1);

    const img = document.createElement('img');
    img.id     = 'compareChartPrint';
    img.src    = png;
    img.alt    = 'Predicted vs Actual Yield chart';
    img.style.width    = '100%';
    img.style.height   = 'auto';
    img.style.maxWidth = '100%';
    img.style.display  = 'block';

    canvas.parentNode.appendChild(img);
    canvas.style.display = 'none';

    if (typeof img.decode === 'function') {
        try { await img.decode(); } catch (e) {}
    } else {
        await new Promise(resolve => {
            img.onload  = resolve;
            img.onerror = resolve;
        });
    }

    await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));
}

function restoreChartAfterPrint() {
    const img = document.getElementById('compareChartPrint');
    if (img) img.remove();
    const canvas = document.getElementById('compareChart');
    if (canvas) canvas.style.display = '';
}

window.addEventListener('afterprint', restoreChartAfterPrint);
window.addEventListener('focus', function () {
    setTimeout(restoreChartAfterPrint, 500);
});

async function printComparison() {
    if (!lastComparisonData) return;

    const now = new Date();
    const stamp = now.toLocaleString('en-US', {
        year: 'numeric', month: 'long', day: '2-digit',
        hour: '2-digit', minute: '2-digit'
    });
    document.getElementById('printGeneratedAt').textContent = stamp;

    const ref = String(now.getTime()).slice(-6);
    document.getElementById('printRef').textContent = ref;

    document.getElementById('printFiltersLine').textContent = buildFilterSummary();

    await snapshotChartForPrint();
    setTimeout(() => window.print(), 150);
}

// ═══════════════════════════════════════════════════════════
// EXCEL EXPORT
// ═══════════════════════════════════════════════════════════
function exportExcel() {
    const years = Array.from(document.getElementById('compareYears').selectedOptions)
        .map(o => o.value);

    if (years.length < 2) {
        CompareModal.show('Please select at least 2 years before exporting.', {
            type: 'warning',
            title: 'Not enough years selected',
        });
        return;
    }

    const params = new URLSearchParams();
    years.forEach(y => params.append('years[]', y));

    const map = {
        farmer_id:      'compareFarmer',
        farm_id:        'compareFarm',
        variety:        'compareVariety',
        barangay:       'compareBarangay',
        season:         'compareSeason',
        seeding_method: 'compareSeedingMethod',
        water_source:   'compareWaterSource',
        soil_type:      'compareSoilType',
    };

    for (const [key, id] of Object.entries(map)) {
        const val = getSelectValue(id);
        if (val) params.append(key, val);
    }

    const base = '{{ route("admin.predictions.compare.export") }}';
    window.location.href = base + '?' + params.toString();
}

document.addEventListener('DOMContentLoaded', function () {
    runComparison(true);
});
</script>
@endpush

@push('styles')
<style>
    /* ══════════════════════════════════════════════════════════
       TOM SELECT — style override to match existing form-select
       ══════════════════════════════════════════════════════════ */
    .ts-wrapper.single .ts-control {
        border: 1px solid var(--gray-200);
        border-radius: 8px;
        padding: 0.375rem 0.75rem;
        font-size: 13px;
        min-height: 38px;
        background: #fff;
        box-shadow: none;
    }
    .ts-wrapper.single .ts-control.focus {
        border-color: var(--brand-green);
        box-shadow: 0 0 0 3px rgba(22, 91, 51, 0.12);
    }
    .ts-wrapper.single .ts-control > input {
        font-size: 13px;
    }
    .ts-wrapper .ts-dropdown {
        border-radius: 8px;
        border: 1px solid var(--slate-200);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.10);
        font-size: 13px;
        z-index: 1050;
    }
    .ts-wrapper .ts-dropdown .option {
        padding: 6px 12px;
    }
    .ts-wrapper .ts-dropdown .option.active {
        background: var(--brand-green-light);
        color: var(--brand-green-dark);
    }
    .ts-wrapper .ts-dropdown .option:hover {
        background: var(--slate-50);
    }
    .ts-wrapper .ts-control .item {
        color: var(--slate-800);
        font-weight: 500;
    }

    /* Print header / print rules */
    .print-only { display: none; }
    #compareChartPrint { display: none; }

    @media print {
        .no-print,
        .no-print * { display: none !important; }

        .modal-backdrop { display: none !important; }

        .print-only { display: block !important; }

        .card-custom {
            box-shadow: none !important;
            border: 1px solid #cbd5e1 !important;
            page-break-inside: avoid;
            margin-bottom: 12px !important;
        }

        #compareChartWrap {
            height: auto !important;
            min-height: 0 !important;
            max-height: none !important;
            overflow: visible !important;
            page-break-inside: avoid;
        }

        #compareChartWrap canvas {
            display: none !important;
        }
        #compareChartWrap img,
        #compareChartPrint {
            display: block !important;
            width: 100% !important;
            height: auto !important;
            max-width: 100% !important;
            max-height: 500px !important;
            object-fit: contain !important;
            margin: 0 auto !important;
        }

        .table { font-size: 11px !important; }
        .table thead th {
            background: #165b33 !important;
            color: #ffffff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .table tbody tr:nth-child(even) td {
            background: #f8fafc !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        #compareStats .card-custom {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body { font-size: 11px; }
        a { color: inherit !important; text-decoration: none !important; }
    }

    .print-header { margin-bottom: 16px; }
    .print-header-brand {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        flex-wrap: wrap;
    }
    .print-header-agency {
        font-size: 10px;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        color: #b45309;
        font-weight: 700;
    }
    .print-header-title {
        font-size: 18px;
        font-weight: 800;
        color: #165b33;
        letter-spacing: -0.3px;
        margin-top: 2px;
    }
    .print-header-meta {
        font-size: 10px;
        color: #475569;
        text-align: right;
        line-height: 1.5;
    }
    .print-header-filters {
        margin-top: 10px;
        font-size: 10px;
        color: #334155;
        padding: 8px 10px;
        background: #f1f5f9;
        border-left: 3px solid #165b33;
        border-radius: 4px;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .print-rule {
        border: none;
        border-top: 2px solid #165b33;
        margin: 12px 0 0 0;
    }
</style>
@endpush