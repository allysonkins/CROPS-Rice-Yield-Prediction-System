<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Http\Request;

class FarmController extends Controller
{
    /**
     * Display a listing of farms (Accessible to Admin & Staff).
     */
    public function index()
    {
        $farms = Farm::with('user')->orderBy('name')->get();
        $farmers = User::where('role', 'farmer')->orderBy('name')->get();

        return view('admin.farms.index', compact('farms', 'farmers'));
    }

    /**
     * Show the form for creating a new farm (Admin only).
     */
    public function create()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Only administrators can create farms.');
        }

        $farmers = User::where('role', 'farmer')->orderBy('name')->get();
        return view('admin.farms.create', compact('farmers'));
    }

    /**
     * Store a newly created farm in storage (Admin only).
     */
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Only administrators can create farms.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'land_area_ha' => 'required|numeric|min:0.01',
            'soil_type' => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $farm = Farm::create($request->all());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Farm created successfully!',
                'data' => $farm
            ]);
        }

        return redirect()->route('admin.farms.index')
            ->with('success', 'Farm created successfully!');
    }

    /**
     * Show the form for editing the specified farm (Admin only).
     */
    public function edit($id)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Only administrators can edit farms.');
        }

        $farm = Farm::findOrFail($id);
        $farmers = User::where('role', 'farmer')->orderBy('name')->get();

        return view('admin.farms.edit', compact('farm', 'farmers'));
    }

    /**
     * Update the specified farm in storage (Admin only).
     */
    public function update(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Only administrators can update farms.');
        }

        $farm = Farm::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'land_area_ha' => 'required|numeric|min:0.01',
            'soil_type' => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $farm->update($request->all());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Farm updated successfully!',
                'data' => $farm
            ]);
        }

        return redirect()->route('admin.farms.index')
            ->with('success', 'Farm updated successfully!');
    }

    /**
     * Remove the specified farm from storage (Admin only).
     */
    public function destroy($id)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Only administrators can delete farms.');
        }

        $farm = Farm::findOrFail($id);

        if ($farm->farmRecords()->count() > 0) {
            return redirect()->route('admin.farms.index')
                ->with('error', 'Cannot delete this farm because it has associated farm records.');
        }

        $farm->delete();

        return redirect()->route('admin.farms.index')
            ->with('success', 'Farm deleted successfully!');
    }
}