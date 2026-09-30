@extends('layouts.app')

@section('title', 'Model Training')

@php
    $model = $stats['current_model'];
@endphp

@section('content')

{{-- ═══════════ CURRENT MODEL ═══════════ --}}
<div class="card-custom mb-4" style="border-top: 4px solid var(--brand-green);">
    <div class="card-title d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-cpu-fill"></i> Current Model</span>
        @if($model)
            <span class="badge-status high">
                <span class="dot"></span> Live
            </span>
        @else
            <span class="badge-status" style="background: var(--gray-100); color: var(--gray-600); border-color: var(--gray-200);">
                <span class="dot" style="background: var(--gray-400);"></span> Baseline (not retrained yet)
            </span>
        @endif
    </div>

    @if($model)
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.5px;">Version</div>
                <div style="font-family: monospace; font-size: 15px; font-weight: 700; color: var(--slate-800); margin-top: 4px;">
                    {{ $model['version'] ?? '—' }}
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.5px;">Accuracy</div>
                <div style="font-size: 20px; font-weight: 800; color: var(--brand-green); margin-top: 4px;">
                    {{ isset($model['overall_accuracy']) ? number_format($model['overall_accuracy'] * 100, 2) . '%' : '—' }}
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.5px;">Samples</div>
                <div style="font-size: 15px; font-weight: 700; color: var(--slate-800); margin-top: 4px;">
                    {{ $model['training_samples'] ?? '—' }}
                    @if(!empty($model['real_samples']))
                        <small class="text-muted">({{ $model['real_samples'] }} real)</small>
                    @endif
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div style="font-size: 10px; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.5px;">Trained at</div>
                <div style="font-size: 13px; font-weight: 600; color: var(--slate-700); margin-top: 4px;">
                    {{ isset($model['trained_at']) ? \Carbon\Carbon::parse($model['trained_at'])->diffForHumans() : '—' }}
                </div>
            </div>
        </div>
    @else
        <p class="text-muted mb-0" style="font-size: 13px;">
            Predictions are currently using the baseline model trained on the dataset.
            After your first retrain, the model will start learning from real harvest records.
        </p>
    @endif
</div>

{{-- ═══════════ TRAINING DATA ═══════════ --}}
<div class="row g-2 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="border-left-color: var(--brand-green);">
            <div class="stat-info">
                <div class="label" style="text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;">Harvested Records</div>
                <div class="value" style="font-size: 26px;">{{ $stats['total_samples'] }}</div>
                <div class="text-muted small">With actual yield</div>
            </div>
            <div class="stat-icon"><i class="bi bi-database-fill" style="color: var(--brand-green);"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="border-left-color: var(--brand-gold);">
            <div class="stat-info">
                <div class="label" style="text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;">With Weather</div>
                <div class="value" style="font-size: 26px;">{{ $stats['with_weather'] }}</div>
                <div class="text-muted small">Have weather snapshot</div>
            </div>
            <div class="stat-icon"><i class="bi bi-cloud-sun-fill" style="color: var(--brand-gold);"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="border-left-color: #4f46e5;">
            <div class="stat-info">
                <div class="label" style="text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;">With History</div>
                <div class="value" style="font-size: 26px;">{{ $stats['with_history'] }}</div>
                <div class="text-muted small">Have prior-season yield</div>
            </div>
            <div class="stat-icon"><i class="bi bi-clock-history" style="color: #4f46e5;"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="border-left-color: {{ $stats['can_train'] ? 'var(--brand-green)' : 'var(--brand-danger)' }};">
            <div class="stat-info">
                <div class="label" style="text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;">Retrain Status</div>
                <div class="value" style="font-size: 18px; color: {{ $stats['can_train'] ? 'var(--brand-green)' : 'var(--brand-danger)' }};">
                    {{ $stats['can_train'] ? 'Ready' : 'Not Ready' }}
                </div>
                <div class="text-muted small">
                    @if($stats['can_train'])
                        Minimum of {{ $stats['min_samples'] }} samples met
                    @else
                        Need {{ $stats['min_samples'] - $stats['total_samples'] }} more sample(s)
                    @endif
                </div>
            </div>
            <div class="stat-icon">
                <i class="bi bi-{{ $stats['can_train'] ? 'check-circle-fill' : 'exclamation-triangle-fill' }}"
                   style="color: {{ $stats['can_train'] ? 'var(--brand-green)' : 'var(--brand-danger)' }};"></i>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════ RETRAIN ═══════════ --}}
