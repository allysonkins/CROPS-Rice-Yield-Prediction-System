@extends('layouts.app')

@section('title', 'Reports')

@section('content')
@php
    $classes = $overview['yield_class_counts'] ?? ['High' => 0, 'Medium' => 0, 'Low' => 0];
    $high = $classes['High'] ?? 0;
    $med  = $classes['Medium'] ?? 0;
    $low  = $classes['Low'] ?? 0;
    $totalClasses = max(1, $high + $med + $low);
    $highPct = round(($high / $totalClasses) * 100, 1);
    $medPct  = round(($med  / $totalClasses) * 100, 1);
    $lowPct  = round(($low  / $totalClasses) * 100, 1);
    $totalPred = $overview['total_predictions'] ?? 0;
@endphp

{{-- ═══════════ HEADER ═══════════ --}}
<div class="row g-2 mb-3">
    <div class="col-12">
        <div class="card-custom" style="border-left: 4px solid var(--gold); background: linear-gradient(135deg, #f9fafb 0%, #f0f4f2 100%); padding: 16px 22px;">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-file-earmark-bar-graph-fill" style="color: var(--gold); font-size: 22px;"></i>
                    <div>
                        <div style="font-size: 15px; font-weight: 700; color: var(--green);">Yield Reports</div>
                        <div class="text-muted" style="font-size: 12px;">Comprehensive analytics for Santiago City rice production.</div>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.reports.generate-pdf') }}" class="btn btn-danger btn-sm">
                        <i class="bi bi-file-earmark-pdf-fill"></i> Download PDF
                    </a>
                    <a href="{{ route('admin.reports.generate-excel') }}" class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-excel-fill"></i> Download Excel
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════ TOP STATS ROW ═══════════ --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-lg-4 col-xl">
        <div class="stat-card" style="padding: 14px 18px;">
            <div class="stat-info">
                <div class="label" style="font-size: 12px;">Total Farmers</div>
                <div class="value" style="font-size: 22px;">{{ $overview['total_farmers'] }}</div>
                <div class="text-muted" style="font-size: 11px; margin-top: 2px;">
                    {{ $overview['verified_farmers'] }} verified · {{ $overview['pending_farmers'] }} pending
                </div>
            </div>
            <div class="stat-icon" style="width: 40px; height: 40px; font-size: 18px;"><i class="bi bi-people-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-4 col-xl">
        <div class="stat-card" style="padding: 14px 18px;">
            <div class="stat-info">
                <div class="label" style="font-size: 12px;">Total Farms</div>
                <div class="value" style="font-size: 22px;">{{ $overview['total_farms'] }}</div>
                <div class="text-muted" style="font-size: 11px; margin-top: 2px;">
                    {{ number_format($overview['total_area_ha'], 1) }} ha total
                </div>
            </div>
            <div class="stat-icon" style="width: 40px; height: 40px; font-size: 18px;"><i class="bi bi-geo-alt-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-4 col-xl">
        <div class="stat-card" style="padding: 14px 18px;">
            <div class="stat-info">
                <div class="label" style="font-size: 12px;">Farm Records</div>
                <div class="value" style="font-size: 22px;">{{ $overview['total_farm_records'] }}</div>
                <div class="text-muted" style="font-size: 11px; margin-top: 2px;">
                    {{ $overview['vegetative_records'] }} growing · {{ $overview['harvested_records'] }} harvested
                </div>
            </div>
            <div class="stat-icon" style="width: 40px; height: 40px; font-size: 18px;"><i class="bi bi-clipboard-data-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-4 col-xl">
        <div class="stat-card" style="padding: 14px 18px;">
            <div class="stat-info">
                <div class="label" style="font-size: 12px;">Rice Varieties</div>
                <div class="value" style="font-size: 22px;">{{ $overview['total_varieties'] }}</div>
                <div class="text-muted" style="font-size: 11px; margin-top: 2px;">
                    in production DB
                </div>
            </div>
            <div class="stat-icon" style="width: 40px; height: 40px; font-size: 18px;"><i class="bi bi-flower1"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-4 col-xl">
        <div class="stat-card" style="padding: 14px 18px; border-top: 3px solid var(--green);">
            <div class="stat-info">
                <div class="label" style="font-size: 12px;">Avg Predicted Yield</div>
                <div class="value" style="font-size: 22px;">
                    {{ $overview['avg_predicted_yield'] !== null ? number_format($overview['avg_predicted_yield'], 2) : '—' }}
                    <small style="font-size:12px;color:var(--gray-500);">t/ha</small>
                </div>
                <div class="text-muted" style="font-size: 11px; margin-top: 2px;">
                    Across {{ $totalPred }} prediction{{ $totalPred !== 1 ? 's' : '' }}
                </div>
            </div>
            <div class="stat-icon" style="width: 40px; height: 40px; font-size: 18px;"><i class="bi bi-graph-up-arrow"></i></div>
        </div>
    </div>
