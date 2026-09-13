@extends('layouts.app')

@section('title', 'Log Detail')

@section('content')
<div class="card-custom">
    <div class="card-title">
        <i class="bi bi-file-text"></i> Log Entry #{{ $log->id }}
        <a href="{{ route('admin.logs.index') }}" class="btn btn-secondary btn-sm ms-auto">Back</a>
    </div>

    <dl class="row">
        <dt class="col-sm-3">User</dt>
        <dd class="col-sm-9">{{ $log->user?->name ?? 'System' }}</dd>

        <dt class="col-sm-3">Action</dt>
        <dd class="col-sm-9">{{ ucfirst($log->action) }}</dd>

        <dt class="col-sm-3">Description</dt>
        <dd class="col-sm-9">{{ $log->description }}</dd>

        <dt class="col-sm-3">Subject</dt>
        <dd class="col-sm-9">
            @if($log->subject_type)
                {{ class_basename($log->subject_type) }} (ID: {{ $log->subject_id }})
            @else
                —
            @endif
        </dd>

        <dt class="col-sm-3">Properties</dt>
        <dd class="col-sm-9">
            <pre>{{ json_encode($log->properties, JSON_PRETTY_PRINT) }}</pre>
        </dd>

        <dt class="col-sm-3">IP Address</dt>
        <dd class="col-sm-9">{{ $log->ip_address }}</dd>

        <dt class="col-sm-3">User Agent</dt>
        <dd class="col-sm-9">{{ $log->user_agent }}</dd>

        <dt class="col-sm-3">Timestamp</dt>
        <dd class="col-sm-9">{{ $log->created_at->format('Y-m-d H:i:s') }}</dd>
    </dl>
</div>
@endsection