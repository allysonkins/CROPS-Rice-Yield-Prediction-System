<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>CROPS - Yield Report</title>
    <style>
        @page { margin: 30px 40px; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1e293b; font-size: 11px; line-height: 1.45; }

        h1 { color: #165b33; font-size: 22px; margin: 0; letter-spacing: -0.3px; }
        h2 {
            color: #165b33; font-size: 13px; margin: 22px 0 10px;
            border-bottom: 2px solid #165b33; padding-bottom: 5px;
            text-transform: uppercase; letter-spacing: 0.6px;
        }

        /* Header */
        .header { text-align: center; padding-bottom: 16px; margin-bottom: 8px; border-bottom: 3px solid #d97706; }
        .header p { color: #64748b; font-size: 11px; margin: 3px 0; }
        .header .brand { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: #d97706; font-weight: 700; margin-bottom: 6px; }

        /* Stat grid */
        .stats-grid { width: 100%; margin-bottom: 15px; border-collapse: separate; border-spacing: 6px 0; }
        .stats-grid td {
            width: 25%; padding: 10px 8px; background: #f8fafc;
            border: 1px solid #e2e8f0; text-align: center; border-radius: 6px;
        }
        .stats-grid .number { font-size: 20px; font-weight: 700; color: #165b33; display: block; line-height: 1.1; }
        .stats-grid .label { font-size: 9px; color: #64748b; display: block; margin-top: 3px; letter-spacing: 0.4px; text-transform: uppercase; }

        /* Generic tables */
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 10px; }
        thead th {
            background: #165b33; color: white; padding: 7px 8px;
            text-align: left; font-weight: 700; font-size: 9.5px;
            text-transform: uppercase; letter-spacing: 0.3px;
        }
        tbody td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        tbody tr:nth-child(even) { background: #f8fafc; }

        /* Class badges */
        .badge-high { color: #165b33; font-weight: 700; }
        .badge-medium { color: #b45309; font-weight: 700; }
        .badge-low { color: #b91c1c; font-weight: 700; }

        .pill {
            display: inline-block; padding: 1px 8px; border-radius: 10px;
            font-size: 9px; font-weight: 700; letter-spacing: 0.3px;
        }
        .pill.high   { background: #d1fae5; color: #065f46; }
        .pill.medium { background: #fef3c7; color: #92400e; }
        .pill.low    { background: #fee2e2; color: #991b1b; }

        /* Distribution bars */
        .dist-row td { padding: 8px; }
        .bar-wrap { width: 100%; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
        .bar { height: 8px; border-radius: 4px; }
        .bar.high   { background: #165b33; }
        .bar.medium { background: #d97706; }
        .bar.low    { background: #dc2626; }

        .footer {
            text-align: center; font-size: 9px; color: #94a3b8;
            border-top: 1px solid #e2e8f0; padding-top: 12px; margin-top: 20px;
        }
        .footer strong { color: #165b33; }
        .page-break { page-break-after: always; }
        .muted { color: #94a3b8; }
    </style>
</head>
<body>

{{-- ═══════════ HEADER ═══════════ --}}
<div class="header">
    <div class="brand">City Agriculture Office · Santiago City</div>
    <h1>CROPS — Rice Yield Report</h1>
    <p>Machine Learning-Based Rice Yield Prediction System</p>
    <p>Generated: {{ $generated_at->format('F d, Y h:i A') }}</p>
</div>

{{-- ═══════════ OVERVIEW ═══════════ --}}
<h2>Overview</h2>
<table class="stats-grid">
    <tr>
        <td><span class="number">{{ $overview['total_farmers'] }}</span><span class="label">Farmers</span></td>
        <td><span class="number">{{ $overview['total_farms'] }}</span><span class="label">Farms</span></td>
        <td><span class="number">{{ number_format($overview['total_area_ha'], 1) }}</span><span class="label">Hectares</span></td>
        <td><span class="number">{{ $overview['total_varieties'] }}</span><span class="label">Varieties</span></td>
    </tr>
    <tr>
        <td><span class="number">{{ $overview['total_farm_records'] }}</span><span class="label">Farm Records</span></td>
        <td><span class="number">{{ $overview['vegetative_records'] }}</span><span class="label">Growing</span></td>
        <td><span class="number">{{ $overview['harvested_records'] }}</span><span class="label">Harvested</span></td>
        <td><span class="number">{{ $overview['total_predictions'] }}</span><span class="label">Predictions</span></td>
    </tr>
</table>

{{-- ═══════════ CLASS DISTRIBUTION ═══════════ --}}
<h2>Prediction Class Distribution</h2>
@php
    $cls = $overview['yield_class_counts'];
    $h = $cls['High'] ?? 0; $m = $cls['Medium'] ?? 0; $l = $cls['Low'] ?? 0;
    $t = max(1, $h + $m + $l);
    $hp = round(($h / $t) * 100, 1);
    $mp = round(($m / $t) * 100, 1);
    $lp = round(($l / $t) * 100, 1);
@endphp
<table>
    <thead>
        <tr>
            <th style="width: 18%;">Class</th>
            <th style="width: 12%;">Count</th>
            <th style="width: 14%;">Share</th>
            <th>Distribution</th>
        </tr>
    </thead>
    <tbody>
        <tr class="dist-row">
            <td><span class="pill high">HIGH</span></td>
            <td><strong>{{ $h }}</strong></td>
            <td>{{ $hp }}%</td>
            <td>
                <div class="bar-wrap"><div class="bar high" style="width: {{ $hp }}%;"></div></div>
            </td>
        </tr>
        <tr class="dist-row">
            <td><span class="pill medium">MEDIUM</span></td>
            <td><strong>{{ $m }}</strong></td>
            <td>{{ $mp }}%</td>
            <td>
                <div class="bar-wrap"><div class="bar medium" style="width: {{ $mp }}%;"></div></div>
            </td>
        </tr>
        <tr class="dist-row">
            <td><span class="pill low">LOW</span></td>
            <td><strong>{{ $l }}</strong></td>
            <td>{{ $lp }}%</td>
            <td>
                <div class="bar-wrap"><div class="bar low" style="width: {{ $lp }}%;"></div></div>
            </td>
        </tr>
    </tbody>
</table>
<p style="font-size: 9px; color: #94a3b8; margin-top: -6px;">
    Classes are variety-relative: High ≥ 112.5% of the variety's own average yield, Medium 87.5%–112.5%, Low below 87.5%.
    Derived in the application — not a direct model output.
</p>

<div class="page-break"></div>

{{-- ═══════════ BARANGAY SUMMARY ═══════════ --}}
<h2>Summary by Barangay</h2>
<table>
    <thead>
        <tr>
            <th>Barangay</th>
            <th>Farms</th>
            <th>Total Area (ha)</th>
            <th>Farm Records</th>
            <th>Avg Predicted Yield (t/ha)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($by_barangay as $row)
            <tr>
                <td><strong>{{ $row['Barangay'] }}</strong></td>
                <td>{{ $row['Farms'] }}</td>
                <td>{{ $row['Total Area (ha)'] }}</td>
                <td>{{ $row['Farm Records'] }}</td>
                <td><strong style="color:#165b33;">{{ $row['Avg Predicted Yield'] }}</strong></td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align: center; color: #94a3b8;">No barangay data</td></tr>
        @endforelse
    </tbody>
</table>

{{-- ═══════════ VARIETY SUMMARY ═══════════ --}}
<h2>Summary by Rice Variety</h2>
<table>
    <thead>
        <tr>
            <th>Variety</th>
            <th>Class</th>
            <th>Records</th>
            <th>Avg Yield (t/ha)</th>
            <th>Max Yield (t/ha)</th>
            <th>Avg Predicted</th>
        </tr>
    </thead>
    <tbody>
        @forelse($by_variety as $row)
            <tr>
                <td><strong>{{ $row['Variety'] }}</strong></td>
                <td>{{ $row['Classification'] }}</td>
                <td>{{ $row['Records'] }}</td>
                <td>{{ $row['Avg Yield (t/ha)'] }}</td>
                <td>{{ $row['Max Yield (t/ha)'] }}</td>
                <td>{{ $row['Avg Predicted'] }}</td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align: center; color: #94a3b8;">No variety data</td></tr>
        @endforelse
    </tbody>
</table>

<div class="page-break"></div>

{{-- ═══════════ TOP PERFORMERS ═══════════ --}}
<h2>Top 10 Performing Farms</h2>
<table>
    <thead>
        <tr>
            <th style="width: 6%;">#</th>
            <th>Farm</th>
            <th>Barangay</th>
            <th>Farmer</th>
            <th>Variety</th>
            <th style="text-align: right;">Yield</th>
        </tr>
    </thead>
    <tbody>
        @forelse($top_farms as $i => $f)
            <tr>
                <td style="color:#94a3b8; font-weight:700;">{{ $i + 1 }}</td>
                <td><strong>{{ $f['Farm'] }}</strong></td>
                <td>{{ $f['Barangay'] }}</td>
                <td>{{ $f['Farmer'] }}</td>
                <td>{{ $f['Variety'] }}</td>
                <td style="text-align: right;"><span class="pill high">{{ $f['Yield'] }}</span></td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align: center; color: #94a3b8;">No data</td></tr>
        @endforelse
    </tbody>
</table>

{{-- ═══════════ LOW PERFORMERS ═══════════ --}}
<h2>Farms Needing Attention</h2>
<table>
    <thead>
        <tr>
            <th style="width: 6%;">#</th>
            <th>Farm</th>
            <th>Barangay</th>
            <th>Farmer</th>
            <th>Variety</th>
            <th style="text-align: right;">Yield</th>
        </tr>
    </thead>
    <tbody>
        @forelse($low_farms as $i => $f)
            <tr>
                <td style="color:#94a3b8; font-weight:700;">{{ $i + 1 }}</td>
                <td><strong>{{ $f['Farm'] }}</strong></td>
                <td>{{ $f['Barangay'] }}</td>
                <td>{{ $f['Farmer'] }}</td>
                <td>{{ $f['Variety'] }}</td>
                <td style="text-align: right;"><span class="pill low">{{ $f['Yield'] }}</span></td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align: center; color: #94a3b8;">No data</td></tr>
        @endforelse
    </tbody>
</table>

<div class="page-break"></div>

{{-- ═══════════ ALL FARM RECORDS ═══════════ --}}
<h2>All Farm Records</h2>
<table>
    <thead>
        <tr>
            <th>Farm</th>
            <th>Year</th>
            <th>Season</th>
            <th>Variety</th>
            <th>Predicted</th>
            <th>Actual</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($farm_records as $r)
            <tr>
                <td>{{ $r['Farm'] }}</td>
                <td>{{ $r['Year'] }}</td>
                <td>{{ $r['Season'] }}</td>
                <td>{{ $r['Variety'] }}</td>
                <td>{{ $r['Predicted Yield'] }}</td>
                <td>{{ $r['Actual Yield'] }}</td>
                <td>{{ $r['Status'] }}</td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align: center; color: #94a3b8;">No farm records</td></tr>
        @endforelse
    </tbody>
</table>

{{-- ═══════════ FOOTER ═══════════ --}}
<div class="footer">
    <p style="margin: 0 0 3px;"><strong>CROPS</strong> — Machine Learning-Based Rice Yield Prediction System</p>
    <p style="margin: 0;">Santiago City Agriculture Office · {{ $generated_at->format('F d, Y') }}</p>
</div>

</body>
</html>