</div>

{{-- ═══════════ CLASS DISTRIBUTION PANEL ═══════════ --}}
<div class="row g-2 mb-3">
    <div class="col-12">
        <div class="card-custom" style="border-top: 3px solid var(--green); padding: 16px 20px;">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-pie-chart-fill" style="color: var(--green); font-size: 18px;"></i>
                    <span style="font-size: 14px; font-weight: 700;">Prediction Class Distribution</span>
                    <i class="bi bi-question-circle-fill"
                       style="font-size: 12px; color: var(--gray-400); cursor: help;"
                       data-bs-toggle="tooltip"
                       data-bs-placement="top"
                       data-bs-html="true"
                       title="<div style='text-align:left; font-size:12px; line-height:1.5;'>
                           <strong>Variety-relative class</strong><br>
                           Each prediction is compared against its variety's <em>own</em> average yield.<br><br>
                           • <strong>High</strong> — ≥ 112.5% of variety average<br>
                           • <strong>Medium</strong> — 87.5% – 112.5%<br>
                           • <strong>Low</strong> — below 87.5%<br><br>
                           <em>Derived in the app — not a model output.</em>
                       </div>"></i>
                </div>
                <span class="text-muted" style="font-size: 11px;">
                    {{ $totalClasses }} classified prediction{{ $totalClasses !== 1 ? 's' : '' }}
                </span>
            </div>

            <div class="row g-2">
                {{-- HIGH --}}
                <div class="col-md-4">
                    <div style="background: var(--brand-green-light); border-radius: 12px; padding: 16px;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--brand-green-dark); font-weight: 700; letter-spacing: 0.5px;">High</span>
                            <span class="badge-status high" style="font-size: 10px;"><span class="dot"></span> High</span>
                        </div>
                        <div style="font-size: 28px; font-weight: 800; color: var(--brand-green); line-height: 1.1;">{{ $high }}</div>
                        <div style="font-size: 12px; color: var(--slate-500); margin-bottom: 10px;">{{ $highPct }}% of predictions</div>
                        <div style="height: 6px; background: rgba(255,255,255,0.7); border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; width: {{ $highPct }}%; background: var(--brand-green); border-radius: 4px;"></div>
                        </div>
                    </div>
                </div>

                {{-- MEDIUM --}}
                <div class="col-md-4">
                    <div style="background: var(--brand-gold-light); border-radius: 12px; padding: 16px;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span style="font-size: 11px; text-transform: uppercase; color: #92400e; font-weight: 700; letter-spacing: 0.5px;">Medium</span>
                            <span class="badge-status medium" style="font-size: 10px;"><span class="dot"></span> Medium</span>
                        </div>
                        <div style="font-size: 28px; font-weight: 800; color: var(--brand-gold); line-height: 1.1;">{{ $med }}</div>
                        <div style="font-size: 12px; color: var(--slate-500); margin-bottom: 10px;">{{ $medPct }}% of predictions</div>
                        <div style="height: 6px; background: rgba(255,255,255,0.7); border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; width: {{ $medPct }}%; background: var(--brand-gold); border-radius: 4px;"></div>
                        </div>
                    </div>
                </div>

                {{-- LOW --}}
                <div class="col-md-4">
                    <div style="background: var(--brand-danger-light); border-radius: 12px; padding: 16px;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span style="font-size: 11px; text-transform: uppercase; color: #991b1b; font-weight: 700; letter-spacing: 0.5px;">Low</span>
                            <span class="badge-status low" style="font-size: 10px;"><span class="dot"></span> Low</span>
                        </div>
                        <div style="font-size: 28px; font-weight: 800; color: var(--brand-danger); line-height: 1.1;">{{ $low }}</div>
                        <div style="font-size: 12px; color: var(--slate-500); margin-bottom: 10px;">{{ $lowPct }}% of predictions</div>
                        <div style="height: 6px; background: rgba(255,255,255,0.7); border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; width: {{ $lowPct }}%; background: var(--brand-danger); border-radius: 4px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════ TOP + LOW FARMS ═══════════ --}}
