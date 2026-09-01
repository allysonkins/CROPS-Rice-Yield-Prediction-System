<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Farm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class FarmerController extends Controller
{
    public function index()
    {
        $farmers = User::where('role', 'farmer')
            ->with('farms')  // ✅ EAGER LOAD - fixes the count() error
            ->orderBy('name')
            ->get();

        return view('admin.farmers.index', compact('farmers'));
    }

    public function create()
    {
        return view('admin.farmers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'barangay' => 'nullable|string|max:255',
        ]);

        $farmer = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'farmer',
            'barangay' => $request->barangay,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Farmer created successfully!',
                'data' => $farmer
            ]);
        }

        return redirect()->route('admin.farmers.index')
            ->with('success', 'Farmer created successfully!');
    }

    public function edit($id)
    {
        $farmer = User::where('role', 'farmer')->with('farms')->findOrFail($id);
        return view('admin.farmers.edit', compact('farmer'));
    }

    public function update(Request $request, $id)
    {
        $farmer = User::where('role', 'farmer')->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|min:8',
            'barangay' => 'nullable|string|max:255',
        ]);

        $farmer->name = $request->name;
        $farmer->email = $request->email;
        $farmer->barangay = $request->barangay;

        if ($request->filled('password')) {
            $farmer->password = Hash::make($request->password);
        }

        $farmer->save();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Farmer updated successfully!',
                'data' => $farmer
            ]);
        }

        return redirect()->route('admin.farmers.index')
            ->with('success', 'Farmer updated successfully!');
    }

    public function destroy($id)
    {
        $farmer = User::where('role', 'farmer')->findOrFail($id);

        if ($farmer->id === auth()->id()) {
            return redirect()->route('admin.farmers.index')
                ->with('error', 'You cannot delete your own account!');
        }

        $farmer->delete();

        return redirect()->route('admin.farmers.index')
            ->with('success', 'Farmer deleted successfully!');
    }
}