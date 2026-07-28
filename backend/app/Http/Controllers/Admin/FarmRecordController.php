<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\FarmRecord;
use App\Models\RiceVariety;
use Illuminate\Http\Request;

class FarmRecordController extends Controller
{
    public function index()
    {
        $farmRecords = FarmRecord::with(['farm', 'riceVariety'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.farm-records.index', compact('farmRecords'));
    }

    public function create()
    {
        $farms = Farm::orderBy('name')->get();
        $varieties = RiceVariety::orderBy('name')->get();

        return view('admin.farm-records.create', compact('farms', 'varieties'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'rice_variety_id' => 'required|exists:rice_varieties,id',
            'season' => 'required|string|max:255',
            'fertilizer_kg_ha' => 'required|numeric|min:0',
            'historical_yield_tons_ha' => 'nullable|numeric|min:0',
            'seeding_method' => 'nullable|string|max:255',
        ]);

        FarmRecord::create($request->all());

        return redirect()->route('admin.farm-records.index')
            ->with('success', 'Farm record created successfully!');
    }

    public function edit($id)
    {
        $farmRecord = FarmRecord::with(['farm', 'riceVariety'])->findOrFail($id);
        $farms = Farm::orderBy('name')->get();
        $varieties = RiceVariety::orderBy('name')->get();

        return view('admin.farm-records.edit', compact('farmRecord', 'farms', 'varieties'));
    }

    public function update(Request $request, $id)
    {
        $farmRecord = FarmRecord::findOrFail($id);

        $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'rice_variety_id' => 'required|exists:rice_varieties,id',
            'season' => 'required|string|max:255',
            'fertilizer_kg_ha' => 'required|numeric|min:0',
            'historical_yield_tons_ha' => 'nullable|numeric|min:0',
            'seeding_method' => 'nullable|string|max:255',
        ]);

        $farmRecord->update($request->all());

        return redirect()->route('admin.farm-records.index')
            ->with('success', 'Farm record updated successfully!');
    }

    public function destroy($id)
    {
        $farmRecord = FarmRecord::findOrFail($id);

        // Check if this record has predictions
        if ($farmRecord->predictions()->count() > 0) {
            return redirect()->route('admin.farm-records.index')
                ->with('error', 'Cannot delete this record because it has associated predictions.');
        }

        $farmRecord->delete();

        return redirect()->route('admin.farm-records.index')
            ->with('success', 'Farm record deleted successfully!');
    }
}