<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FarmerRegistrationController extends Controller
{
    public function showForm()
    {
        if (Auth::check()) {
            return redirect($this->dashboardFor(Auth::user()->role));
        }

        return view('auth.farmer-register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'phone'        => 'required|string|regex:/^09\d{9}$/|unique:users,phone',
            'rsbsa_number' => 'nullable|string|max:50|unique:users,rsbsa_number',
            'barangay'     => 'required|string|max:255',
            'password'     => 'required|string|min:6|confirmed',
        ], [
            'phone.regex'      => 'Please enter a valid PH mobile number (e.g. 09171234567).',
            'phone.unique'     => 'This phone number is already registered. Try logging in instead.',
            'rsbsa_number.unique' => 'This RSBSA number is already registered.',
            'password.confirmed'  => 'The PINs do not match.',
            'password.min'     => 'PIN must be at least 6 characters.',
        ]);

        // Optional: enforce valid barangay from config
        if (config('santiago.barangays') && !in_array($validated['barangay'], config('santiago.barangays'), true)) {
            return back()->withErrors(['barangay' => 'Please select a valid Santiago City barangay.'])->withInput();
        }

        $user = DB::transaction(function () use ($validated) {
            // Farmers can't use @crops.local (reserved for CAO-created accounts)
            // Generate a placeholder email if they don't have one
            $email = "selfreg-{$validated['phone']}@crops.local";

            return User::create([
                'name'              => $validated['name'],
                'email'             => $email,
                'phone'             => $validated['phone'],
                'rsbsa_number'      => $validated['rsbsa_number'] ?: null,
                'barangay'          => $validated['barangay'],
                'password'          => Hash::make($validated['password']),
                'role'              => 'farmer',
                'email_verified_at' => now(),          // placeholder email = auto "verified"
                // verified_by_cao_at intentionally NULL → goes to pending queue
            ]);
        });

        if (function_exists('log_activity')) {
            log_activity('created', 'Farmer self-registered (pending CAO verification)', $user, [
                'phone'        => $user->phone,
                'rsbsa_number' => $user->rsbsa_number,
                'barangay'     => $user->barangay,
                'source'       => 'self-registration',
            ]);
        }

        // Log them in immediately — they can use the system while pending
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('farmer.dashboard')
            ->with('status', 'Welcome! Your account is pending CAO verification — some features will unlock after verification.');
    }

    private function dashboardFor(string $role): string
    {
        return match ($role) {
            'admin'  => '/admin/dashboard',
            'staff'  => '/staff/dashboard',
            'farmer' => '/farmer/dashboard',
            default  => '/',
        };
    }
}