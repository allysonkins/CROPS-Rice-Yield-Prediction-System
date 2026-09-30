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
        if (!in_array(auth()->user()->role, ['admin', 'staff'], true)) {
            abort(403, 'Only administrators and staff can create farms.');
        }

        if (!request()->ajax() && !request()->wantsJson()) {
            return redirect()->route('admin.farms.index')
                ->with('warning', 'Use the "Add Farm" button to create a new farm.');
        }

        $farmers = User::where('role', 'farmer')->orderBy('name')->get();
        return view('admin.farms.create', compact('farmers'));
    }

    public function store(Request $request)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'], true)) {
            abort(403, 'Only administrators and staff can create farms.');
        }

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'barangay'     => 'required|string|in:' . implode(',', config('santiago.barangays', [])),
            'land_area_ha' => 'required|numeric|min:0.01|max:1000',
            'soil_type'    => 'required|string|in:Clay Loam,Silty Clay,Sandy Loam,Clay,Loam',
            'user_id'      => 'nullable|exists:users,id,role,farmer',
            'latitude'     => 'nullable|numeric|between:16.60,16.75',
            'longitude'    => 'nullable|numeric|between:121.48,121.63',
        ]);

        $farm = Farm::create($validated);
        $farm->load('user');

        log_activity('created', 'Farm created', $farm, [
            'name'      => $farm->name,
            'barangay'  => $farm->barangay,
            'farmer'    => $farm->user->name ?? 'Unassigned',
            'land_area' => $farm->land_area_ha,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Farm created successfully!',
                'data'    => $farm,
            ]);
        }

        return redirect()->route('admin.farms.index')
            ->with('success', 'Farm created successfully!');
    }

    public function edit($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'], true)) {
            abort(403, 'Only administrators and staff can edit farms.');
        }

        if (!request()->ajax() && !request()->wantsJson()) {
            return redirect()->route('admin.farms.index');
        }

        $farm = Farm::findOrFail($id);
        $farmers = User::where('role', 'farmer')->orderBy('name')->get();

        return view('admin.farms.edit', compact('farm', 'farmers'));
    }

    public function update(Request $request, $id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'], true)) {
            abort(403, 'Only administrators and staff can update farms.');
        }

        $farm = Farm::findOrFail($id);

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'barangay'     => 'required|string|in:' . implode(',', config('santiago.barangays', [])),
            'land_area_ha' => 'required|numeric|min:0.01|max:1000',
            'soil_type'    => 'required|string|in:Clay Loam,Silty Clay,Sandy Loam,Clay,Loam',
            'user_id'      => 'nullable|exists:users,id,role,farmer',
            'latitude'     => 'nullable|numeric|between:16.60,16.75',
            'longitude'    => 'nullable|numeric|between:121.48,121.63',
        ]);

        $oldData = $farm->only(['name', 'barangay', 'land_area_ha', 'soil_type', 'user_id']);
        $farm->update($validated);

        log_activity('updated', 'Farm updated', $farm, [
            'old' => $oldData,
            'new' => $farm->only(['name', 'barangay', 'land_area_ha', 'soil_type', 'user_id']),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Farm updated successfully!',
                'data'    => $farm,
            ]);
        }

        return redirect()->route('admin.farms.index')
            ->with('success', 'Farm updated successfully!');
    }

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

        log_activity('deleted', 'Farm deleted', $farm, [
            'name'     => $farm->name,
            'barangay' => $farm->barangay,
            'farmer'   => $farm->user->name ?? 'Unassigned',
        ]);

        $farm->delete();

        return redirect()->route('admin.farms.index')
            ->with('success', 'Farm deleted successfully!');
    }
}