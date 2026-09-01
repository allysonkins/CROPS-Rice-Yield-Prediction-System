<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Show the profile edit form.
     */
    public function edit()
    {
        $user = auth()->user();
        return view('profile.edit', compact('user'));
    }

    /**
     * Update the user's profile.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
        ];

        // Only farmers have a barangay field
        if ($user->role === 'farmer') {
            $rules['barangay'] = 'nullable|string|max:255';
        }

        $validated = $request->validate($rules);

        // Update name and email
        $user->name = $validated['name'];
        $user->email = $validated['email'];

        // Update barangay only if farmer
        if ($user->role === 'farmer' && isset($validated['barangay'])) {
            $user->barangay = $validated['barangay'];
        }

        // Update password if provided
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('profile.edit')
            ->with('success', 'Profile updated successfully!');
    }
}