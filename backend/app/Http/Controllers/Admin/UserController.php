<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // List all users (Admin only)
    public function index()
    {
        $users = User::orderBy('name')->get();
        return view('admin.users.index', compact('users'));
    }

    // Show create form with role restrictions
    public function create()
    {
        $user = auth()->user();

        // Determine which roles can be created
        if ($user->role === 'admin') {
            $allowedRoles = ['admin', 'staff', 'farmer'];
        } elseif ($user->role === 'staff') {
            $allowedRoles = ['farmer']; // Staff can only add farmers
        } else {
            abort(403, 'You are not authorized to create users.');
        }

        return view('admin.users.create', compact('allowedRoles'));
    }

    // Store new user with permission checks
    public function store(Request $request)
    {
        $user = auth()->user();

        // Validate based on role
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role' => 'required|in:admin,staff,farmer',
        ];

        // Staff can only create farmers
        if ($user->role === 'staff' && $request->role !== 'farmer') {
            return back()->with('error', 'Staff can only create Farmer accounts.');
        }

        // If user is not admin, they cannot create admin or staff
        if ($user->role !== 'admin' && in_array($request->role, ['admin', 'staff'])) {
            return back()->with('error', 'You do not have permission to create Admin or Staff accounts.');
        }

        $request->validate($rules);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully! 👤');
    }

    // Show edit form with restrictions
    public function edit($id)
    {
        $userToEdit = User::findOrFail($id);
        $currentUser = auth()->user();

        // Staff can only edit farmers
        if ($currentUser->role === 'staff' && $userToEdit->role !== 'farmer') {
            abort(403, 'Staff can only edit Farmer accounts.');
        }

        // Staff cannot edit their own role to admin/staff
        if ($currentUser->role === 'staff' && $currentUser->id === $userToEdit->id) {
            // Allow editing your own profile, but limit role options
            $allowedRoles = ['staff']; // Staff can only stay as staff
            return view('admin.users.edit', compact('userToEdit', 'allowedRoles'));
        }

        // Admin can edit anyone
        if ($currentUser->role === 'admin') {
            $allowedRoles = ['admin', 'staff', 'farmer'];
            return view('admin.users.edit', compact('userToEdit', 'allowedRoles'));
        }

        // Staff editing a farmer - only allow farmer role
        if ($currentUser->role === 'staff' && $userToEdit->role === 'farmer') {
            $allowedRoles = ['farmer'];
            return view('admin.users.edit', compact('userToEdit', 'allowedRoles'));
        }

        abort(403, 'You do not have permission to edit this user.');
    }

    // Update user with permission checks
    public function update(Request $request, $id)
    {
        $userToEdit = User::findOrFail($id);
        $currentUser = auth()->user();

        // Staff can only update farmers
        if ($currentUser->role === 'staff' && $userToEdit->role !== 'farmer') {
            return back()->with('error', 'Staff can only edit Farmer accounts.');
        }

        // Staff cannot change a farmer to admin/staff
        if ($currentUser->role === 'staff' && $request->role !== 'farmer') {
            return back()->with('error', 'Staff can only assign the Farmer role.');
        }

        // Non-admin cannot create admin/staff
        if ($currentUser->role !== 'admin' && in_array($request->role, ['admin', 'staff'])) {
            return back()->with('error', 'You cannot assign Admin or Staff roles.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'role' => 'required|in:admin,staff,farmer',
            'password' => 'nullable|min:8',
        ]);

        $userToEdit->name = $request->name;
        $userToEdit->email = $request->email;
        $userToEdit->role = $request->role;

        if ($request->filled('password')) {
            $userToEdit->password = Hash::make($request->password);
        }

        $userToEdit->save();

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully! 🔄');
    }

    // Delete user with restrictions
    public function destroy($id)
    {
        $userToDelete = User::findOrFail($id);
        $currentUser = auth()->user();

        // Staff can only delete farmers
        if ($currentUser->role === 'staff' && $userToDelete->role !== 'farmer') {
            return back()->with('error', 'Staff can only delete Farmer accounts.');
        }

        // Prevent deleting your own account
        if ($userToDelete->id === $currentUser->id) {
            return back()->with('error', 'You cannot delete your own account!');
        }

        $userToDelete->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully! 🗑️');
    }
}