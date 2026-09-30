@extends('layouts.app')

@section('title', 'Pending Farmer Verifications')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="{{ route('admin.farmers.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Farmers
    </a>
    <span class="badge bg-warning text-dark">
        {{ $pending->count() }} Pending
    </span>
</div>

<div class="card-custom">
    <div class="card-title">
        <i class="bi bi-hourglass-split"></i> Farmers Awaiting CAO Verification
    </div>

    @if($pending->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-check-circle" style="font-size: 42px; color: var(--green);"></i>
            <p class="mt-3 mb-0">All farmers are verified. Nothing to review.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>RSBSA #</th>
                        <th>Barangay</th>
                        <th>Registered</th>
                        <th style="min-width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($pending as $farmer)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $farmer->name }}</strong></td>
                        <td style="font-family: monospace; font-size: 12px;">{{ $farmer->phone ?? '—' }}</td>
                        <td>
                            @if($farmer->rsbsa_number)
                                <span class="badge bg-light text-dark" style="font-size: 11px;">{{ $farmer->rsbsa_number }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $farmer->barangay ?? '—' }}</td>
                        <td>
                            <small class="text-muted">
                                {{ $farmer->created_at->format('M d, Y') }}<br>
                                {{ $farmer->created_at->diffForHumans() }}
                            </small>
                        </td>
                        <td>
                            <form action="{{ route('admin.farmers.verify', $farmer->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="bi bi-patch-check-fill"></i> Verify
                                </button>
                            </form>
                            <form action="{{ route('admin.farmers.reject', $farmer->id) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Reject and delete this registration? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="bi bi-x-circle"></i> Reject
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection