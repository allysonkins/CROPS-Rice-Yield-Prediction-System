@extends('layouts.app')

@section('title', 'Farmer Credentials')

@section('content')

@php
    // Count farmers missing a PIN — queries the encrypted column,
    // not the 'pin' accessor (which cannot be used in WHERE clauses).
    $farmersWithoutPin = \App\Models\User::where('role', 'farmer')
        ->whereNull('pin_encrypted')
        ->count();
@endphp

<style>
    @media print {
        .sidebar, .topbar, .sidebar-backdrop, .no-print { display: none !important; }
        .main-wrapper { margin-left: 0 !important; }
        .main-content { padding: 0 !important; }
        .card-custom { box-shadow: none !important; border: none !important; padding: 0 !important; }
        body { background: white !important; }
    }
    .cred-slip {
        border: 1.5px dashed var(--brand-green);
        padding: 14px;
        border-radius: 10px;
        background: var(--brand-green-subtle);
    }
    .cred-slip .label {
        font-size: 10px;
        text-transform: uppercase;
        color: var(--brand-green-dark);
        font-weight: 700;
        letter-spacing: 1px;
    }
    .cred-slip .name {
        font-weight: 800;
        font-size: 15px;
        margin: 4px 0 8px;
        color: var(--slate-900);
    }
    .cred-slip .pin-box {
        font-family: monospace;
        font-size: 20px;
        font-weight: 800;
        letter-spacing: 4px;
        color: var(--brand-green);
    }
</style>

{{-- Flash: new PIN just generated --}}
@if(session('reset_pin'))
    <div class="alert-custom alert-custom-success mb-3 no-print">
        <i class="bi bi-key-fill alert-icon"></i>
        <div class="alert-content">
            <strong>New PIN for {{ session('reset_pin_farmer') }}:</strong>
            <span style="font-family: monospace; font-size: 20px; font-weight: 800; letter-spacing: 3px; margin-left: 12px; color: var(--brand-green-dark);">
                {{ session('reset_pin') }}
            </span>
            <div style="font-size: 12px; margin-top: 4px; color: var(--slate-600);">
                Write this down or print this page now. You can always view it again here.
            </div>
        </div>
    </div>
@endif

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 no-print">
    <div>
        <a href="{{ route('admin.farmers.index') }}" class="text-decoration-none" style="font-size: 13px; color: var(--slate-500);">
            <i class="bi bi-arrow-left"></i> Back to Farmers
        </a>
        <h5 style="font-weight: 800; color: var(--slate-900); margin: 8px 0 0; letter-spacing: -0.4px;">
            <i class="bi bi-key-fill" style="color: var(--brand-green);"></i>
            Farmer Login Credentials
        </h5>
        <p style="font-size: 13px; color: var(--slate-500); margin: 4px 0 0;">
            All farmers with their current PIN. Print or reprint any time.
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if($farmersWithoutPin > 0)
            <button type="button" class="btn btn-warning" onclick="openGenerateAllPinsModal()">
                <i class="bi bi-key-fill"></i>
                Generate {{ number_format($farmersWithoutPin) }} Missing PIN{{ $farmersWithoutPin !== 1 ? 's' : '' }}
            </button>
        @endif
        <button onclick="window.print()" class="btn btn-success">
            <i class="bi bi-printer"></i> Print All
        </button>
    </div>
</div>

{{-- Filters --}}
<div class="card-custom mb-3 no-print">
    <div class="d-flex flex-wrap align-items-center gap-3">
        <div style="flex: 1; min-width: 200px;">
            <input type="text" id="credSearch" class="form-control"
                   placeholder="Search by name, phone, or RSBSA number..."
                   style="font-size: 13px;">
        </div>
        <div>
            <a href="{{ route('admin.farmers.credentials', ['filter' => 'with_pin']) }}"
               class="btn btn-sm {{ request('filter') === 'with_pin' ? 'btn-success' : 'btn-outline-success' }}">
                <i class="bi bi-key"></i> With PIN stored
            </a>
            <a href="{{ route('admin.farmers.credentials', ['filter' => 'never_logged_in']) }}"
               class="btn btn-sm {{ request('filter') === 'never_logged_in' ? 'btn-success' : 'btn-outline-success' }}">
                <i class="bi bi-hourglass"></i> Never logged in
            </a>
            @if(request('filter'))
                <a href="{{ route('admin.farmers.credentials') }}" class="btn btn-sm btn-secondary">
                    <i class="bi bi-x-circle"></i> Clear
                </a>
            @endif
        </div>
        <span class="badge-status"
              style="background: var(--brand-green-light); color: var(--brand-green-dark); border-color: #c7e6d2;">
            <span class="dot" style="background: var(--brand-green);"></span>
            {{ $farmers->count() }} Farmers
        </span>
    </div>
