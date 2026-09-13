@extends('layouts.app')

@section('title', 'Activity Logs')

@section('content')
{{-- Stats Cards --}}
<div class="row g-2 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Total Logs</div>
                <div class="value">{{ $logs->total() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Unique Users</div>
                <div class="value">{{ $users->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-people"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Unique Actions</div>
                <div class="value">{{ $actions->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-list-check"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Latest Log</div>
                <div class="value" style="font-size: 14px; font-weight: 600;">
                    {{ $logs->first()?->created_at?->format('M d, Y H:i') ?? 'N/A' }}
                </div>
            </div>
            <div class="stat-icon"><i class="bi bi-calendar3"></i></div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="row g-2 mb-4">
    <div class="col-12">
        <div class="card-custom" style="padding: 12px 20px;">
            <form method="GET" class="d-flex flex-wrap align-items-center gap-3" id="filterForm">
                {{-- Search --}}
                <div style="flex: 1; min-width: 180px;">
                    <div class="input-group" style="border-radius: 12px; overflow: hidden; border: 1px solid var(--gray-200);">
                        <span class="input-group-text" style="background: var(--gray-50); border: none; color: var(--gray-400);">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control" placeholder="Search by user, action, IP..." value="{{ request('search') }}" style="border: none; background: var(--gray-50); font-size: 13px;">
                    </div>
                </div>

                {{-- Action Dropdown --}}
                <div style="min-width: 140px;">
                    <select name="action" class="form-select" style="border-radius: 12px; border: 1px solid var(--gray-200); background: var(--gray-50); font-size: 13px; padding: 0.45rem 2rem 0.45rem 1rem; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.7rem; cursor: pointer; appearance: none; width: auto; min-width: 140px;">
                        <option value="">All Actions</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                {{ ucfirst($action) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- User Dropdown --}}
                <div style="min-width: 140px;">
                    <select name="user_id" class="form-select" style="border-radius: 12px; border: 1px solid var(--gray-200); background: var(--gray-50); font-size: 13px; padding: 0.45rem 2rem 0.45rem 1rem; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.7rem; cursor: pointer; appearance: none; width: auto; min-width: 140px;">
                        <option value="">All Users</option>
                        @foreach($users as $id => $name)
                            <option value="{{ $id }}" {{ request('user_id') == $id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Date From --}}
                <div style="min-width: 130px;">
                    <input type="date" name="from" class="form-control" value="{{ request('from') }}" style="border-radius: 12px; border: 1px solid var(--gray-200); background: var(--gray-50); font-size: 13px; padding: 0.45rem 0.75rem;">
                </div>

                {{-- Date To --}}
                <div style="min-width: 130px;">
                    <input type="date" name="to" class="form-control" value="{{ request('to') }}" style="border-radius: 12px; border: 1px solid var(--gray-200); background: var(--gray-50); font-size: 13px; padding: 0.45rem 0.75rem;">
                </div>

                {{-- Buttons --}}
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" style="border-radius: 12px; padding: 0.45rem 1.2rem; font-size: 13px;">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="{{ route('admin.logs.index') }}" class="btn btn-outline-secondary" style="border-radius: 12px; padding: 0.45rem 1.2rem; font-size: 13px;">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                    <button type="button" class="btn btn-outline-danger" style="border-radius: 12px; padding: 0.45rem 1.2rem; font-size: 13px;" data-bs-toggle="modal" data-bs-target="#clearModal">
                        <i class="bi bi-trash"></i> Clear Old
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Logs Table --}}
<div class="card-custom">
    <div class="card-title">
        <i class="bi bi-table"></i> Activity Logs
        <span class="badge bg-secondary ms-2">{{ $logs->total() }} entries</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>Subject</th>
                    <th>IP</th>
                    <th>Date</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td>{{ $log->id }}</td>
                    <td>{{ $log->user?->name ?? 'System' }}</td>
                    <td><span class="badge bg-secondary">{{ ucfirst($log->action) }}</span></td>
                    <td>{{ $log->description }}</td>
                    <td>
                        @if($log->subject_type)
                            {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $log->ip_address }}</td>
                    <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary view-log" 
                                data-url="{{ route('admin.logs.show', $log) }}" 
                                data-bs-toggle="modal" 
                                data-bs-target="#logModal">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No logs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">
        {{ $logs->withQueryString()->links() }}
    </div>
</div>

{{-- Clear Modal --}}
<div class="modal fade" id="clearModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Clear Old Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.logs.clear') }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p>Delete logs older than:</p>
                    <div class="mb-3">
                        <input type="number" name="days" class="form-control" value="30" min="1">
                        <label class="form-label">days</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Clear</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Log Detail Modal --}}
<div class="modal fade" id="logModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--green); color: white;">
                <h5 class="modal-title"><i class="bi bi-file-text"></i> Log Entry <span id="logId"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="logDetailBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('logModal');
        const modalBody = document.getElementById('logDetailBody');
        const logIdSpan = document.getElementById('logId');

        modal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const url = button.getAttribute('data-url');

            modalBody.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;

            const parts = url.split('/');
            const id = parts[parts.length - 1];
            logIdSpan.textContent = '#' + id;

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.text())
            .then(html => {
                modalBody.innerHTML = html;
            })
            .catch(() => {
                modalBody.innerHTML = `<div class="alert alert-danger">Failed to load log details.</div>`;
            });
        });
    });
</script>
@endpush
@endsection