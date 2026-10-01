@extends('layouts.app')

@section('title', 'Import Farmers from RSBSA')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="{{ route('admin.farmers.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Farmers
    </a>
    <a href="{{ route('admin.farmers.import.diagnostics') }}" target="_blank" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-speedometer2"></i> Server Diagnostics
    </a>
</div>

@if(session('error'))
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
    </div>
@endif

<div class="card-custom">
    <div class="card-title">
        <i class="bi bi-upload"></i> Bulk Import Farmers (RSBSA CSV)
    </div>

    @if(!isset($rows))
        {{-- ════════ UPLOAD STAGE ════════ --}}
        <p class="text-muted mb-3">
            Upload your RSBSA export. One row per parcel — the system groups parcels by farmer automatically.
        </p>

        @php
            $uploadMax = ini_get('upload_max_filesize');
            $postMax   = ini_get('post_max_size');
        @endphp

        <div class="alert alert-info small py-2 mb-3">
            <i class="bi bi-info-circle"></i>
            Server upload limit: <strong>{{ $uploadMax }}</strong> &nbsp;·&nbsp;
            POST limit: <strong>{{ $postMax }}</strong>.
        </div>

        <form action="{{ route('admin.farmers.import.preview') }}" method="POST"
              enctype="multipart/form-data" id="importForm">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">RSBSA CSV File</label>
                <input type="file" name="csv" id="csvFile" class="form-control" accept=".csv,.txt" required>
                <small class="text-muted d-block mt-2">
                    <strong>Required columns:</strong> <code>first_name, last_name, barangay</code><br>
                    <strong>Required (at least one):</strong> <code>phone</code> <em>or</em> <code>rsbsa_number</code><br>
                    <strong>Optional:</strong> <code>middle_name, sex, parcel_no, parcel_barangay, area_ha, commodity</code>
                </small>
            </div>
            <button class="btn btn-success" id="submitBtn">
                <i class="bi bi-search"></i> Preview Import
            </button>
            <a href="{{ route('admin.farmers.import.template') }}" class="btn btn-outline-secondary">
                <i class="bi bi-download"></i> Download CSV Template
            </a>
        </form>

    @else
        {{-- ════════ PREVIEW STAGE ════════ --}}
        <div class="row g-2 mb-4">
            <div class="col-6 col-md">
                <div class="stat-card"><div class="stat-info">
                    <div class="label">Total Rows</div>
                    <div class="value">{{ number_format($summary['total']) }}</div>
                </div></div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card"><div class="stat-info">
                    <div class="label">New</div>
                    <div class="value text-success">{{ number_format($summary['new']) }}</div>
                </div></div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card"><div class="stat-info">
                    <div class="label">Duplicates</div>
                    <div class="value text-warning">{{ number_format($summary['duplicate']) }}</div>
                </div></div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card"><div class="stat-info">
                    <div class="label">Invalid</div>
                    <div class="value text-danger">{{ number_format($summary['invalid']) }}</div>
                </div></div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card"><div class="stat-info">
                    <div class="label">Non-Rice</div>
                    <div class="value text-muted">{{ number_format($summary['skipped']) }}</div>
                </div></div>
            </div>
        </div>

        <div class="alert alert-secondary small py-2">
            Showing first {{ count($rows) }} of {{ number_format($summary['total']) }} rows.
        </div>

        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
            <table class="table table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Line</th>
                        <th>Status</th>
                        <th>Name</th>
                        <th>RSBSA #</th>
                        <th>Barangay</th>
                        <th>Phone</th>
                        <th>Parcel</th>
                        <th>Area</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($rows as $r)
                    <tr class="{{ $r['status'] === 'new' ? '' : 'table-light' }}">
                        <td>{{ $r['line'] }}</td>
                        <td>
                            @if($r['status'] === 'new')
                                <span class="badge bg-success">New</span>
                            @elseif($r['status'] === 'duplicate')
                                <span class="badge bg-warning text-dark">Duplicate</span>
                            @else
                                <span class="badge bg-danger">Invalid</span>
                            @endif
                            @if(!$r['is_rice'])
                                <br><span class="badge bg-secondary mt-1">Non-rice</span>
                            @endif
                        </td>
                        <td>{{ $r['name'] }}</td>
                        <td><small>{{ $r['rsbsa_number'] ?: '—' }}</small></td>
                        <td>{{ $r['barangay'] }}</td>
                        <td><small>{{ $r['phone'] ?: '—' }}</small></td>
                        <td>{{ $r['parcel_no'] ?: '—' }}</td>
                        <td>{{ $r['land_area_ha'] ? number_format($r['land_area_ha'], 2) : '—' }}</td>
                        <td><small class="text-muted">{{ implode('; ', $r['errors']) }}</small></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if($summary['new'] > 0)
            <div class="mt-3">
                <button type="button" class="btn btn-success" id="commitBtn" onclick="startChunkedImport()">
                    <i class="bi bi-check-circle"></i>
                    Import {{ number_format($summary['new']) }} Farmers
                </button>
                <a href="{{ route('admin.farmers.import.form') }}" class="btn btn-secondary">Cancel</a>

                <div id="importProgress" style="display:none; margin-top:16px;">
                    <div class="d-flex justify-content-between mb-1" style="font-size:12px;">
                        <span class="fw-semibold" style="color:#495057;">Importing…</span>
                        <span id="importProgressText" class="text-muted">Starting…</span>
                    </div>
                    <div class="progress" style="height:22px; border-radius:8px; background:#e9ecef;">
                        <div id="importProgressBar"
                             class="progress-bar progress-bar-striped progress-bar-animated"
                             role="progressbar"
                             style="width:0%; background:#198754; font-size:12px; font-weight:700;">0%</div>
                    </div>
                    <div id="importProgressError" class="alert alert-danger" style="display:none; margin-top:10px;"></div>
                </div>
            </div>
        @else
            <div class="alert alert-info mt-3">
                No new farmers to import. All rows are duplicates or invalid.
            </div>
            <a href="{{ route('admin.farmers.import.form') }}" class="btn btn-secondary">Upload Another File</a>
        @endif
    @endif
