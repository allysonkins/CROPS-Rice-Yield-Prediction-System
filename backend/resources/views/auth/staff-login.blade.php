@extends('layouts.auth')
@section('title', 'CAO Staff Login')

@section('content')
    <div class="brand">
        <h1>Staff sign in</h1>
        <div class="accent"></div>
        <p>City Agriculture Office</p>
        <div class="role staff">Staff &amp; Admin Login</div>
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

    <form method="POST" action="{{ route('staff.login.submit') }}" data-loading="Signing in...">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" id="email" name="email" class="form-control"
                       value="{{ old('email') }}" placeholder="you@cao.gov.ph"
                       autocomplete="email" inputmode="email" autocapitalize="off" spellcheck="false" required autofocus>
            </div>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" id="password" name="password" class="form-control"
                       autocomplete="current-password" required>
                <button type="button" class="pw-toggle" onclick="togglePassword()" tabindex="-1" aria-label="Show or hide password">
                    <i class="bi bi-eye" id="pwIcon"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-primary-cta js-submit">Sign In</button>
    </form>

    <div class="secure-note"><i class="bi bi-shield-lock-fill"></i> Authorized personnel only. Activity may be logged.</div>
@endsection

@section('below')
    <a href="{{ route('login') }}"><i class="bi bi-arrow-left"></i> Farmer Login</a>
@endsection