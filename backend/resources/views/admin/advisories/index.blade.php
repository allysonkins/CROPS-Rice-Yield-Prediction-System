@extends('layouts.app')

@section('title', 'Advisories')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">📢 Advisories</h4>
    <a href="{{ route('admin.advisories.create') }}" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> New Advisory
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">
    @forelse($advisories as $advisory)
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card-custom" style="border-left: 5px solid {{ $advisory->is_active ? '#2e7d32' : '#95a5a6' }};">
                <div class="d-flex justify-content-between align-items-start">
                    <h6 class="mb-1"><strong>{{ $advisory->title }}</strong></h6>
                    <span class="badge {{ $advisory->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $advisory->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <small class="text-muted">
                    <i class="bi bi-tag"></i> {{ ucfirst($advisory->target_audience) }}
                </small>
                <p class="mt-2 text-muted small">{{ Str::limit($advisory->content, 100) }}</p>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <small class="text-muted">{{ $advisory->published_at->diffForHumans() }}</small>
                    <div>
                        <a href="{{ route('admin.advisories.edit', $advisory->id) }}" class="btn btn-sm btn-warning">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form action="{{ route('admin.advisories.destroy', $advisory->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this advisory?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="text-center py-5 text-muted">
                <i class="bi bi-megaphone" style="font-size: 48px;"></i>
                <p>No advisories yet. Create your first one!</p>
            </div>
        </div>
    @endforelse
</div>
@endsection