<div class="card-custom mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div style="flex: 1; min-width: 260px;">
            <h6 style="font-weight: 700; color: var(--slate-900); margin-bottom: 6px;">
                <i class="bi bi-arrow-repeat" style="color: var(--brand-green);"></i>
                Retrain the Model
            </h6>
            <p style="font-size: 13px; color: var(--slate-600); margin-bottom: 0;">
                Exports all harvested farm records with their weather snapshots and retrains the RandomForest
                by merging them with the baseline. The current model keeps serving predictions until
                the new one passes validation, then hot-swaps in with <strong>no restart</strong>.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.ml.history') }}" class="btn btn-secondary">
                <i class="bi bi-clock-history"></i> View History
            </a>
            <button type="button" class="btn btn-success" id="retrainBtn"
                    {{ !$stats['can_train'] ? 'disabled' : '' }}
                    onclick="startRetraining()">
                <i class="bi bi-lightning-charge-fill"></i> Retrain Now
            </button>
        </div>
    </div>

    <div id="trainingPanel" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--slate-200);">
        <div class="d-flex align-items-center gap-3 mb-3">
            <div class="spinner-border text-success" id="trainingSpinner" role="status" style="width: 24px; height: 24px;"></div>
            <div>
                <strong style="color: var(--slate-900); font-size: 14px;" id="trainingStatusText">Training in progress...</strong>
                <div class="text-muted" style="font-size: 12px;" id="trainingRunMeta">This typically takes 1–5 minutes.</div>
            </div>
        </div>

        <div id="trainingResult" style="display: none;"></div>

        <details style="margin-top: 12px;">
            <summary style="cursor: pointer; font-size: 12px; color: var(--slate-500);">Show training log</summary>
            <pre id="trainingLog"
                 style="margin-top: 8px; padding: 12px; background: var(--slate-900); color: #d1fae5; border-radius: 8px; font-size: 11px; max-height: 240px; overflow: auto; white-space: pre-wrap;"></pre>
        </details>
    </div>
</div>

