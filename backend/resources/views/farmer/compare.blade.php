@extends('layouts.app')

@section('title', 'Compare My Years')

@section('content')

{{-- ═══════════ PRINT HEADER ═══════════ --}}
<div class="print-only print-header">
    <div class="print-header-brand">
        <div>
            <div class="print-header-agency">City Agriculture Office · Santiago City</div>
            <div class="print-header-title">My Yield History</div>
        </div>
        <div class="print-header-meta">
            <div>Printed: <strong id="printGeneratedAt">—</strong></div>
            <div>Reference: <strong>MY-YIELD-<span id="printRef"></span></strong></div>
        </div>
    </div>
    <div id="printFiltersLine" class="print-header-filters"></div>
    <hr class="print-rule">
</div>

{{-- ═══════════ HEADER ═══════════ --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
    <div>
        <h4 style="font-weight: 800; color: var(--slate-900); margin: 0;">
        </h4>
        <p class="text-muted mb-0" style="font-size: 12.5px;">
            See how your predictions compared to what you actually harvested.
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-outline-success" onclick="printPage()" id="printBtn" disabled>
            <i class="bi bi-printer-fill"></i> Print
        </button>
        <a href="{{ route('farmer.predictions.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

{{-- ═══════════ SIMPLE FILTERS ═══════════ --}}
<div class="card-custom mb-3 no-print" style="padding: 18px 20px;">

    {{-- YEARS — tappable chips, no Ctrl key needed --}}
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
            <label class="form-label mb-0 fw-semibold" style="font-size: 13px;">
                <i class="bi bi-calendar-range" style="color: var(--brand-green);"></i>
                Which years?
            </label>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllYears(true)">
                    Select all
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllYears(false)">
                    Clear
                </button>
            </div>
        </div>

        <div id="yearChips" class="d-flex flex-wrap gap-2">
            @foreach($years as $y)
                <button type="button"
                        class="year-chip {{ $loop->index < 5 ? 'active' : '' }}"
                        data-year="{{ $y }}"
                        onclick="toggleYear(this)">
                    {{ $y }}{{ (int) $y === (int) now()->year ? ' (Current)' : '' }}
                </button>
            @endforeach
        </div>

        @if($years->isEmpty())
            <div class="alert alert-info mb-0 mt-2" style="border-radius: 10px;">
                <i class="bi bi-info-circle"></i>
                You don't have any seasons yet. Add one to see comparisons.
            </div>
        @endif

        <div id="yearHint" class="text-muted mt-2" style="font-size: 12px;">
            <i class="bi bi-hand-index-thumb"></i> Tap a year to include or exclude it. Pick at least 2.
        </div>
    </div>

    {{-- OPTIONAL FARM FILTER --}}
    @if($farms->count() > 1)
        <div class="pt-3" style="border-top: 1px dashed var(--slate-200);">
            <label class="form-label mb-2 fw-semibold" style="font-size: 13px;">
                <i class="bi bi-geo-alt" style="color: var(--brand-green);"></i>
                Which farm?
                <span class="text-muted fw-normal">(optional)</span>
            </label>
            <select id="compareFarm" class="form-select" onchange="runComparison()" style="max-width: 360px;">
                <option value="">All my farms</option>
                @foreach($farms as $f)
                    <option value="{{ $f->id }}">{{ $f->name }}{{ $f->barangay ? ' · ' . $f->barangay : '' }}</option>
                @endforeach
            </select>
        </div>
    @endif
</div>

{{-- ═══════════ EMPTY STATE ═══════════ --}}
<div id="emptyState" class="card-custom" style="display: none;">
    <div class="text-center py-5">
        <i class="bi bi-bar-chart" style="font-size: 48px; color: var(--slate-300);"></i>
        <h5 class="mt-3 mb-2" style="color: var(--slate-700); font-weight: 700;">
            No data to show yet
        </h5>
        <p class="mb-0" style="font-size: 13px; color: var(--slate-500);">
            Select at least 2 years, or add a season to one of your farms first.
        </p>
    </div>
</div>

{{-- ═══════════ SUMMARY CARDS ═══════════ --}}
<div id="summaryCards" class="row g-2 mb-3" style="display: none;">
    <div class="col-12 col-sm-4">
        <div class="card-custom" style="padding: 16px 18px; border-left: 4px solid #4f46e5;">
            <div class="text-muted" style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                Seasons Compared
            </div>
            <div id="statRecords" style="font-size: 26px; font-weight: 800; color: #4f46e5; line-height: 1.1; margin-top: 4px;">—</div>
            <div class="text-muted" style="font-size: 11px;">Your records with a harvest</div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="card-custom" style="padding: 16px 18px; border-left: 4px solid var(--brand-gold);">
            <div class="text-muted" style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                Average Difference
            </div>
            <div id="statDiff" style="font-size: 26px; font-weight: 800; color: var(--brand-gold-dark); line-height: 1.1; margin-top: 4px;">—</div>
            <div class="text-muted" style="font-size: 11px;">Between predicted & your harvest</div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="card-custom" style="padding: 16px 18px; border-left: 4px solid var(--slate-400);">
            <div class="text-muted" style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                Years Shown
            </div>
            <div id="statYears" style="font-size: 26px; font-weight: 800; color: var(--slate-700); line-height: 1.1; margin-top: 4px;">—</div>
            <div class="text-muted" style="font-size: 11px;">Across your farms</div>
        </div>
    </div>
</div>

{{-- ═══════════ CHART ═══════════ --}}
<div id="chartCard" class="card-custom mb-3" style="display: none;">
    <div class="card-title d-flex justify-content-between align-items-center">
        <span>Predicted vs. Your Harvest</span>
        <span class="text-muted no-print" id="chartStatus" style="font-size: 11px;"></span>
    </div>

    <div id="compareChartWrap" style="height: 380px; position: relative;">
        <canvas id="compareChart"></canvas>
        <img id="compareChartPrint" alt="Chart" style="display:none;">
    </div>

    <div id="missingYearsWarning" class="alert alert-warning mt-3" style="display:none; font-size: 12.5px; border-radius: 10px;">
        <i class="bi bi-info-circle"></i>
        <span id="missingYearsList"></span>
    </div>
</div>

{{-- ═══════════ YEAR TABLE ═══════════ --}}
<div id="tableCard" class="card-custom" style="display: none;">
    <div class="card-title">
        <i class="bi bi-table"></i> Year by Year
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Year</th>
                    <th>Seasons</th>
                    <th>Predicted (t/ha)</th>
                    <th>Your Harvest (t/ha)</th>
                    <th>Difference</th>
                </tr>
            </thead>
            <tbody id="compareTableBody"></tbody>
        </table>
    </div>
    <p class="text-muted mb-0 mt-3 no-print" style="font-size: 11.5px;">
        <strong>Difference</strong> shows how much higher (+) or lower (−) your real harvest was
        compared to the prediction.
    </p>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ═══════════════════════════════════════════════════════════
// STATE
// ═══════════════════════════════════════════════════════════
let compareChart       = null;
let lastData           = null;
let chartPngDataUrl    = null;
let chartPngReady      = false;

// ═══════════════════════════════════════════════════════════
// YEAR CHIPS
// ═══════════════════════════════════════════════════════════
function toggleYear(btn) {
    btn.classList.toggle('active');
    runComparison();
}

function selectAllYears(active) {
    document.querySelectorAll('.year-chip').forEach(btn => {
        btn.classList.toggle('active', active);
    });
    runComparison();
}

function getSelectedYears() {
    return Array.from(document.querySelectorAll('.year-chip.active'))
        .map(b => parseInt(b.dataset.year, 10))
        .sort((a, b) => a - b);
}

// ═══════════════════════════════════════════════════════════
// FETCH + RENDER
// ═══════════════════════════════════════════════════════════
async function runComparison() {
    const years = getSelectedYears();

    if (years.length < 2) {
        showEmpty('Please select at least 2 years.');
        return;
    }

    const farmId = document.getElementById('compareFarm')?.value || null;

    document.getElementById('chartStatus').textContent = 'Loading…';

    try {
        const res = await fetch('{{ route("farmer.predictions.compare.data") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ years, farm_id: farmId }),
        });

        const data = await res.json();

        if (!data.success || !data.labels || data.labels.length === 0) {
            showEmpty('No seasons match those years.');
            return;
        }

        lastData = data;
        render(data);

    } catch (err) {
        console.error(err);
        document.getElementById('chartStatus').textContent = 'Error';
        showEmpty('Something went wrong. Please try again.');
    }
}

function showEmpty(message) {
    document.getElementById('emptyState').style.display = 'block';
    document.getElementById('summaryCards').style.display = 'none';
    document.getElementById('chartCard').style.display = 'none';
    document.getElementById('tableCard').style.display = 'none';
    document.getElementById('printBtn').disabled = true;
    if (message) {
        document.querySelector('#emptyState p').textContent = message;
    }
}

function render(data) {
    document.getElementById('emptyState').style.display = 'none';
    document.getElementById('summaryCards').style.display = 'flex';
    document.getElementById('chartCard').style.display = 'block';
    document.getElementById('tableCard').style.display = 'block';
    document.getElementById('printBtn').disabled = false;

    // Summary cards
    const overall = data.overall || {};
    document.getElementById('statRecords').textContent = overall.matched ?? 0;
    document.getElementById('statDiff').textContent =
        (overall.matched > 0 && overall.mae !== null)
            ? overall.mae.toFixed(2) + ' t/ha'
            : '—';
    document.getElementById('statYears').textContent = overall.years ?? data.labels.length;

    // Chart
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
                    label: 'AI Forecast (t/ha)',
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
                    label: 'Your Harvest (t/ha)',
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
                        label: (context) => {
                            const i = context.dataIndex;
                            const meta = data.meta[i];
                            const lines = [`${context.dataset.label}: ${context.parsed.y ?? '—'} t/ha`];
                            if (meta) lines.push(`${meta.records} season(s)`);
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

    document.getElementById('chartStatus').textContent = `${data.labels.length} year(s)`;

    // Missing years
    const warn = document.getElementById('missingYearsWarning');
    const list = document.getElementById('missingYearsList');
    if (data.missingYears && data.missingYears.length > 0) {
        list.textContent = ' No seasons in ' + data.missingYears.join(', ') + '.';
        warn.style.display = 'block';
    } else {
        warn.style.display = 'none';
    }

    // Table
    renderTable(data.perYear);

    // Pre-render chart PNG for printing
    setTimeout(prerenderChart, 300);
}

function renderTable(perYear) {
    const tbody = document.getElementById('compareTableBody');
    tbody.innerHTML = '';

    perYear.forEach(row => {
        let deltaHtml = '<span class="text-muted">—</span>';
        if (row.delta !== null) {
            const color = row.delta > 0.05 ? '#15803d' : (row.delta < -0.05 ? '#b91c1c' : 'var(--slate-500)');
            const icon  = row.delta > 0.05 ? 'arrow-up' : (row.delta < -0.05 ? 'arrow-down' : 'dash');
            deltaHtml = `<span style="color:${color}; font-weight:700;">
                <i class="bi bi-${icon}"></i>
                ${row.delta > 0 ? '+' : ''}${row.delta.toFixed(2)} t/ha
            </span>`;
        }

        tbody.innerHTML += `
            <tr>
                <td><strong>${row.year}</strong></td>
                <td>${row.records > 0 ? row.records : '<span class="text-muted">0</span>'}</td>
                <td>${row.avgPred !== null ? row.avgPred.toFixed(2) : '<span class="text-muted">—</span>'}</td>
                <td>${row.avgAct  !== null ? row.avgAct.toFixed(2)  : '<span class="text-muted">—</span>'}</td>
                <td>${deltaHtml}</td>
            </tr>
        `;
    });
}

// ═══════════════════════════════════════════════════════════
// PRE-RENDER CHART PNG FOR PRINT
// ═══════════════════════════════════════════════════════════
function prerenderChart() {
    if (!compareChart) return;
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
        console.error('[print] PNG failed', e);
    }
}

// ═══════════════════════════════════════════════════════════
// PRINT
// ═══════════════════════════════════════════════════════════
function printPage() {
    if (!lastData) return;

    const now = new Date();
    document.getElementById('printGeneratedAt').textContent =
        now.toLocaleString('en-US', {
            year: 'numeric', month: 'long', day: '2-digit',
            hour: '2-digit', minute: '2-digit'
        });
    document.getElementById('printRef').textContent =
        String(now.getTime()).slice(-6);

    // Filter summary
    const years = getSelectedYears().join(', ');
    const farmSel = document.getElementById('compareFarm');
    const farmName = farmSel && farmSel.value
        ? farmSel.options[farmSel.selectedIndex].text
        : 'All my farms';
    document.getElementById('printFiltersLine').textContent =
        'Years: ' + years + '   ·   Farm: ' + farmName;

    // Fallback PNG if not ready yet
    if (!chartPngReady && compareChart) {
        try {
            chartPngDataUrl = compareChart.toBase64Image('image/png', 1);
            chartPngReady = true;
        } catch (e) {}
    }

    const canvas = document.getElementById('compareChart');
    const img    = document.getElementById('compareChartPrint');

    if (chartPngReady && img && chartPngDataUrl) {
        img.src = chartPngDataUrl;
        img.style.cssText = 'display:block !important;width:100% !important;height:auto !important;';
        canvas.style.cssText = 'display:none !important;';
    }

    setTimeout(() => {
        window.print();
        setTimeout(() => {
            canvas.style.cssText = '';
            img.style.cssText = 'display:none;';
        }, 1000);
    }, 250);
}

// Ctrl+P safety net
window.addEventListener('beforeprint', () => {
    if (!chartPngReady || !chartPngDataUrl) return;
    const canvas = document.getElementById('compareChart');
    const img    = document.getElementById('compareChartPrint');
    if (canvas) canvas.style.cssText = 'display:none !important;';
    if (img) {
        img.src = chartPngDataUrl;
        img.style.cssText = 'display:block !important;width:100% !important;';
    }
});
window.addEventListener('afterprint', () => {
    const canvas = document.getElementById('compareChart');
    const img    = document.getElementById('compareChartPrint');
    if (canvas) canvas.style.cssText = '';
    if (img) img.style.cssText = 'display:none;';
});

// ═══════════════════════════════════════════════════════════
// BOOT
// ═══════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    if (getSelectedYears().length >= 2) {
        runComparison();
    } else {
        showEmpty('Please select at least 2 years.');
    }
});
</script>
@endpush

