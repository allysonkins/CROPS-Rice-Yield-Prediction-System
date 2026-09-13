<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of staff accounts only.
     */
    public function index()
    {
        $users = User::where('role', 'staff')->orderBy('name')->get();
        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new staff account.
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created staff account.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'staff',
        ]);

        // Log the creation
        log_activity('created', 'Staff account created', $user, [
            'email' => $user->email,
            'name'  => $user->name,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Staff account created successfully!'
            ]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Staff account created successfully!');
    }

    /**
     * Show the form for editing a staff account.
     */
    public function edit($id)
    {
        $user = User::where('role', 'staff')->findOrFail($id);
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified staff account.
     */
    public function update(Request $request, $id)
    {
        $user = User::where('role', 'staff')->findOrFail($id);

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|min:8',
        ]);

        $oldData = $user->only(['name', 'email']);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        // Log the update with changed fields
        log_activity('updated', 'Staff account updated', $user, [
            'old' => $oldData,
            'new' => $user->only(['name', 'email']),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Staff account updated successfully!'
            ]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Staff account updated successfully!');
    }

    /**
     * Remove the specified staff account.
     */
    public function destroy($id)
    {
        $user = User::where('role', 'staff')->findOrFail($id);

        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete your own account!');
        }

        // Log before deletion
        log_activity('deleted', 'Staff account deleted', $user, [
            'email' => $user->email,
            'name'  => $user->name,
        ]);

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Staff account deleted successfully!');
    }
}