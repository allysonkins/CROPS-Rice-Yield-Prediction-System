@extends('layouts.app')

@section('title', 'Farmers List')

@section('content')
    <style>
        .farmers-list-scroll {
            max-height: 560px;
            overflow-y: auto;
        }
        .farmers-list-scroll thead th {
            position: sticky;
            top: 0;
            background: white;
            z-index: 2;
            box-shadow: inset 0 -1px 0 var(--gray-200);
        }
        .farmers-filter-btn {
            padding: 5px 12px;
            border-radius: 7px;
            border: none;
            font-weight: 600;
            font-size: 11px;
            background: transparent;
            color: var(--gray-600);
            white-space: nowrap;
            transition: all 0.15s;
        }
        .farmers-filter-btn.active {
            background: white;
            color: var(--gray-900);
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        #farmersPaginationList button:not(:disabled):hover {
            background: var(--brand-green-light) !important;
            border-color: var(--brand-green) !important;
            color: var(--brand-green-dark) !important;
        }
    </style>

<!-- ============================================================ -->
<!-- HEADER -->
<!-- ============================================================ -->
<div class="page-header">
    <div>
        <h4 class="page-header-title">
            <i class="bi bi-people-fill"></i> Farmers Directory
        </h4>
        <p class="page-header-desc">
            Registered farmers in Santiago City, RSBSA verification status, and linked farm records.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        @if(auth()->user()->role === 'admin')
            @php
                $pendingCount = \App\Models\User::where('role', 'farmer')
                    ->whereNull('verified_by_cao_at')
                    ->count();
            @endphp

            <a href="{{ route('admin.farmers.pending') }}" class="btn btn-outline-warning">
                <i class="bi bi-hourglass-split"></i> Pending
                @if($pendingCount > 0)
                    <span class="badge bg-danger ms-1">{{ $pendingCount }}</span>
                @endif
            </a>

            <a href="{{ route('admin.farmers.credentials') }}" class="btn btn-outline-success">
                <i class="bi bi-key-fill"></i> Credentials
            </a>

            <a href="{{ route('admin.farmers.import.form') }}" class="btn btn-outline-success">
                <i class="bi bi-upload"></i> Bulk Import (RSBSA)
            </a>

            <button type="button" class="btn btn-success" onclick="openFarmerModal()">
                <i class="bi bi-plus-circle"></i> Add Farmer
            </button>
        @endif

        <span class="badge-count">{{ $farmers->count() }} Farmers</span>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-2 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Total Farmers</div>
                <div class="value">{{ $farmers->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">With Phone</div>
                <div class="value">{{ $farmers->whereNotNull('phone')->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-telephone-fill" style="color: var(--green);"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">With RSBSA #</div>
                <div class="value">{{ $farmers->whereNotNull('rsbsa_number')->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-card-checklist" style="color: var(--gold);"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">With Farms</div>
                <div class="value">{{ $farmers->filter(fn($f) => $f->farms->count() > 0)->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-geo-alt-fill"></i></div>
        </div>
    </div>
</div>

<!-- Farmers Card -->
<div class="card-custom">

    <div class="card-title d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>
            <i class="bi bi-table"></i> Farmers
            <span class="badge bg-light text-muted ms-1" id="farmersVisibleCount"
                  style="font-weight: 400; font-size: 10px;">
                {{ $farmers->count() }} total
            </span>
        </span>
    </div>

    {{-- FILTER BAR --}}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">

        {{-- SEARCH --}}
        <div style="flex: 1; min-width: 200px;">
            <div class="input-group" style="border-radius: 10px; overflow: hidden; border: 1px solid var(--gray-200);">
                <span class="input-group-text" style="background: var(--gray-50); border: none; color: var(--gray-400); padding: 0.35rem 0.7rem;">
                    <i class="bi bi-search" style="font-size: 12px;"></i>
                </span>
                <input type="text" id="farmersSearch" class="form-control"
                       placeholder="Search name, RSBSA #, phone, or barangay..."
                       style="border: none; background: var(--gray-50); font-size: 12px; padding: 0.4rem 0.7rem;">
            </div>
        </div>

        {{-- BARANGAY --}}
        <select id="farmersFilterBarangay" class="form-select"
                style="border-radius: 10px; border: 1px solid var(--gray-200); background: var(--gray-50); font-size: 12px; padding: 0.4rem 2rem 0.4rem 0.85rem; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.65rem center; background-size: 0.65rem; appearance: none; cursor: pointer; width: auto; min-width: 160px;">
            <option value="all">All Barangays</option>
            @foreach($farmers->pluck('barangay')->unique()->filter()->sort()->values() as $barangay)
                <option value="{{ $barangay }}">{{ $barangay }}</option>
            @endforeach
        </select>

        {{-- CONTACT INFO PILLS --}}
        <div class="d-flex" style="background: var(--gray-100); border-radius: 10px; padding: 3px; gap: 2px;">
            <button class="farmers-filter-btn active" data-contact="all">All</button>
            <button class="farmers-filter-btn" data-contact="complete">Complete</button>
            <button class="farmers-filter-btn" data-contact="missing">Missing</button>
        </div>

        {{-- FARMS PILLS --}}
        <div class="d-flex" style="background: var(--gray-100); border-radius: 10px; padding: 3px; gap: 2px;">
            <button class="farmers-filter-btn active" data-farms="all">All</button>
            <button class="farmers-filter-btn" data-farms="with">Has Farms</button>
            <button class="farmers-filter-btn" data-farms="without">No Farms</button>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="table-responsive farmers-list-scroll">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>RSBSA #</th>
                    <th>Phone</th>
                    <th>Barangay</th>
                    <th>Farms</th>
                    <th>Joined</th>
                    @if(auth()->user()->role === 'admin')
                        <th style="width: 90px;">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody id="farmersListBody">
                @forelse($farmers as $farmer)
                    @php
                        $hasPhone = !empty($farmer->phone);
                        $hasRsbsa = !empty($farmer->rsbsa_number);
                        $farmCount = $farmer->farms->count();

                        // "Complete" = has both phone and RSBSA
                        $contactState = ($hasPhone && $hasRsbsa) ? 'complete' : 'missing';
                        $farmsState   = $farmCount > 0 ? 'with' : 'without';

                        $searchText = strtolower(
                            ($farmer->name ?? '') . ' ' .
                            ($farmer->rsbsa_number ?? '') . ' ' .
                            ($farmer->phone ?? '') . ' ' .
                            ($farmer->barangay ?? '')
                        );
                    @endphp
                    <tr class="farmers-row"
                        data-search="{{ $searchText }}"
                        data-barangay="{{ $farmer->barangay ?? '' }}"
                        data-contact="{{ $contactState }}"
                        data-farms="{{ $farmsState }}">
                        <td class="farmers-row-index">{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $farmer->name }}</strong>
                            @if(!$hasPhone && !$hasRsbsa)
                                <br>
                                <span class="badge bg-warning text-dark" style="font-size: 10px;">
                                    <i class="bi bi-exclamation-triangle"></i> No contact info
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($hasRsbsa)
                                <span class="badge bg-light text-dark" style="font-weight: 600; font-size: 11px;">
                                    {{ $farmer->rsbsa_number }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($hasPhone)
                                <span style="font-family: monospace; font-size: 12px;">
                                    {{ $farmer->phone }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $farmer->barangay ?? '—' }}</td>
                        <td>
                            @if($farmCount > 0)
                                <span class="badge bg-success">{{ $farmCount }}</span>
                            @else
                                <span class="badge bg-secondary">0</span>
                            @endif
                        </td>
                        <td>
                            <small class="text-muted">
                                {{ $farmer->created_at ? $farmer->created_at->format('M d, Y') : '—' }}
                            </small>
                        </td>
                        @if(auth()->user()->role === 'admin')
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <button type="button" class="btn-action btn-action-edit" onclick="editFarmer({{ $farmer->id }})" title="Edit Farmer">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    @if($farmer->id !== auth()->id())
                                        <form action="{{ route('admin.farmers.destroy', $farmer->id) }}" method="POST" class="d-inline m-0" onsubmit="return confirm('Delete this farmer?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-action btn-action-danger" title="Delete Farmer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <button class="btn-action" disabled title="You cannot delete yourself">
                                            <i class="bi bi-lock"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->role === 'admin' ? 8 : 7 }}" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 28px;"></i>
                            <p class="mt-2 mb-0">No farmers registered yet.</p>
                            @if(auth()->user()->role === 'admin')
                                <div class="mt-2 d-flex gap-2 justify-content-center">
                                    <a href="{{ route('admin.farmers.import.form') }}" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-upload"></i> Bulk Import from RSBSA
                                    </a>
                                    <button type="button" class="btn btn-sm btn-success" onclick="openFarmerModal()">
                                        <i class="bi bi-plus-circle"></i> Add Farmer Manually
                                    </button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforelse

                <tr id="farmersNoResults" style="display: none;">
                    <td colspan="{{ auth()->user()->role === 'admin' ? 8 : 7 }}" class="text-center py-4 text-muted">
                        <i class="bi bi-search" style="font-size: 28px;"></i>
                        <p class="mt-2 mb-0">No farmers match your filters.</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    <div id="farmersPaginationBar" class="d-none"
         style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding-top: 12px; margin-top: 8px; border-top: 1px solid var(--gray-200);">

        <div class="d-flex align-items-center gap-2" style="font-size: 11px; color: var(--gray-500);">
            <span>Show</span>
            <select id="farmersPerPage" class="form-select form-select-sm"
                    style="width: auto; border-radius: 7px; border: 1px solid var(--gray-200); font-size: 11px; padding: 2px 22px 2px 8px; appearance: none; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 6px center; background-size: 9px; cursor: pointer;">
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span>entries</span>
            <span id="farmersPaginationInfo" style="margin-left: 4px;"></span>
        </div>

        <nav aria-label="Farmer pagination">
            <ul id="farmersPaginationList" class="pagination mb-0"
                style="display: flex; align-items: center; gap: 3px; list-style: none; padding: 0; margin: 0;"></ul>
        </nav>
    </div>

</div>

<!-- Include the Farmer Modal -->
@if(auth()->user()->role === 'admin')
    @include('admin.farmers.partials.modal')
@endif

@endsection

@if(auth()->user()->role === 'admin')
@push('scripts')
<script>
    // ============================================================
    // OPEN MODAL FOR ADD
    // ============================================================
    function openFarmerModal() {
        document.getElementById('modalTitle').textContent = 'Add Farmer';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        fetch('{{ route("admin.farmers.create") }}')
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalContent').style.display = 'block';
                document.getElementById('modalContent').innerHTML = html;

                const form = document.getElementById('farmerForm');
                if (form) {
                    form.addEventListener('submit', handleFarmerFormSubmit);
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

        var modal = new bootstrap.Modal(document.getElementById('farmerModal'));
        modal.show();
    }

    // ============================================================
    // OPEN MODAL FOR EDIT
    // ============================================================
    function editFarmer(id) {
        document.getElementById('modalTitle').textContent = 'Edit Farmer';
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalContent').style.display = 'none';
        document.getElementById('modalContent').innerHTML = '';

        fetch('/admin/farmers/' + id + '/edit')
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalContent').style.display = 'block';
                document.getElementById('modalContent').innerHTML = html;

                const form = document.getElementById('farmerForm');
                if (form) {
                    form.addEventListener('submit', handleFarmerFormSubmit);
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

        var modal = new bootstrap.Modal(document.getElementById('farmerModal'));
        modal.show();
    }

    // ============================================================
    // HANDLE FORM SUBMISSION
    // ============================================================
    function handleFarmerFormSubmit(e) {
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
                var modal = bootstrap.Modal.getInstance(document.getElementById('farmerModal'));
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

    // ============================================================
    // FARMERS LIST — FILTERING + PAGINATION
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput    = document.getElementById('farmersSearch');
        const barangaySelect = document.getElementById('farmersFilterBarangay');
        const contactBtns    = document.querySelectorAll('.farmers-filter-btn[data-contact]');
        const farmsBtns      = document.querySelectorAll('.farmers-filter-btn[data-farms]');
        const allRows        = Array.from(document.querySelectorAll('#farmersListBody .farmers-row'));
        const noResults      = document.getElementById('farmersNoResults');
        const visibleCount   = document.getElementById('farmersVisibleCount');
        const paginationBar  = document.getElementById('farmersPaginationBar');
        const paginationList = document.getElementById('farmersPaginationList');
        const paginationInfo = document.getElementById('farmersPaginationInfo');
        const perPageSelect  = document.getElementById('farmersPerPage');

        if (!searchInput || allRows.length === 0) return;

        let currentPage = 1;
        let perPage = parseInt(perPageSelect?.value || '25', 10);

        function getMatchingRows() {
            const search        = searchInput.value.toLowerCase().trim();
            const barangay      = barangaySelect.value;
            const activeC       = document.querySelector('.farmers-filter-btn[data-contact].active');
            const contactFilter = activeC ? activeC.dataset.contact : 'all';
            const activeF       = document.querySelector('.farmers-filter-btn[data-farms].active');
            const farmsFilter   = activeF ? activeF.dataset.farms : 'all';

            return allRows.filter(row => {
                const sd = row.dataset.search || '';
                const rb = row.dataset.barangay || '';
                const rc = row.dataset.contact || '';
                const rf = row.dataset.farms || '';

                const matchesSearch   = sd.includes(search);
                const matchesBarangay = (barangay === 'all' || rb === barangay);
                const matchesContact  = (contactFilter === 'all' || rc === contactFilter);
                const matchesFarms    = (farmsFilter === 'all' || rf === farmsFilter);

                return matchesSearch && matchesBarangay && matchesContact && matchesFarms;
            });
        }

        function renderPagination() {
            const matching = getMatchingRows();
            const total = matching.length;
            const totalPages = Math.max(1, Math.ceil(total / perPage));

            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            allRows.forEach(r => r.style.display = 'none');

            const start = (currentPage - 1) * perPage;
            const end = start + perPage;
            matching.slice(start, end).forEach((row, i) => {
                row.style.display = '';
                const c = row.querySelector('.farmers-row-index');
                if (c) c.textContent = start + i + 1;
            });

            if (noResults) noResults.style.display = (total === 0 && allRows.length > 0) ? '' : 'none';
            if (visibleCount) visibleCount.textContent = total + ' visible';

            if (paginationInfo) {
                paginationInfo.textContent = total === 0
                    ? '— no entries'
                    : '— ' + (start + 1) + ' to ' + Math.min(end, total) + ' of ' + total;
            }

            if (paginationBar) paginationBar.classList.toggle('d-none', totalPages <= 1);
            if (!paginationList) return;

            paginationList.innerHTML = '';
            const btnStyle = 'display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 8px;border-radius:7px;border:1px solid var(--gray-200);background:#fff;color:var(--gray-700);font-size:12px;font-weight:600;cursor:pointer;';
            const activeStyle = 'display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 8px;border-radius:7px;border:1px solid var(--brand-green);background:var(--brand-green);color:#fff;font-size:12px;font-weight:700;';
            const disabledStyle = 'display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 8px;border-radius:7px;border:1px solid var(--gray-200);background:var(--gray-50);color:var(--gray-300);font-size:12px;font-weight:600;cursor:not-allowed;';

            const addBtn = (label, page, opts = {}) => {
                const li = document.createElement('li');
                const a = document.createElement('button');
                a.type = 'button';
                a.innerHTML = label;
                a.setAttribute('style', opts.active ? activeStyle : (opts.disabled ? disabledStyle : btnStyle));
                if (!opts.active && !opts.disabled && page !== null) {
                    a.addEventListener('click', () => {
                        currentPage = page;
                        renderPagination();
                    });
                } else a.disabled = true;
                li.appendChild(a);
                paginationList.appendChild(li);
            };

            addBtn('<i class="bi bi-chevron-left"></i>', currentPage - 1, { disabled: currentPage === 1 });

            const pages = [];
            if (totalPages <= 7) {
                for (let i = 1; i <= totalPages; i++) pages.push(i);
            } else {
                pages.push(1);
                if (currentPage > 3) pages.push('...');
                const s = Math.max(2, currentPage - 1);
                const e = Math.min(totalPages - 1, currentPage + 1);
                for (let i = s; i <= e; i++) pages.push(i);
                if (currentPage < totalPages - 2) pages.push('...');
                pages.push(totalPages);
            }

            pages.forEach(p => {
                if (p === '...') {
                    const li = document.createElement('li');
                    const span = document.createElement('span');
                    span.textContent = '…';
                    span.setAttribute('style', 'display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;color:var(--gray-400);font-weight:700;font-size:12px;');
                    li.appendChild(span);
                    paginationList.appendChild(li);
                } else {
                    addBtn(String(p), p, { active: p === currentPage });
                }
            });

            addBtn('<i class="bi bi-chevron-right"></i>', currentPage + 1, { disabled: currentPage === totalPages });
        }

        function resetAndRender() { currentPage = 1; renderPagination(); }

        searchInput.addEventListener('input', resetAndRender);
        barangaySelect.addEventListener('change', resetAndRender);

        contactBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                contactBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                resetAndRender();
            });
        });

        farmsBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                farmsBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                resetAndRender();
            });
        });

        perPageSelect?.addEventListener('change', function() {
            perPage = parseInt(this.value, 10) || 25;
            resetAndRender();
        });

        renderPagination();
    });
</script>
@endpush
@endif