{{-- ═══════════ RECENT RUNS ═══════════ --}}
<div class="card-custom">
    <div class="card-title">
        <i class="bi bi-table"></i> Recent Training Runs
    </div>

    @if($recentRuns->isEmpty())
        <div class="text-center py-4 text-muted">
            <i class="bi bi-inbox" style="font-size: 32px;"></i>
            <p class="mt-2 mb-0" style="font-size: 13px;">No training runs yet.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Status</th>
                        <th>Version</th>
                        <th>Accuracy</th>
                        <th>Samples</th>
                        <th>Duration</th>
                        <th>By</th>
                        <th>When</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentRuns as $run)
                        @php
                            $delta = $run->accuracy_delta;
                            $deltaClass = $delta === null ? '' : ($delta > 0.005 ? 'text-success' : ($delta < -0.005 ? 'text-danger' : 'text-muted'));
                        @endphp
                        <tr>
                            <td>{{ $run->id }}</td>
                            <td>
                                <span class="badge-status {{ $run->status_badge_class }}">
                                    <span class="dot"></span> {{ ucfirst($run->status) }}
                                </span>
                            </td>
                            <td><code style="font-size: 11px;">{{ $run->model_version ?? '—' }}</code></td>
                            <td>
                                @if($run->overall_accuracy !== null)
                                    <strong>{{ number_format($run->overall_accuracy * 100, 2) }}%</strong>
                                    @if($delta !== null)
                                        <small class="{{ $deltaClass }}" style="margin-left: 6px;">
                                            {{ $delta > 0 ? '+' : '' }}{{ number_format($delta * 100, 2) }}%
                                        </small>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                {{ $run->training_samples ?: '—' }}
                                @if($run->real_samples)
                                    <small class="text-muted d-block" style="font-size: 10px;">
                                        {{ $run->real_samples }} real · {{ $run->synthetic_samples }} synthetic
                                    </small>
                                @endif
                            </td>
                            <td>{{ $run->duration_seconds ? gmdate('i:s', $run->duration_seconds) : '—' }}</td>
                            <td>{{ $run->triggeredBy->name ?? 'System' }}</td>
                            <td>{{ $run->created_at->diffForHumans() }}</td>
                            <td>
                                <a href="{{ route('admin.ml.show', $run->id) }}" class="btn btn-sm btn-secondary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
    let pollInterval = null;

    function startRetraining() {
        const btn = document.getElementById('retrainBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Starting...';

        fetch('{{ route("admin.ml.start") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
            },
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                alert('❌ ' + (data.error || 'Failed to start training.'));
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-lightning-charge-fill"></i> Retrain Now';
                return;
            }

            document.getElementById('trainingPanel').style.display = 'block';
            document.getElementById('trainingStatusText').textContent = 'Training in progress...';
            document.getElementById('trainingRunMeta').textContent = 'Run #' + data.run_id + ' — polling for updates…';
            document.getElementById('trainingResult').style.display = 'none';
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Training...';

            pollRun(data.run_id);
        })
        .catch(err => {
            alert('❌ Network error: ' + err);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-lightning-charge-fill"></i> Retrain Now';
        });
    }

    function pollRun(runId) {
        if (pollInterval) clearInterval(pollInterval);

        pollInterval = setInterval(() => {
            fetch('/admin/ml/status/' + runId, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;

                if (data.progress_log) {
                    const logEl = document.getElementById('trainingLog');
                    logEl.textContent = data.progress_log;
                    logEl.scrollTop = logEl.scrollHeight;
                }

                if (data.status === 'completed') {
                    clearInterval(pollInterval);
                    showSuccess(data);
                } else if (data.status === 'failed') {
                    clearInterval(pollInterval);
                    showFailure(data);
                }
            })
            .catch(() => {});
        }, 3000);
    }

    function showSuccess(data) {
        const spinner = document.getElementById('trainingSpinner');
        if (spinner) spinner.outerHTML =
            '<i class="bi bi-check-circle-fill" style="font-size: 28px; color: var(--brand-green);"></i>';

        document.getElementById('trainingStatusText').textContent = 'Training completed successfully!';
        document.getElementById('trainingRunMeta').textContent =
            'Trained in ' + Math.round(data.duration_seconds) + 's · ' +
            data.training_samples + ' samples';

        const pct = (data.overall_accuracy * 100).toFixed(2);
        document.getElementById('trainingResult').style.display = 'block';
        document.getElementById('trainingResult').innerHTML = `
            <div class="alert-custom alert-custom-success mb-0">
                <i class="bi bi-patch-check-fill alert-icon"></i>
                <div class="alert-content">
                    <strong>New model is now live.</strong>
                    <div style="font-size: 12px; margin-top: 2px;">
                        Accuracy: <strong>${pct}%</strong> · Page reloads in 3s.
                    </div>
                </div>
            </div>
        `;

        setTimeout(() => location.reload(), 3000);
    }

    function showFailure(data) {
        const spinner = document.getElementById('trainingSpinner');
        if (spinner) spinner.outerHTML =
            '<i class="bi bi-x-circle-fill" style="font-size: 28px; color: var(--brand-danger);"></i>';

        document.getElementById('trainingStatusText').textContent = 'Training failed';
        document.getElementById('trainingRunMeta').textContent = data.error_message || 'Check the log below for details.';

        const btn = document.getElementById('retrainBtn');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-lightning-charge-fill"></i> Retrain Now';
    }
</script>
@endpush