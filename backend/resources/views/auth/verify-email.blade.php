@extends('layouts.auth')
@section('title', 'Verify Email')

@section('content')
    <div class="brand">
        <h1>Verify your email</h1>
        <div class="accent"></div>
        <div class="role staff">Staff &amp; Admin Only</div>
    </div>

    <p class="text-center mb-3" style="font-size:14px;color:var(--gray-600);line-height:1.6">
        Thanks for signing up, <strong style="color:var(--gray-900)">{{ auth()->user()->name }}</strong>!<br>
        We sent a verification link to:<br>
        <span style="color:var(--green);font-weight:700;word-break:break-all">{{ auth()->user()->email }}</span>
    </p>
    <p class="text-center mb-4" style="font-size:13px;color:var(--gray-500);line-height:1.6">
        Click the link in that email to activate your account and continue.
        If you didn't receive it, we can send another one.
    </p>

    @if (session('status') === 'verification-link-sent')
        <div class="alert-status">
            <i class="bi bi-check-circle-fill me-2"></i>A new verification link has been sent to your email.
        </div>
    @endif

    @if ($errors->any())
        <div class="alert-custom">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" data-loading="Sending...">
        @csrf
        <button type="submit" class="btn-primary-cta js-submit">
            <i class="bi bi-envelope-arrow-up me-1"></i>Resend Verification Email
        </button>
    </form>

    <hr style="border:none;border-top:1px solid var(--gray-200);margin:20px 0 16px">

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn-outline-cta"><i class="bi bi-box-arrow-right me-1"></i>Sign Out</button>
    </form>
@endsection