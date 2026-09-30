<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Support\RecommendationCache;
use Illuminate\Http\Request;

class FarmController extends Controller
{
    public function index()
    {
        $farms = Farm::where('user_id', auth()->id())
            ->withCount('farmRecords')
            ->orderBy('name')
            ->get();

        $incompleteCount = $farms->filter(fn($f) => !$f->soil_type || !$f->latitude)->count();

        return view('farmer.farms.index', compact('farms', 'incompleteCount'));
    }

    public function show($id)
    {
        $farm = Farm::where('user_id', auth()->id())
            ->with(['farmRecords' => function ($q) {
                $q->orderBy('year', 'desc')->orderBy('created_at', 'desc');
            }, 'farmRecords.riceVariety', 'farmRecords.predictions'])
            ->findOrFail($id);

        return view('farmer.farms.show', compact('farm'));
    }

    public function create()
    {
        if (!request()->ajax() && !request()->wantsJson()) {
            return redirect()->route('farmer.farms.index')
                ->with('warning', 'Use the "Add Farm" button to create a new farm.');
        }

        return view('farmer.farms.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'barangay'     => 'required|string|in:' . implode(',', config('santiago.barangays', [])),
            'land_area_ha' => 'required|numeric|min:0.01|max:100',
            'soil_type'    => 'required|string|in:Clay Loam,Silty Clay,Sandy Loam,Clay,Loam',
            'latitude'     => 'nullable|numeric|between:16.60,16.75',
            'longitude'    => 'nullable|numeric|between:121.48,121.63',
        ], [
            'barangay.in' => 'Please select a valid Santiago City barangay.',
        ]);

        $validated['user_id'] = auth()->id();

        // Auto-assign GPS from barangay centroid if not provided
        if (empty($validated['latitude']) || empty($validated['longitude'])) {
            $centroid = config("santiago.centroids.{$validated['barangay']}");
            if ($centroid) {
                $validated['latitude']  = $centroid['lat'];
                $validated['longitude'] = $centroid['lng'];
            }
        }

        $farm = Farm::create($validated);

        log_activity('created', 'Farm added by farmer', $farm, [
            'name'      => $farm->name,
            'barangay'  => $farm->barangay,
            'land_area' => $farm->land_area_ha,
            'source'    => 'farmer-self-service',
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Farm added successfully!',
                'data'    => $farm,
            ]);
        }

        return redirect()
            ->route('farmer.farms.show', $farm->id)
            ->with('success', 'Farm added successfully!');
    }

    public function edit($id)
    {
        if (!request()->ajax() && !request()->wantsJson()) {
            return redirect()->route('farmer.farms.index');
        }

        $farm = Farm::where('user_id', auth()->id())->findOrFail($id);
        return view('farmer.farms.edit', compact('farm'));
    }

    public function update(Request $request, $id)
    {
        $farm = Farm::where('user_id', auth()->id())->findOrFail($id);

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'barangay'     => 'required|string|in:' . implode(',', config('santiago.barangays', [])),
            'land_area_ha' => 'required|numeric|min:0.01|max:100',
            'soil_type'    => 'required|string|in:Clay Loam,Silty Clay,Sandy Loam,Clay,Loam',
            'latitude'     => 'nullable|numeric|between:16.60,16.75',
            'longitude'    => 'nullable|numeric|between:121.48,121.63',
        ]);

        $old = $farm->only(['name', 'barangay', 'land_area_ha', 'soil_type', 'latitude', 'longitude']);
        $farm->update($validated);

        // ── Recommendations now reflect stale farm data (soil, area, GPS) ──
        RecommendationCache::forget($farm->id);

        log_activity('updated', 'Farm updated by farmer', $farm, [
            'old' => $old,
            'new' => $farm->only(['name', 'barangay', 'land_area_ha', 'soil_type', 'latitude', 'longitude']),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Farm updated successfully!',
                'data'    => $farm,
            ]);
        }

        return redirect()
            ->route('farmer.farms.show', $farm->id)
            ->with('success', 'Farm updated successfully!');
    }
}