</div>

{{-- Credentials Grid --}}
@if($farmers->isEmpty())
    <div class="card-custom text-center py-5">
        <i class="bi bi-inbox" style="font-size: 48px; color: var(--slate-300);"></i>
        <h6 class="mt-3 mb-1" style="color: var(--slate-700); font-weight: 700;">No farmers found</h6>
        <p style="font-size: 13px; color: var(--slate-500); margin: 0;">
            @if(request('filter') === 'with_pin')
                No farmers have a stored PIN yet. Import farmers via RSBSA to generate PINs.
            @else
                No farmers registered yet.
            @endif
        </p>
    </div>
@else
    <div class="row g-2" id="credentialsGrid">
        @foreach($farmers as $farmer)
            @php
                $pin = $farmer->pin;
            @endphp
            <div class="col-md-4 col-sm-6 cred-card"
                 data-search="{{ strtolower($farmer->name . ' ' . $farmer->phone . ' ' . $farmer->rsbsa_number) }}">
                <div class="cred-slip h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="label">CROPS Login Slip</div>
                        @if(!$pin)
                            <span class="badge-status" style="background: var(--brand-gold-light); color: #92400e; border-color: #fde68a; font-size: 10px;">
                                <span class="dot" style="background: var(--brand-gold);"></span>
                                No PIN
                            </span>
                        @endif
                    </div>

                    <div class="name">{{ $farmer->name }}</div>

                    <div style="font-size: 12px; color: var(--slate-600); line-height: 1.7;">
                        <div>RSBSA: <strong>{{ $farmer->rsbsa_number ?: '—' }}</strong></div>
                        <div>Barangay: <strong>{{ $farmer->barangay ?: '—' }}</strong></div>
                        <div>Phone: <strong style="font-family: monospace;">{{ $farmer->phone ?: '—' }}</strong></div>
                    </div>

                    <hr style="margin: 10px 0; border-color: #c7e6d2;">

                    @if($pin)
                        <div style="font-size: 11px; text-transform: uppercase; color: var(--brand-green-dark); font-weight: 700; letter-spacing: 0.5px;">
                            PIN
                        </div>
                        <div class="pin-box">{{ $pin }}</div>
                        @if($farmer->pin_generated_at)
                            <div style="font-size: 10px; color: var(--slate-400); margin-top: 4px;">
                                Set {{ $farmer->pin_generated_at->format('M d, Y') }}
                            </div>
                        @endif
                    @else
                        <div style="font-size: 11px; color: var(--slate-500);">
                            PIN not stored. Farmer created manually or has changed their own password.
                        </div>
                        <div style="margin-top: 8px;">
                            <form action="{{ route('admin.farmers.reset-pin', $farmer->id) }}" method="POST" class="no-print"
                                  onsubmit="return confirm('Generate a new PIN for {{ $farmer->name }}? The old password will stop working.')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-success" style="font-size: 11px;">
                                    <i class="bi bi-arrow-clockwise"></i> Generate PIN
                                </button>
                            </form>
                        </div>
                    @endif

                    {{-- Actions (hidden on print) --}}
                    <div class="mt-3 pt-2 no-print" style="border-top: 1px dashed #c7e6d2; display: flex; gap: 6px;">
                        <a href="{{ route('admin.farmers.slip', $farmer->id) }}"
                           class="btn btn-sm btn-outline-secondary" style="font-size: 11px;" target="_blank">
                            <i class="bi bi-printer"></i> Slip
                        </a>
                        @if($pin)
                            <form action="{{ route('admin.farmers.reset-pin', $farmer->id) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Reset PIN for {{ $farmer->name }}? A new PIN will replace the old one.')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size: 11px;">
                                    <i class="bi bi-arrow-clockwise"></i> Reset PIN
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- Generate All Missing PINs — Confirmation Modal --}}
@if($farmersWithoutPin > 0)
<div class="modal fade" id="generateAllPinsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--brand-gold); color: #5a4a00;">
                <h5 class="modal-title">
                    <i class="bi bi-key-fill"></i> Generate All Missing PINs
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div style="width: 48px; height: 48px; border-radius: 12px; background: var(--brand-gold-light); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="bi bi-exclamation-triangle-fill" style="font-size: 24px; color: var(--brand-gold-dark);"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">
                            Generate {{ number_format($farmersWithoutPin) }} PINs?
                        </h6>
                        <p class="text-muted small mb-0">
                            The system will assign a new 6-digit PIN to every farmer that currently has none.
                            This runs in small batches — you'll see the progress below.
                        </p>
                    </div>
                </div>

                <div style="background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 10px; padding: 12px 16px;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-info-circle" style="color: var(--brand-green); margin-top: 2px;"></i>
                        <div style="font-size: 12px;">
                            <strong>Already-set PINs are left alone.</strong><br>
                            <span class="text-muted">Only farmers with an empty PIN field will be updated.</span>
                        </div>
                    </div>
                </div>

                {{-- Progress (hidden until generation starts) --}}
                <div id="generateAllPinsProgress" style="display: none; margin-top: 16px;">
                    <div class="d-flex justify-content-between mb-1" style="font-size: 12px;">
                        <span class="fw-semibold" style="color: var(--slate-700);">Progress</span>
                        <span id="generateAllPinsText" class="text-muted">Starting...</span>
                    </div>
                    <div class="progress" style="height: 22px; border-radius: 8px; background: var(--gray-100);">
                        <div id="generateAllPinsBar"
                             class="progress-bar progress-bar-striped progress-bar-animated"
                             role="progressbar"
                             style="width: 0%; background: var(--brand-green); font-size: 12px; font-weight: 700;">
                            0%
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="generateAllPinsCancel">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
                <button type="button" class="btn btn-warning" id="confirmGenerateAllPinsBtn" onclick="startGenerateAllPins()">
                    <i class="bi bi-key-fill"></i> Yes, Generate All
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
    // ─── Live search filter ─────────────────────────────────
    document.getElementById('credSearch')?.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('.cred-card').forEach(card => {
            const haystack = card.dataset.search || '';
            card.style.display = haystack.includes(q) ? '' : 'none';
        });
    });

    // ─── Chunked PIN generation ─────────────────────────────
    let generatingAllPins = false;

    function openGenerateAllPinsModal() {
        new bootstrap.Modal(document.getElementById('generateAllPinsModal')).show();
    }

    async function startGenerateAllPins() {
        if (generatingAllPins) return;
        generatingAllPins = true;

        const total     = {{ $farmersWithoutPin }};
        const btn       = document.getElementById('confirmGenerateAllPinsBtn');
        const cancelBtn = document.getElementById('generateAllPinsCancel');
        const progress  = document.getElementById('generateAllPinsProgress');
        const bar       = document.getElementById('generateAllPinsBar');
        const text      = document.getElementById('generateAllPinsText');

        // Lock the UI
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Working...';
        cancelBtn.disabled = true;

        // Show progress
        progress.style.display = 'block';
        text.textContent = `Generated 0 of ${total}...`;

        let generatedSoFar = 0;
        let keepGoing = true;
        let errorCount = 0;

        while (keepGoing) {
            try {
                const res = await fetch('{{ route("admin.farmers.generate-pins-chunk") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                if (!res.ok) {
                    throw new Error('Server returned ' + res.status);
                }

                const data = await res.json();

                if (!data.success) {
                    throw new Error(data.error || 'Unknown error');
                }

                generatedSoFar += data.generated;

                const pct = total > 0 ? Math.min(100, Math.round((generatedSoFar / total) * 100)) : 100;
                bar.style.width = pct + '%';
                bar.textContent = pct + '%';
                text.textContent = `Generated ${generatedSoFar} of ${total}...`;

                // Stop when no more work
                if (data.remaining === 0 || data.generated === 0) {
                    keepGoing = false;
                }
            } catch (err) {
                errorCount++;
                console.error('PIN generation chunk failed:', err);

                // If it fails twice in a row, stop and tell the admin
                if (errorCount >= 2) {
                    text.innerHTML =
                        '<span class="text-danger">' +
                        'Stopped after ' + generatedSoFar + ' PINs. Error: ' + err.message +
                        '</span>';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Retry';
                    cancelBtn.disabled = false;
                    generatingAllPins = false;
                    return;
                }
            }
        }

        // Done
        text.innerHTML = `<span style="color: var(--brand-green-dark); font-weight: 700;">
            ✓ Done — ${generatedSoFar} PINs generated
        </span>`;
        bar.classList.remove('progress-bar-animated');
        bar.style.width = '100%';
        bar.textContent = '100%';

        // Reload so all the new PINs show up
        setTimeout(() => location.reload(), 900);
    }
</script>
@endpush