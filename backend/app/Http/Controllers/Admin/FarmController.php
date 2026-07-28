<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Http\Request;

class FarmController extends Controller
{
    public function index()
    {
        $farms = Farm::with('user')->orderBy('name')->get();
        $farmers = User::where('role', 'farmer')->orderBy('name')->get();

        return view('admin.farms.index', compact('farms', 'farmers'));
    }

    public function create()
    {
        $farmers = User::where('role', 'farmer')->orderBy('name')->get();
        return view('admin.farms.create', compact('farmers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'land_area_ha' => 'required|numeric|min:0.01',
            'soil_type' => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        Farm::create($request->all());

        return redirect()->route('admin.farms.index')
            ->with('success', 'Farm created successfully!');
    }

    public function edit($id)
    {
        $farm = Farm::findOrFail($id);
        $farmers = User::where('role', 'farmer')->orderBy('name')->get();

        return view('admin.farms.edit', compact('farm', 'farmers'));
    }

    public function update(Request $request, $id)
    {
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

        return redirect()->route('admin.farms.index')
            ->with('success', 'Farm updated successfully!');
    }

    public function destroy($id)
    {
        $farm = Farm::findOrFail($id);

        // Check if this farm has records
        if ($farm->farmRecords()->count() > 0) {
            return redirect()->route('admin.farms.index')
                ->with('error', 'Cannot delete this farm because it has associated farm records.');
        }

        $farm->delete();

        return redirect()->route('admin.farms.index')
            ->with('success', 'Farm deleted successfully!');
    }
}