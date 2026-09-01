@extends('layouts.app')

@section('title', 'Farm Records')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-left: 4px solid var(--green);">
        <div class="d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-2" style="color: var(--green);"></i>
            <strong>Success!</strong> {{ session('success') }}
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-left: 4px solid var(--red);">
        <div class="d-flex align-items-center">
            <i class="bi bi-x-circle-fill me-2" style="color: var(--red);"></i>
            <strong>Error!</strong> {{ session('error') }}
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="d-flex justify-content-end align-items-center mb-4">
    @if(auth()->user()->role !== 'farmer')
        <button type="button" class="btn btn-success" onclick="openFarmRecordModal()">
            <i class="bi bi-plus-circle"></i> Add Farm Record
        </button>
    @endif
    <span class="badge bg-secondary ms-2">{{ $farmRecords->count() }} Records</span>
</div>

<!-- Stats Cards -->
<div class="row g-2 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Total Records</div>
                <div class="value">{{ $farmRecords->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-clipboard-data-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Farms</div>
                <div class="value">{{ $farmRecords->pluck('farm_id')->unique()->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-geo-alt-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Varieties</div>
                <div class="value">{{ $farmRecords->pluck('rice_variety_id')->unique()->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-flower1"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Avg Fertilizer</div>
                <div class="value">{{ number_format($farmRecords->avg('fertilizer_kg_ha'), 1) }} kg/ha</div>
            </div>
            <div class="stat-icon"><i class="bi bi-droplet"></i></div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- FILTER CONTROLS (Barangay dropdown, Status buttons)          -->
<!-- ============================================================ -->
<div class="row g-2 mb-4">
    <div class="col-12">
        <div class="card-custom" style="padding: 12px 20px;">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <!-- Search -->
                <div style="flex: 1; min-width: 180px;">
                    <div class="input-group" style="border-radius: 12px; overflow: hidden; border: 1px solid var(--gray-200);">
                        <span class="input-group-text" style="background: var(--gray-50); border: none; color: var(--gray-400);">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="searchFarmRecord" class="form-control" placeholder="Search by farmer, variety, season..." style="border: none; background: var(--gray-50); font-size: 13px;">
                    </div>
                </div>

                <!-- Barangay Filter (dropdown) -->
                <div style="min-width: 160px;">
                    <select id="filterBarangay" class="form-select" style="border-radius: 12px; border: 1px solid var(--gray-200); background: var(--gray-50); font-size: 13px; padding: 0.45rem 2rem 0.45rem 1rem; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.7rem; cursor: pointer; appearance: none; width: auto; min-width: 140px;">
                        <option value="all">All Barangays</option>
                        @foreach($farmRecords->pluck('farm.barangay')->unique()->filter()->values() as $barangay)
                            <option value="{{ $barangay }}">{{ $barangay }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter (button group) -->
                <div style="min-width: 140px;">
                    <div class="bg-gray-100 p-1 rounded-xl d-flex" style="background: var(--gray-100); border-radius: 12px; padding: 4px; gap: 2px;">
                        <button class="filter-btn status-btn active" data-status="all" style="padding: 6px 16px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: white; color: var(--gray-900); box-shadow: 0 1px 2px rgba(0,0,0,0.05); white-space: nowrap;">All Statuses</button>
                        <button class="filter-btn status-btn" data-status="Harvested" style="padding: 6px 16px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: transparent; color: var(--gray-600); white-space: nowrap;">Harvested</button>
                        <button class="filter-btn status-btn" data-status="Vegetative" style="padding: 6px 16px; border-radius: 8px; border: none; font-weight: 600; font-size: 12px; background: transparent; color: var(--gray-600); white-space: nowrap;">Vegetative</button>
                    </div>
                </div>

                @if(auth()->user()->role === 'admin')
                    <div>
                        <span class="badge bg-secondary">{{ $farmRecords->count() }} Records</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Farm Records Table -->
<div class="card-custom">
    <div class="card-title"><i class="bi bi-table"></i> Farm Records</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Farm</th>
                    <th>Variety</th>
                    <th>Season</th>
                    <th>Fertilizer (kg/ha)</th>
                    <th>Historical Yield</th>
                    <th>Actual Yield</th>
                    <th>Expected Yield (t/ha)</th>
                    <th>Seeding Method</th>
                    <th>Status</th>
                    <th>Created</th>
                    @if(auth()->user()->role !== 'farmer')
                        <th style="white-space: nowrap; min-width: 140px;">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody id="farmRecordsBody">
                @forelse($farmRecords as $record)
                    @php
                        $expected = null;
                        if ($record->riceVariety && $record->seeding_method) {
                            $expected = $record->riceVariety->getYieldForMethod($record->seeding_method);
                        }
                        $searchData = strtolower(
                            ($record->farm->user->name ?? '') . ' ' .
                            ($record->riceVariety->name ?? '') . ' ' .
                            ($record->season ?? '')
                        );
                        $barangay = $record->farm->barangay ?? '';
                        $status = $record->status ?? '';
                    @endphp
                    <tr class="record-row" data-search="{{ $searchData }}" data-barangay="{{ $barangay }}" data-status="{{ $status }}">
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $record->farm->name ?? 'N/A' }}</strong>
                            <br><small class="text-muted">{{ $record->farm->barangay ?? '' }}</small>
                        </td>
                        <td>
                            <span class="variety-link" style="cursor: pointer; color: var(--green); text-decoration: underline;" onclick="viewVarietyDetails({{ $record->rice_variety_id }})">
                                {{ $record->riceVariety->name ?? 'N/A' }}
                            </span>
                        </td>
                        <td>{{ $record->season }}</td>
                        <td>{{ number_format($record->fertilizer_kg_ha, 2) }}</td>
                        <td>{{ $record->historical_yield_tons_ha ? number_format($record->historical_yield_tons_ha, 2) : 'N/A' }}</td>
                        <td>
                            @if($record->status === 'Harvested')
                                {{ $record->actual_yield_tons_ha ? number_format($record->actual_yield_tons_ha, 2) : 'N/A' }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($expected && $expected->avg !== null && $expected->max !== null)
                                <span class="fw-bold text-success">{{ number_format($expected->avg, 2) }}</span>
                                <small class="text-muted">(max: {{ number_format($expected->max, 2) }})</small>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>{{ $record->seeding_method ?? 'N/A' }}</td>
                        <td>
                            <span class="badge {{ $record->status_badge_class }}">
                                <i class="bi {{ $record->status_icon }}"></i>
                                {{ $record->status ?? 'N/A' }}
                            </span>
                        </td>
                        <td>{{ $record->created_at ? $record->created_at->format('M d, Y') : 'N/A' }}</td>
                        @if(auth()->user()->role !== 'farmer')
                            <td style="white-space: nowrap;">
                                <button type="button" class="btn btn-sm btn-secondary" onclick="viewFarmRecord({{ $record->id }})" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="editFarmRecord({{ $record->id }})" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="{{ route('admin.farm-records.destroy', $record->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this record?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->role !== 'farmer' ? 12 : 11 }}" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 28px;"></i>
                            <p class="mt-2 mb-0">No farm records yet.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Include the Add/Edit Modal -->
@if(auth()->user()->role !== 'farmer')
    @include('admin.farm-records.partials.modal')
@endif

<!-- ============================================================ -->
<!-- VIEW FARM RECORD DETAILS MODAL                                -->
<!-- ============================================================ -->
<div class="modal fade" id="viewFarmRecordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--green); color: white;">
                <h5 class="modal-title"><i class="bi bi-eye"></i> Farm Record Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewFarmRecordModalBody" style="overflow: hidden;">
                <div class="text-center py-4" id="viewModalLoading">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading record details...</p>
                </div>
                <div id="viewModalContent" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- RICE VARIETY DETAILS MODAL                                    -->
<!-- ============================================================ -->
<div class="modal fade" id="varietyDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--green); color: white;">
                <h5 class="modal-title"><i class="bi bi-flower1"></i> Rice Variety Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="varietyDetailModalBody" style="overflow: hidden;">
                <div class="text-center py-4" id="varietyDetailLoading">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading variety details...</p>
                </div>
                <div id="varietyDetailContent" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
@if(auth()->user()->role !== 'farmer')
<script>
    // ============================================================
    // FILTERING (Barangay dropdown + Status buttons)
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchFarmRecord');
        const barangaySelect = document.getElementById('filterBarangay');
        const statusBtns = document.querySelectorAll('.status-btn');
        const rows = document.querySelectorAll('#farmRecordsBody .record-row');

        function filterTable() {
            const search = searchInput.value.toLowerCase().trim();
            const barangay = barangaySelect.value;
            const activeStatus = document.querySelector('.status-btn.active');
            const status = activeStatus ? activeStatus.dataset.status : 'all';

            rows.forEach(row => {
                const searchData = row.dataset.search || '';
                const rowBarangay = row.dataset.barangay || '';
                const rowStatus = row.dataset.status || '';

                const matchesSearch = searchData.includes(search);
                const matchesBarangay = barangay === 'all' || rowBarangay === barangay;
                const matchesStatus = status === 'all' || rowStatus === status;

                row.style.display = (matchesSearch && matchesBarangay && matchesStatus) ? '' : 'none';
            });
        }

        searchInput.addEventListener('input', filterTable);
        barangaySelect.addEventListener('change', filterTable);

        statusBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                statusBtns.forEach(b => {
                    b.style.background = 'transparent';
                    b.style.color = 'var(--gray-600)';
                    b.classList.remove('active');
                });
                this.style.background = 'white';
                this.style.color = 'var(--gray-900)';
                this.classList.add('active');
                filterTable();
            });
        });

        document.querySelector('.status-btn[data-status="all"]')?.click();
    });

    // ============================================================
    // OPEN MODAL FOR ADD
    // ============================================================
    function openFarmRecordModal() {
        document.getElementById('modalTitle').textContent = 'Add Farm Record';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        fetch('{{ route("admin.farm-records.create") }}')
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalContent').style.display = 'block';
                document.getElementById('modalContent').innerHTML = html;
                
                const form = document.getElementById('farmRecordForm');
                if (form) {
                    form.addEventListener('submit', handleFormSubmit);
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

        var modal = new bootstrap.Modal(document.getElementById('farmRecordModal'));
        modal.show();
    }

    // ============================================================
    // OPEN MODAL FOR EDIT
    // ============================================================
    function editFarmRecord(id) {
        document.getElementById('modalTitle').textContent = 'Edit Farm Record';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        fetch('/admin/farm-records/' + id + '/edit')
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalContent').style.display = 'block';
                document.getElementById('modalContent').innerHTML = html;
                
                const form = document.getElementById('farmRecordForm');
                if (form) {
                    form.addEventListener('submit', handleFormSubmit);
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

        var modal = new bootstrap.Modal(document.getElementById('farmRecordModal'));
        modal.show();
    }

    // ============================================================
    // OPEN MODAL FOR VIEW (Farm Record)
    // ============================================================
    function viewFarmRecord(id) {
        document.getElementById('viewModalLoading').style.display = 'block';
        document.getElementById('viewModalContent').style.display = 'none';
        document.getElementById('viewModalContent').innerHTML = '';

        fetch('/admin/farm-records/' + id)
            .then(response => response.text())
            .then(html => {
                document.getElementById('viewModalLoading').style.display = 'none';
                document.getElementById('viewModalContent').style.display = 'block';
                document.getElementById('viewModalContent').innerHTML = html;
            })
            .catch(() => {
                document.getElementById('viewModalLoading').style.display = 'none';
                document.getElementById('viewModalContent').style.display = 'block';
                document.getElementById('viewModalContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> Failed to load record details. Please try again.
                    </div>
                `;
            });

        var modal = new bootstrap.Modal(document.getElementById('viewFarmRecordModal'));
        modal.show();
    }

    // ============================================================
    // VIEW RICE VARIETY DETAILS
    // ============================================================
    function viewVarietyDetails(id) {
        document.getElementById('varietyDetailLoading').style.display = 'block';
        document.getElementById('varietyDetailContent').style.display = 'none';
        document.getElementById('varietyDetailContent').innerHTML = '';

        fetch('/admin/rice-varieties/' + id + '/details')
            .then(response => response.text())
            .then(html => {
                document.getElementById('varietyDetailLoading').style.display = 'none';
                document.getElementById('varietyDetailContent').style.display = 'block';
                document.getElementById('varietyDetailContent').innerHTML = html;
            })
            .catch(() => {
                document.getElementById('varietyDetailLoading').style.display = 'none';
                document.getElementById('varietyDetailContent').style.display = 'block';
                document.getElementById('varietyDetailContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> Failed to load variety details. Please try again.
                    </div>
                `;
            });

        var modal = new bootstrap.Modal(document.getElementById('varietyDetailModal'));
        modal.show();
    }

    // ============================================================
    // HANDLE FORM SUBMISSION (Add/Edit)
    // ============================================================
    function handleFormSubmit(e) {
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
                var modal = bootstrap.Modal.getInstance(document.getElementById('farmRecordModal'));
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