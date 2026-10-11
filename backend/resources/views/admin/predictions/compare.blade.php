@extends('layouts.app')

@section('title', 'Compare Predictions')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 style="font-weight: 800; color: var(--slate-900); margin: 0;">
            Historical Comparison
        </h4>
        <p class="text-muted mb-0" style="font-size: 12.5px;">
            Compare predicted and actual yields across multiple years.
        </p>
    </div>
    <a href="{{ route('admin.predictions.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Predictions
    </a>
</div>

<div class="card-custom mb-3" style="padding: 16px 20px;">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label fw-semibold">Years to compare</label>
            <select id="compareYears" class="form-select" multiple size="5" style="font-size: 13px;">
                @foreach($years as $y)
                    <option value="{{ $y }}" {{ $loop->index < 5 ? 'selected' : '' }}>
                        {{ $y }}{{ (int) $y === (int) now()->year ? ' (Current)' : '' }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted">Ctrl / Cmd + click to select multiple.</small>
        </div>

        <div class="col-md-3">
            <label class="form-label fw-semibold">Farmer (optional)</label>
            <input type="text" id="compareFarmer" class="form-control" placeholder="Search farmer name...">
        </div>

        <div class="col-md-3">
            <label class="form-label fw-semibold">Variety (optional)</label>
            <select id="compareVariety" class="form-select">
                <option value="">Any Variety</option>
                @foreach($varieties as $v)
                    <option value="{{ $v->id }}">{{ $v->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label fw-semibold">Season (optional)</label>
            <select id="compareSeason" class="form-select">
                <option value="">Any Season</option>
                <option value="Dry">Dry</option>
                <option value="Wet">Wet</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label fw-semibold">Barangay (optional)</label>
            <select id="compareBarangay" class="form-select">
                <option value="">Any Barangay</option>
                @foreach($barangays as $b)
                    <option value="{{ $b }}">{{ $b }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <button type="button" class="btn btn-success w-100" onclick="runComparison()">
                <i class="bi bi-play-fill"></i> Compare
            </button>
        </div>
    </div>
</div>

<div class="card-custom">
    <div class="card-title d-flex justify-content-between align-items-center">
        <span>Predicted vs. Actual Yield by Year</span>
        <span class="text-muted" id="chartStatus" style="font-size: 11px;"></span>
    </div>

    <div id="compareEmpty" class="text-center py-5 text-muted" style="display:none;">
        <i class="bi bi-bar-chart" style="font-size: 42px; color: var(--slate-300);"></i>
        <h5 class="mt-3 mb-2" style="color: var(--slate-700); font-weight: 700;">No data to compare</h5>
        <p class="mb-0" style="font-size: 13px; color: var(--slate-500);">
            Adjust the filters above and click <strong>Compare</strong>.
        </p>
    </div>

    <div id="compareChartWrap" style="height: 420px;">
        <canvas id="compareChart"></canvas>
    </div>

    <div id="missingYearsWarning" class="alert alert-warning mt-3" style="display:none; font-size: 12.5px; border-radius: 10px;">
        <strong><i class="bi bi-exclamation-triangle"></i> Missing data:</strong>
        <span id="missingYearsList"></span>
    </div>
</div>

{{-- ═══════════════ COMPARE MODAL ═══════════════ --}}
<div class="modal fade" id="compareAlertModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
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
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ═══════════════════════════════════════════════════════════
// COMPARE MODAL — replaces native alert()
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
            document.getElementById('cmpModalTitleText').textContent = opts.title || ({ info: 'Notice', success: 'Success', warning: 'Warning', error: 'Error' }[type] || 'Notice');
            document.getElementById('cmpModalIconGlyph').className = 'bi ' + cfg.icon;

            const iconBox = document.getElementById('cmpModalIconBox');
            iconBox.style.background = cfg.iconBg;
            iconBox.style.color = cfg.iconColor;

            document.getElementById('cmpModalMessage').textContent = message || '';

            const detail = document.getElementById('cmpModalDetail');
            if (opts.detail) {
                detail.textContent = opts.detail;
                detail.style.display = 'block';
            } else {
                detail.style.display = 'none';
            }

            const btn = document.getElementById('cmpModalConfirm');
            btn.className = 'btn ' + cfg.btn;
            btn.textContent = opts.confirmText || 'OK';

            bootstrap.Modal.getOrCreateInstance(document.getElementById('compareAlertModal')).show();
        }
    };
})();

// ═══════════════════════════════════════════════════════════
// COMPARE CHART
// ═══════════════════════════════════════════════════════════
let compareChart = null;

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
        document.getElementById('compareEmpty').style.display = 'block';
        document.getElementById('compareChartWrap').style.display = 'none';
        document.getElementById('chartStatus').textContent = '';
        document.getElementById('missingYearsWarning').style.display = 'none';
        return;
    }

    const payload = {
        years: years,
        farmer: document.getElementById('compareFarmer').value || null,
        variety: document.getElementById('compareVariety').value || null,
        season: document.getElementById('compareSeason').value || null,
        barangay: document.getElementById('compareBarangay').value || null,
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

function renderChart(data) {
    const empty = document.getElementById('compareEmpty');
    const wrap  = document.getElementById('compareChartWrap');

    if (!data.labels || data.labels.length === 0) {
        empty.style.display = 'block';
        wrap.style.display = 'none';
        document.getElementById('chartStatus').textContent = '';
        document.getElementById('missingYearsWarning').style.display = 'none';
        return;
    }

    empty.style.display = 'none';
    wrap.style.display = 'block';

    const ctx = document.getElementById('compareChart').getContext('2d');
    if (compareChart) compareChart.destroy();

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
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const i = context.dataIndex;
                            const meta = data.meta[i];
                            const lines = [
                                `${context.dataset.label}: ${context.parsed.y ?? '—'} t/ha`,
                            ];
                            if (meta) {
                                lines.push(`Records: ${meta.records} (pred: ${meta.predicted}, actual: ${meta.actual})`);
                                if (meta.farmer)  lines.push(`Farmer: ${meta.farmer}`);
                                if (meta.variety) lines.push(`Variety: ${meta.variety}`);
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

    document.getElementById('chartStatus').textContent =
        `${data.labels.length} year(s) compared`;

    const warn = document.getElementById('missingYearsWarning');
    const list = document.getElementById('missingYearsList');
    if (data.missingYears && data.missingYears.length > 0) {
        list.textContent = 'No records found for ' + data.missingYears.join(', ') + '. Those points appear as gaps.';
        warn.style.display = 'block';
    } else {
        warn.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    runComparison(true);
});
</script>
@endpush