<div class="row g-2 mb-3">
    <div class="col-12 col-lg-6">
        <div class="card-custom h-100" style="padding: 16px 20px;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-trophy-fill" style="color: var(--gold); font-size: 16px;"></i>
                    <span style="font-size: 13px; font-weight: 700;">Top 10 Performing Farms</span>
                </div>
                <span class="text-muted" style="font-size: 11px;">By predicted yield</span>
            </div>

            @if($topFarms->isEmpty())
                <div class="text-center py-4">
                    <i class="bi bi-inbox" style="font-size: 32px; color: var(--slate-300);"></i>
                    <p class="mt-2 mb-0" style="font-size: 12px; color: var(--slate-500);">No predictions yet.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th style="padding: 6px 10px;">#</th>
                                <th style="padding: 6px 10px;">Farm</th>
                                <th style="padding: 6px 10px;">Variety</th>
                                <th class="text-end" style="padding: 6px 10px;">Yield</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topFarms as $i => $f)
                                <tr>
                                    <td style="padding: 6px 10px; color: var(--slate-400); font-weight: 700;">{{ $i + 1 }}</td>
                                    <td style="padding: 6px 10px;">
                                        <strong>{{ $f['Farm'] }}</strong>
                                        <br><small class="text-muted">{{ $f['Barangay'] }}</small>
                                    </td>
                                    <td style="padding: 6px 10px;"><small>{{ $f['Variety'] }}</small></td>
                                    <td class="text-end" style="padding: 6px 10px;">
                                        <span class="badge-status high" style="font-size: 11px; padding: 2px 10px;">
                                            <span class="dot"></span> {{ $f['Yield'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card-custom h-100" style="padding: 16px 20px;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill" style="color: var(--brand-danger); font-size: 16px;"></i>
                    <span style="font-size: 13px; font-weight: 700;">Farms Needing Attention</span>
                </div>
                <span class="text-muted" style="font-size: 11px;">Lowest yields</span>
            </div>

            @if($lowFarms->isEmpty())
                <div class="text-center py-4">
                    <i class="bi bi-check-circle-fill" style="font-size: 32px; color: #27ae60;"></i>
                    <p class="mt-2 mb-0" style="font-size: 12px; color: var(--slate-500);">No low-yield farms detected.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th style="padding: 6px 10px;">#</th>
                                <th style="padding: 6px 10px;">Farm</th>
                                <th style="padding: 6px 10px;">Variety</th>
                                <th class="text-end" style="padding: 6px 10px;">Yield</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowFarms as $i => $f)
                                <tr>
                                    <td style="padding: 6px 10px; color: var(--slate-400); font-weight: 700;">{{ $i + 1 }}</td>
                                    <td style="padding: 6px 10px;">
                                        <strong>{{ $f['Farm'] }}</strong>
                                        <br><small class="text-muted">{{ $f['Barangay'] }}</small>
                                    </td>
                                    <td style="padding: 6px 10px;"><small>{{ $f['Variety'] }}</small></td>
                                    <td class="text-end" style="padding: 6px 10px;">
                                        <span class="badge-status low" style="font-size: 11px; padding: 2px 10px;">
                                            <span class="dot"></span> {{ $f['Yield'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ═══════════ EXPORT CTA ═══════════ --}}
<div class="row g-2">
    <div class="col-12">
        <div class="card-custom text-center" style="border-top: 3px solid var(--green); padding: 26px 22px;">
            <i class="bi bi-cloud-arrow-down-fill" style="font-size: 42px; color: var(--brand-green);"></i>
            <h5 class="mt-3" style="font-weight: 800; color: var(--slate-900);">Export Full Report</h5>
            <p style="font-size: 13px; color: var(--slate-500); max-width: 640px; margin: 0 auto;">
                The Excel version contains 7 sheets — Overview, Farmers, Farms, Farm Records, Predictions,
                By Barangay, and By Variety. The PDF is a print-ready report suitable for the City Agriculture Office.
            </p>
            <div class="d-flex justify-content-center gap-2 mt-3 flex-wrap">
                <a href="{{ route('admin.reports.generate-pdf') }}" class="btn btn-danger">
                    <i class="bi bi-file-earmark-pdf-fill"></i> Download PDF
                </a>
                <a href="{{ route('admin.reports.generate-excel') }}" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel-fill"></i> Download Excel (.xlsx)
                </a>
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
</script>
@endpush