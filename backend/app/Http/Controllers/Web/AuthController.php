<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // ══════════════════════════════════════════════════════════
    // FARMER LOGIN (default at /login)
    // ══════════════════════════════════════════════════════════

    public function showFarmerLogin()
    {
        if (Auth::check()) {
            return $this->redirectToDashboard(Auth::user()->role);
        }

        return view('auth.login');
    }

    public function loginFarmer(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'password'   => 'required|string',
        ]);

        $identifier = trim((string) $request->input('identifier'));
        $password   = $request->input('password');
        $remember   = $request->boolean('remember');

        // Farmers can't log in with an email
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'identifier' => 'Use your phone number or RSBSA number to log in. Staff can log in at the Staff Login page.',
            ]);
        }

        $rateKey = 'farmer.login.' . strtolower($identifier) . '|' . $request->ip();
        $this->checkRateLimit($rateKey, $request);

        // Find farmer by phone OR rsbsa_number
        $user = $this->findFarmerByIdentifier($identifier);

        if (!$user || !Hash::check($password, $user->password)) {
            $this->hitRateLimit($rateKey, $request, 'identifier');
        }

        // ── Success ──
        RateLimiter::clear($rateKey);
        Auth::login($user, $remember);
        $request->session()->regenerate();

        if (function_exists('log_activity')) {
            log_activity('login', 'Farmer logged in', $user);
        }

        return $this->redirectToDashboard('farmer');
    }

    // ══════════════════════════════════════════════════════════
    // STAFF / ADMIN LOGIN  (/staff/login)
    // ══════════════════════════════════════════════════════════

    public function showStaffLogin()
    {
        if (Auth::check()) {
            return $this->redirectToDashboard(Auth::user()->role);
        }

        return view('auth.staff-login');
    }

    public function loginStaff(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $email    = strtolower(trim((string) $request->input('email')));
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        $rateKey = 'staff.login.' . $email . '|' . $request->ip();
        $this->checkRateLimit($rateKey, $request);

        $user = User::where('email', $email)
            ->whereIn('role', ['admin', 'staff'])
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            $this->hitRateLimit($rateKey, $request, 'email');
        }

        // ── Success ──
        RateLimiter::clear($rateKey);
        Auth::login($user, $remember);
        $request->session()->regenerate();

        if (function_exists('log_activity')) {
            log_activity('login', 'Staff logged in', $user);
        }

        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return $this->redirectToDashboard($user->role);
    }

    // ══════════════════════════════════════════════════════════
    // LOGOUT
    // ══════════════════════════════════════════════════════════

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user && function_exists('log_activity')) {
            log_activity('logout', 'User logged out', $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Send staff back to staff login, farmers back to farmer login
        $isStaff = $user && in_array($user->role, ['admin', 'staff'], true);

        return $isStaff
            ? redirect()->route('staff.login')->with('status', 'You have been logged out.')
            : redirect()->route('login')->with('status', 'You have been logged out.');
    }

    // ══════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════

    private function findFarmerByIdentifier(string $identifier): ?User
    {
        // Normalize PH phone number
        $digits = preg_replace('/\D/', '', $identifier);
        if (str_starts_with($digits, '639')) {
            $digits = '0' . substr($digits, 2);
        } elseif (str_starts_with($digits, '9') && strlen($digits) === 10) {
            $digits = '0' . $digits;
        }

        if (preg_match('/^09\d{9}$/', $digits)) {
            return User::where('role', 'farmer')->where('phone', $digits)->first();
        }

        // Fallback: RSBSA number
        return User::where('role', 'farmer')->where('rsbsa_number', $identifier)->first();
    }

    private function checkRateLimit(string $key, Request $request): void
    {
        $ipKey = 'login.ip.' . $request->ip();

        if (RateLimiter::tooManyAttempts($ipKey, 10)) {
            $seconds = RateLimiter::availableIn($ipKey);
            throw ValidationException::withMessages([
                'identifier' => "Too many login attempts from your network. Try again in {$seconds} seconds.",
                'email'      => "Too many login attempts from your network. Try again in {$seconds} seconds.",
            ]);
        }

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'identifier' => "Too many failed attempts. Try again in {$seconds} seconds.",
                'email'      => "Too many failed attempts. Try again in {$seconds} seconds.",
            ]);
        }
    }

    private function hitRateLimit(string $key, Request $request, string $field): void
    {
        $ipKey = 'login.ip.' . $request->ip();

        RateLimiter::hit($ipKey, 60);
        RateLimiter::hit($key, 60);

        $remaining = max(0, 5 - RateLimiter::attempts($key));

        $message = 'Wrong credentials. Please try again.';
        if ($remaining > 0 && $remaining <= 2) {
            $message .= " {$remaining} attempt(s) remaining.";
        } elseif ($remaining === 0) {
            $seconds = RateLimiter::availableIn($key);
            $message = "Too many failed attempts. Try again in {$seconds} seconds.";
        }

        throw ValidationException::withMessages([$field => $message]);
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