<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Show the farmer's profile edit form.
     */
    public function edit()
    {
        return view('farmer.profile');
    }

    /**
     * Update the farmer's profile.
     *
     * Farmers use phone + PIN to log in. Email is not required for farmers —
     * if they have one, it's just a placeholder generated at import time.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name'         => 'required|string|max:255',
            'phone'        => [
                'nullable',
                'string',
                'regex:/^09\d{9}$/',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
            'rsbsa_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('users', 'rsbsa_number')->ignore($user->id),
            ],
            'barangay'     => 'nullable|string|max:255',
            'password'     => 'nullable|min:6|confirmed',
        ], [
            'phone.regex'  => 'Phone must be a valid PH mobile number (e.g. 09171234567).',
            'phone.unique' => 'This phone number is already registered to another account.',
            'rsbsa_number.unique' => 'This RSBSA number is already registered.',
            'password.min' => 'PIN must be at least 6 characters.',
            'password.confirmed' => 'The PIN confirmation does not match.',
        ]);

        // ── A farmer must always keep at least one login identifier ──
        $newPhone = $request->filled('phone') ? $request->phone : $user->phone;
        $newRsbsa = $request->filled('rsbsa_number') ? $request->rsbsa_number : $user->rsbsa_number;

        if (empty($newPhone) && empty($newRsbsa)) {
            return back()
                ->withErrors(['phone' => 'You must keep at least a phone number or RSBSA number so you can log in.'])
                ->withInput();
        }

        // ── Save basic fields ──
        $user->name         = $request->name;
        $user->phone        = $request->filled('phone') ? $request->phone : null;
        $user->rsbsa_number = $request->filled('rsbsa_number') ? $request->rsbsa_number : null;
        $user->barangay     = $request->barangay;

        // ── PIN change ──
        // When the farmer sets their own PIN, clear the CAO-viewable copy
        // (the encrypted `pin_encrypted` field) so the CAO can no longer read it.
        if ($request->filled('password')) {
            $user->password         = Hash::make($request->password);
            $user->pin_encrypted    = null;
            $user->pin_generated_at = null;
        }

        $user->save();

        if (function_exists('log_activity')) {
            log_activity('updated', 'Farmer updated own profile', $user, [
                'name'         => $user->name,
                'phone'        => $user->phone,
                'rsbsa_number' => $user->rsbsa_number,
                'barangay'     => $user->barangay,
                'pin_changed'  => $request->filled('password'),
            ]);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully!',
            ]);
        }

        return redirect()->route('farmer.profile.edit')
            ->with('success', 'Profile updated successfully!');
    }
}