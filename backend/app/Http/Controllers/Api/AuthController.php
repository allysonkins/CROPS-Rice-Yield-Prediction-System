<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectToDashboard(Auth::user()->role);
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // --- Rate limiting keys ---
        $ipKey    = 'login.ip.' . $request->ip();
        $emailKey = 'login.email.' . strtolower($request->input('email')) . '|' . $request->ip();

        $maxAttempts  = 5;
        $decaySeconds = 60;

        // --- Check IP-based limit ---
        if (RateLimiter::tooManyAttempts($ipKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($ipKey);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts from your network. Please try again in {$seconds} seconds.",
            ]);
        }

        // --- Check email+IP limit ---
        if (RateLimiter::tooManyAttempts($emailKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($emailKey);

            throw ValidationException::withMessages([
                'email' => "Too many failed attempts for this account. Please try again in {$seconds} seconds.",
            ]);
        }

        // --- Attempt login ---
        $credentials = $request->only('email', 'password');
        $remember    = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            // Clear all limits on success
            RateLimiter::clear($ipKey);
            RateLimiter::clear($emailKey);

            $request->session()->regenerate();

            if (function_exists('log_activity')) {
                log_activity('login', 'User logged in', Auth::user());
            }

            // Redirect unverified users to the notice page
            if (!Auth::user()->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }

            return $this->redirectToDashboard(Auth::user()->role);
        }

        // --- Failure: record attempt ---
        RateLimiter::hit($ipKey, $decaySeconds);
        RateLimiter::hit($emailKey, $decaySeconds);

        // Show remaining attempts warning
        $attempts    = RateLimiter::attempts($emailKey);
        $remaining   = max(0, $maxAttempts - $attempts);

        $message = 'Invalid email or password.';
        if ($remaining > 0 && $remaining <= 2) {
            $message .= " You have {$remaining} attempt(s) remaining.";
        } elseif ($remaining === 0) {
            $seconds = RateLimiter::availableIn($emailKey);
            $message = "Too many failed attempts. Please try again in {$seconds} seconds.";
        }

        throw ValidationException::withMessages([
            'email' => $message,
        ]);
    }

    public function logout(Request $request)
    {
        if (Auth::check() && function_exists('log_activity')) {
            log_activity('logout', 'User logged out', Auth::user());
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('status', 'You have been logged out.');
    }

    protected function redirectToDashboard($role)
    {
        return match ($role) {
            'admin'  => redirect()->route('admin.dashboard'),
            'staff'  => redirect()->route('staff.dashboard'),
            'farmer' => redirect()->route('farmer.dashboard'),
            default  => redirect('/login'),
        };
    }
}