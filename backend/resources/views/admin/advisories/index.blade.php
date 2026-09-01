@extends('layouts.app')

@section('title', 'Advisories')

@section('content')
<div class="d-flex justify-content-end align-items-center mb-4">
    @if(auth()->user()->role !== 'farmer')
        <button type="button" class="btn btn-success" onclick="openAdvisoryModal()">
            <i class="bi bi-plus-circle"></i> New Advisory
        </button>
    @endif
    <span class="badge bg-secondary ms-2">{{ $advisories->count() }} Advisories</span>
</div>

<!-- Stats Cards — Hidden for Farmers -->
@if(auth()->user()->role !== 'farmer')
<div class="row g-2 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Total</div>
                <div class="value">{{ $advisories->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-megaphone"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Active</div>
                <div class="value">{{ $advisories->where('is_active', true)->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-check-circle-fill" style="color: var(--green);"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Expired</div>
                <div class="value">{{ $advisories->where('is_active', false)->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-clock-fill" style="color: var(--red);"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Target: Farmers</div>
                <div class="value">{{ $advisories->whereIn('target_audience', ['farmers', 'all'])->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
        </div>
    </div>
</div>
@endif

<!-- Advisories Cards -->
<div class="row g-3">
    @forelse($advisories as $advisory)
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card-custom" style="height: 100%; border-top: 4px solid {{ $advisory->is_active ? 'var(--green)' : 'var(--red)' }};">
                <div class="d-flex justify-content-between align-items-start">
                    <h6 class="mb-1"><strong>{{ $advisory->title }}</strong></h6>
                    <span class="badge {{ $advisory->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $advisory->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="mb-2">
                    <span class="badge bg-light text-muted" style="font-size: 10px;">
                        <i class="bi bi-tag"></i> {{ ucfirst($advisory->target_audience) }}
                    </span>
                    <span class="badge bg-light text-muted" style="font-size: 10px;">
                        <i class="bi bi-person"></i> {{ $advisory->user->name ?? 'Unknown' }}
                    </span>
                </div>
                <p class="text-muted small mb-2">{{ Str::limit($advisory->content, 100) }}</p>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <small class="text-muted">
                        <i class="bi bi-clock"></i> {{ $advisory->created_at->diffForHumans() }}
                        @if($advisory->expiry_date)
                            <br><i class="bi bi-calendar"></i> Expires: {{ $advisory->expiry_date->format('M d, Y') }}
                        @endif
                    </small>
                    @if(auth()->user()->role !== 'farmer')
                        <div>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="editAdvisory({{ $advisory->id }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="{{ route('admin.advisories.destroy', $advisory->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this advisory?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card-custom text-center py-5">
                <i class="bi bi-inbox" style="font-size: 48px; color: var(--gray-400);"></i>
                <p class="mt-3 mb-0">
                    @if(auth()->user()->role === 'farmer')
                        No advisories available for you at this time.
                    @else
                        No advisories yet.
                    @endif
                </p>
                @if(auth()->user()->role === 'farmer')
                    <small>Check back later for updates from the City Agriculture Office.</small>
                @else
                    <button type="button" class="btn btn-sm btn-primary mt-2" onclick="openAdvisoryModal()">
                        <i class="bi bi-plus-circle"></i> Create First Advisory
                    </button>
                @endif
            </div>
        </div>
    @endforelse
</div>

<!-- Include the Modal (Only for Admin & Staff) -->
@if(auth()->user()->role !== 'farmer')
    @include('admin.advisories.partials.modal')
@endif

@endsection

@push('scripts')
@if(auth()->user()->role !== 'farmer')
<script>
    // ============================================================
    // OPEN MODAL FOR ADD
    // ============================================================
    function openAdvisoryModal() {
        document.getElementById('modalTitle').textContent = 'New Advisory';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        fetch('{{ route("admin.advisories.create") }}')
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalContent').style.display = 'block';
                document.getElementById('modalContent').innerHTML = html;
                
                const form = document.getElementById('advisoryForm');
                if (form) {
                    form.addEventListener('submit', handleAdvisoryFormSubmit);
                }
            })
            .catch(() => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalContent').style.display = 'block';
                document.getElementById('modalContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> Failed to load form. Please try again.
                    </div>
                `;
            });

        var modal = new bootstrap.Modal(document.getElementById('advisoryModal'));
        modal.show();
    }

    // ============================================================
    // OPEN MODAL FOR EDIT
    // ============================================================
    function editAdvisory(id) {
        document.getElementById('modalTitle').textContent = 'Edit Advisory';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        fetch('/admin/advisories/' + id + '/edit')
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalContent').style.display = 'block';
                document.getElementById('modalContent').innerHTML = html;
                
                const form = document.getElementById('advisoryForm');
                if (form) {
                    form.addEventListener('submit', handleAdvisoryFormSubmit);
                }
            })
            .catch(() => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalContent').style.display = 'block';
                document.getElementById('modalContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> Failed to load form. Please try again.
                    </div>
                `;
            });

        var modal = new bootstrap.Modal(document.getElementById('advisoryModal'));
        modal.show();
    }

    // ============================================================
    // HANDLE FORM SUBMISSION
    // ============================================================
    function handleAdvisoryFormSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Saving...';
        submitBtn.disabled = true;

        const existingErrors = form.querySelector('#formErrors');
        if (existingErrors) existingErrors.remove();

        fetch(form.action, {
            method: form.method || 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                var modal = bootstrap.Modal.getInstance(document.getElementById('advisoryModal'));
                modal.hide();
                location.reload();
            } else {
                const errorDiv = document.createElement('div');
                errorDiv.id = 'formErrors';
                errorDiv.className = 'alert alert-danger mt-3';
                let errorMsg = data.error || 'An error occurred.';
                if (data.errors) {
                    errorMsg = Object.values(data.errors).flat().join('<br>');
                }
                errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + errorMsg;
                form.prepend(errorDiv);
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            alert('An error occurred. Please try again.');
        });
    }
</script>
@endif
@endpush