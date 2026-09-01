<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>CROPS - Yield Report</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 40px;
            color: #1f2937;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #b8860b;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #0f4c2b;
            font-size: 24px;
            margin: 0;
        }
        .header p {
            color: #6b7280;
            font-size: 12px;
            margin: 4px 0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        .stat-box .number {
            font-size: 22px;
            font-weight: 700;
            color: #0f4c2b;
        }
        .stat-box .label {
            font-size: 12px;
            color: #6b7280;
        }
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #0f4c2b;
            margin: 20px 0 12px 0;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 6px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 20px;
        }
        thead th {
            background: #0f4c2b;
            color: white;
            padding: 8px 10px;
            text-align: left;
        }
        tbody td {
            padding: 6px 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        tbody tr:hover {
            background: #f9fafb;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
            margin-top: 20px;
        }
        .badge-high { color: #0f4c2b; font-weight: 700; }
        .badge-medium { color: #b8860b; font-weight: 700; }
        .badge-low { color: #b22222; font-weight: 700; }
    </style>
</head>
<body>
    <div class="header">
        <h1>CROPS - Yield Report</h1>
        <p>Santiago City Agriculture Office</p>
        <p>Generated: {{ $generated_at->format('F d, Y h:i A') }}</p>
    </div>

    <div class="stats-grid">
        <div class="stat-box">
            <div class="number">{{ $stats['total_farmers'] ?? 0 }}</div>
            <div class="label">Total Farmers</div>
        </div>
        <div class="stat-box">
            <div class="number">{{ $stats['total_farms'] ?? 0 }}</div>
            <div class="label">Total Farms</div>
        </div>
        <div class="stat-box">
            <div class="number">{{ $stats['total_varieties'] ?? 0 }}</div>
            <div class="label">Rice Varieties</div>
        </div>
        <div class="stat-box">
            <div class="number">{{ $stats['avg_yield'] ? number_format($stats['avg_yield'], 2) : 'N/A' }}</div>
            <div class="label">Avg Yield (t/ha)</div>
        </div>
    </div>

    <div class="section-title">Farm Details</div>
    <table>
        <thead>
            <tr>
                <th>Farm Name</th>
                <th>Barangay</th>
                <th>Farmer</th>
                <th>Area (ha)</th>
                <th>Ensemble Yield</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($farms as $farm)
                @php
                    $lastPrediction = $farm->farmRecords
                        ->flatMap(function($r) { return $r->predictions; })
                        ->where('model_type', 'Ensemble')
                        ->last();
                    $yield = $lastPrediction ? $lastPrediction->predicted_yield_tons_ha : null;
                    $status = $yield >= 4.5 ? 'High' : ($yield >= 3.5 ? 'Medium' : 'Low');
                    $badgeClass = $yield >= 4.5 ? 'badge-high' : ($yield >= 3.5 ? 'badge-medium' : 'badge-low');
                @endphp
                <tr>
                    <td><strong>{{ $farm->name }}</strong></td>
                    <td>{{ $farm->barangay }}</td>
                    <td>{{ $farm->user->name ?? 'Unassigned' }}</td>
                    <td>{{ number_format($farm->land_area_ha, 2) }}</td>
                    <td>{{ $yield ? number_format($yield, 2) : 'N/A' }}</td>
                    <td>
                        @if($yield)
                            <span class="{{ $badgeClass }}">{{ $status }}</span>
                        @else
                            <span style="color: #9ca3af;">No Data</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #9ca3af;">No farms available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>CROPS - Machine Learning-Based Rice Yield Prediction System</p>
        <p>Santiago City, Philippines</p>
    </div>
</body>
</html>