</div>

@endsection

@push('scripts')
<script>
    // Client-side file size guard
    document.getElementById('csvFile')?.addEventListener('change', function () {
        const maxBytes = 50 * 1024 * 1024;
        if (this.files[0] && this.files[0].size > maxBytes) {
            alert('File too large. Maximum is 50MB. Split the CSV and import in chunks.');
            this.value = '';
        }
    });

    // Upload spinner
    document.getElementById('importForm')?.addEventListener('submit', function () {
        const btn = document.getElementById('submitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Uploading & parsing… (this may take a minute)';
        }
    });

    // ─── Chunked import ─────────────────────────────────────
    // Only defined when we actually have a previewed batch.
    @isset($batch)
    let importing = false;

    async function startChunkedImport() {
        if (importing) return;
        importing = true;

        const total     = {{ (int) ($summary['new'] ?? 0) }};
        const batchUuid = '{{ $batch->uuid }}';

        const btn      = document.getElementById('commitBtn');
        const progress = document.getElementById('importProgress');
        const bar      = document.getElementById('importProgressBar');
        const text     = document.getElementById('importProgressText');
        const errBox   = document.getElementById('importProgressError');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Importing…';
        progress.style.display = 'block';
        errBox.style.display = 'none';

        let totalUsers = 0;
        let totalFarms = 0;
        let lastRemaining = null;
        let stuckCount = 0;
        let consecutiveErrors = 0;
        const MAX_ERRORS = 3;

        while (true) {
            try {
                const res = await fetch('{{ route('admin.farmers.import.commit-chunk') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ batch_uuid: batchUuid }),
                });

                if (!res.ok) {
                    throw new Error('Server returned ' + res.status);
                }

                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.error || 'Unknown error');
                }

                totalUsers += data.createdUsers || 0;
                totalFarms += data.createdFarms || 0;

                const done = Math.max(0, total - (data.remaining || 0));
                const pct  = total > 0 ? Math.min(100, Math.round((done / total) * 100)) : 100;
                bar.style.width = pct + '%';
                bar.textContent = pct + '%';
                text.textContent = totalUsers + ' farmers, ' + totalFarms + ' farms created — ' + data.remaining + ' remaining';

                consecutiveErrors = 0;

                if (data.remaining === 0) {
                    break;
                }

                if (data.remaining === lastRemaining) {
                    stuckCount++;
                    if (stuckCount >= 3) {
                        throw new Error('Import stalled — remaining count is not decreasing. Check laravel.log.');
                    }
                } else {
                    stuckCount = 0;
                }
                lastRemaining = data.remaining;

            } catch (err) {
                consecutiveErrors++;
                console.error('Import chunk failed:', err);

                if (consecutiveErrors >= MAX_ERRORS) {
                    errBox.style.display = 'block';
                    errBox.innerHTML =
                        '<strong>Import stopped.</strong> ' + err.message +
                        '<br>Created so far: <strong>' + totalUsers + ' farmers</strong>, ' +
                        totalFarms + ' farms.' +
                        '<br><small>Check <code>storage/logs/laravel.log</code>. ' +
                        'You can safely click Retry — already-created farmers are skipped.</small>';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Retry';
                    importing = false;
                    return;
                }

                await new Promise(r => setTimeout(r, 1000 * consecutiveErrors));
            }
        }

        text.innerHTML = '<span style="color:#198754; font-weight:700;">✓ Done — '
            + totalUsers + ' farmers, ' + totalFarms + ' farms</span>';
        bar.classList.remove('progress-bar-animated');
        bar.style.width = '100%';
        bar.textContent = '100%';

        setTimeout(() => {
            window.location.href = '{{ route('admin.farmers.credentials') }}';
        }, 1200);
    }
    @endisset
</script>
@endpush