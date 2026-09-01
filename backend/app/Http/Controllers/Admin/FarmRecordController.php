<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\FarmRecord;
use App\Models\RiceVariety;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class FarmRecordController extends Controller
{
    /**
     * Display a listing of farm records.
     */
    public function index()
    {
        $user = auth()->user();

        $query = FarmRecord::with(['farm', 'riceVariety']);
        
        if ($user->role === 'farmer') {
            $farmIds = Farm::where('user_id', $user->id)->pluck('id');
            $query->whereIn('farm_id', $farmIds);
        }

        $farmRecords = $query->orderBy('created_at', 'desc')->get();

        if ($user->role === 'farmer') {
            $farms = Farm::where('user_id', $user->id)->get();
        } else {
            $farms = Farm::orderBy('name')->get();
        }

        $varieties = RiceVariety::orderBy('name')->get();

        return view('admin.farm-records.index', compact('farmRecords', 'farms', 'varieties'));
    }

    /**
     * Show the form for creating a new farm record.
     */
    public function create()
    {
        if (auth()->user()->role === 'farmer') {
            abort(403, 'Farmers cannot add farm records.');
        }

        $farms = Farm::orderBy('name')->get();
        $varieties = RiceVariety::orderBy('name')->get();

        return view('admin.farm-records.create', compact('farms', 'varieties'));
    }

    /**
     * Store a newly created farm record.
     */
    public function store(Request $request)
    {
        if (auth()->user()->role === 'farmer') {
            return response()->json([
                'success' => false,
                'error' => 'Farmers cannot add farm records.'
            ], 403);
        }

        try {
            $validated = $request->validate([
                'farm_id' => 'required|exists:farms,id',
                'rice_variety_id' => 'required|exists:rice_varieties,id',
                'season' => 'required|string|max:255',
                'fertilizer_kg_ha' => 'required|numeric|min:0',
                'historical_yield_tons_ha' => 'nullable|numeric|min:0',
                'seeding_method' => 'nullable|string|max:255',
            ]);

            $record = FarmRecord::create($validated);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => '✅ Farm record created successfully!',
                    'data' => $record
                ], 201);
            }

            return redirect()->route('admin.farm-records.index')
                ->with('success', 'Farm record created successfully!');

        } catch (ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors(),
                    'error' => 'Please correct the highlighted fields.'
                ], 422);
            }
            return back()->withErrors($e->errors())->withInput();

        } catch (QueryException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Database error: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Database error occurred.')->withInput();

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to save record: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Failed to save record.')->withInput();
        }
    }

    /**
     * Show the form for editing the specified farm record.
     */
    public function edit($id)
    {
        if (auth()->user()->role === 'farmer') {
            abort(403, 'Farmers cannot edit farm records.');
        }

        $farmRecord = FarmRecord::with(['farm', 'riceVariety'])->findOrFail($id);
        $farms = Farm::orderBy('name')->get();
        $varieties = RiceVariety::orderBy('name')->get();

        return view('admin.farm-records.edit', compact('farmRecord', 'farms', 'varieties'));
    }

    /**
     * Update the specified farm record.
     */
    public function update(Request $request, $id)
    {
        if (auth()->user()->role === 'farmer') {
            return response()->json([
                'success' => false,
                'error' => 'Farmers cannot edit farm records.'
            ], 403);
        }

        try {
            $farmRecord = FarmRecord::findOrFail($id);

            $validated = $request->validate([
                'farm_id' => 'required|exists:farms,id',
                'rice_variety_id' => 'required|exists:rice_varieties,id',
                'season' => 'required|string|max:255',
                'fertilizer_kg_ha' => 'required|numeric|min:0',
                'historical_yield_tons_ha' => 'nullable|numeric|min:0',
                'seeding_method' => 'nullable|string|max:255',
            ]);

            $farmRecord->update($validated);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => '✅ Farm record updated successfully!',
                    'data' => $farmRecord
                ]);
            }

            return redirect()->route('admin.farm-records.index')
                ->with('success', 'Farm record updated successfully!');

        } catch (ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors(),
                    'error' => 'Please correct the highlighted fields.'
                ], 422);
            }
            return back()->withErrors($e->errors())->withInput();

        } catch (QueryException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Database error: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Database error occurred.')->withInput();

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to update record: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Failed to update record.')->withInput();
        }
    }

    /**
     * Remove the specified farm record.
     */
    public function destroy($id)
    {
        if (auth()->user()->role === 'farmer') {
            return back()->with('error', 'Farmers cannot delete farm records.');
        }

        $farmRecord = FarmRecord::findOrFail($id);

        if ($farmRecord->predictions()->count() > 0) {
            return redirect()->route('admin.farm-records.index')
                ->with('error', 'Cannot delete this record because it has associated predictions.');
        }

        $farmRecord->delete();

        return redirect()->route('admin.farm-records.index')
            ->with('success', 'Farm record deleted successfully!');
    }
}