@push('styles')
<style>
    /* Year chips */
    .year-chip {
        padding: 8px 18px;
        border-radius: 999px;
        border: 1.5px solid var(--slate-200);
        background: #fff;
        color: var(--slate-600);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s ease;
    }
    .year-chip:hover {
        border-color: var(--brand-green);
        color: var(--brand-green);
        transform: translateY(-1px);
    }
    .year-chip.active {
        background: var(--brand-green);
        border-color: var(--brand-green);
        color: #fff;
        box-shadow: 0 3px 8px rgba(22, 91, 51, 0.25);
    }

    .print-only { display: none; }
    #compareChartPrint { display: none; }

    @media print {
        .no-print,
        .no-print * { display: none !important; }
        .print-only { display: block !important; }
        .modal-backdrop { display: none !important; }

        .card-custom {
            box-shadow: none !important;
            border: 1px solid #cbd5e1 !important;
            page-break-inside: avoid;
            margin-bottom: 12px !important;
        }

        #compareChartWrap {
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
            page-break-inside: avoid;
        }
        #compareChartWrap canvas { display: none !important; }
        #compareChartWrap img,
        #compareChartPrint {
            display: block !important;
            width: 100% !important;
            height: auto !important;
            max-height: 480px !important;
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
        body { font-size: 11px; }
        a { color: inherit !important; text-decoration: none !important; }
    }

    .print-header { margin-bottom: 16px; }
    .print-header-brand {
        display: flex; justify-content: space-between; align-items: flex-start;
        gap: 16px; flex-wrap: wrap;
    }
    .print-header-agency {
        font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase;
        color: #b45309; font-weight: 700;
    }
    .print-header-title {
        font-size: 18px; font-weight: 800; color: #165b33;
        letter-spacing: -0.3px; margin-top: 2px;
    }
    .print-header-meta {
        font-size: 10px; color: #475569;
        text-align: right; line-height: 1.5;
    }
    .print-header-filters {
        margin-top: 10px; font-size: 10px; color: #334155;
        padding: 8px 10px; background: #f1f5f9;
        border-left: 3px solid #165b33; border-radius: 4px;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .print-rule {
        border: none; border-top: 2px solid #165b33; margin: 12px 0 0 0;
    }
</style>
@endpush