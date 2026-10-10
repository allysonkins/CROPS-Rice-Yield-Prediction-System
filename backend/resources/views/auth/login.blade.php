@extends('layouts.auth')
@section('title', 'Farmer Login')

@section('content')
    <div class="brand">
        <h1>Welcome back</h1>
        <div class="accent"></div>
        <p>Rice Yield Prediction</p>
        <div class="role">Farmer Login</div>
    </div>

    @if ($errors->any())
        <div class="alert-custom">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first() }}
        </div>
    @endif

    @if (session('status'))
        <div class="alert-status">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" data-loading="Signing in...">
        @csrf

        <div class="mb-3">
            <label for="identifier" class="form-label">Phone Number or RSBSA Number</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                <input type="text" id="identifier" name="identifier" class="form-control"
                       value="{{ old('identifier') }}"
                       placeholder="09171234567 or RSBSA-2026-0061"
                       inputmode="text" autocomplete="username" required autofocus>
            </div>
            <div class="helper">Use your phone number or your RSBSA number.</div>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label">PIN</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="••••••" autocomplete="current-password" required>
                <button type="button" class="pw-toggle" onclick="togglePassword()" tabindex="-1" aria-label="Show or hide PIN">
                    <i class="bi bi-eye" id="pwIcon"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-primary-cta js-submit">Sign In</button>
    </form>

    <div class="card-foot">
        <p>New farmer?</p>
        <a href="{{ route('farmer.register') }}">Create your account <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="secure-note"><i class="bi bi-lock-fill"></i> Your connection to this system is secured.</div>
@endsection

@section('below')
    <a href="{{ route('staff.login') }}"><i class="bi bi-shield-lock"></i> CAO Staff Login</a>
@endsection