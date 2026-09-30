<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login Slip — {{ $farmer->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            padding: 40px;
            background: #f8fafc;
            color: #1e293b;
        }
        .slip {
            max-width: 480px;
            margin: 0 auto;
            border: 2px dashed #165b33;
            border-radius: 12px;
            padding: 28px;
            background: #f2f9f4;
        }
        .slip .label {
            font-size: 11px;
            text-transform: uppercase;
            color: #0d381f;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .slip .name {
            font-weight: 800;
            font-size: 22px;
            margin: 6px 0 14px;
            color: #0f172a;
        }
        .slip .field {
            font-size: 14px;
            color: #334155;
            margin-bottom: 4px;
        }
        .slip .field strong {
            color: #0f172a;
        }
        .slip .pin-box {
            font-family: monospace;
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 8px;
            color: #165b33;
            text-align: center;
            padding: 16px 0;
            background: white;
            border-radius: 10px;
            margin-top: 8px;
        }
        .slip hr {
            border: none;
            border-top: 1px dashed #c7e6d2;
            margin: 16px 0;
        }
        @media print {
            body { background: white; padding: 0; }
            .slip { border-color: #999; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="slip">
    <div class="label">CROPS Login Slip</div>
    <div class="name">{{ $farmer->name }}</div>

    <div class="field">RSBSA: <strong>{{ $farmer->rsbsa_number ?: '—' }}</strong></div>
    <div class="field">Barangay: <strong>{{ $farmer->barangay ?: '—' }}</strong></div>
    <div class="field">Phone: <strong style="font-family: monospace;">{{ $farmer->phone ?: '—' }}</strong></div>

    <hr>

    @if($farmer->pin)
        <div class="label" style="text-align: center;">Your PIN</div>
        <div class="pin-box">{{ $farmer->pin }}</div>
    @else
        <div class="label" style="text-align: center;">PIN not available</div>
        <div style="text-align: center; font-size: 13px; color: #64748b; margin-top: 8px;">
            Ask the CAO office to generate a new PIN.
        </div>
    @endif

    <hr>

    <div style="font-size: 12px; color: #475569; text-align: center;">
        Login at the CROPS website with your phone number and this PIN.
    </div>
</div>

<div class="text-center mt-4 no-print">
    <button onclick="window.print()" class="btn btn-success">
        <i class="bi bi-printer"></i> Print
    </button>
    <a href="{{ route('admin.farmers.credentials') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

</body>
</html>