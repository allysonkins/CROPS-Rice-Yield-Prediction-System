<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RiceVariety;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class RiceVarietyController extends Controller
{
    public function index()
    {
        $varieties = RiceVariety::orderBy('name')->get();
        return view('admin.rice-varieties.index', compact('varieties'));
    }

    public function create()
    {
        if (auth()->user()->role === 'farmer') {
            abort(403, 'Farmers cannot add rice varieties.');
        }
        return view('admin.rice-varieties.create');
    }

    public function store(Request $request)
    {
        if (auth()->user()->role === 'farmer') {
            return response()->json([
                'success' => false,
                'error' => 'Farmers cannot add rice varieties.'
            ], 403);
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:rice_varieties',
                'classification' => 'required|in:Hybrid,Inbred',
                'growth_period' => 'nullable|integer|min:60|max:200',
                'growth_period_transplanted' => 'nullable|integer|min:60|max:200',
                'growth_period_direct' => 'nullable|integer|min:60|max:200',
                'description' => 'nullable|string',
                'avg_yield' => 'nullable|numeric|min:0|max:15',
                'max_yield' => 'nullable|numeric|min:0|max:15',
                'avg_yield_transplanted' => 'nullable|numeric|min:0|max:15',
                'max_yield_transplanted' => 'nullable|numeric|min:0|max:15',
                'avg_yield_direct' => 'nullable|numeric|min:0|max:15',
                'max_yield_direct' => 'nullable|numeric|min:0|max:15',
                'grain_quality' => 'nullable|string',
                'disease_susceptibility' => 'nullable|string|max:255',
                'optimal_temp_min' => 'nullable|numeric|min:10|max:40',
                'optimal_temp_max' => 'nullable|numeric|min:10|max:40',
                'resilience' => 'nullable|array',
            ]);

            // Set growth_period from method-specific values if not provided
            $validated['growth_period'] = $validated['growth_period_transplanted']
                                       ?? $validated['growth_period_direct']
                                       ?? null;

            $variety = RiceVariety::create($validated);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => '✅ Rice variety "' . $variety->name . '" added successfully!',
                    'data' => $variety
                ], 201);
            }

            return redirect()->route('admin.rice-varieties.index')
                ->with('success', 'Rice variety added successfully!');

        } catch (ValidationException $e) {
            // Return validation errors for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors(),
                    'error' => 'Please correct the highlighted fields.'
                ], 422);
            }
            return back()->withErrors($e->errors())->withInput();

        } catch (QueryException $e) {
            // Database errors (e.g., duplicate entry, constraint violation)
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Database error: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Database error occurred.')->withInput();

        } catch (\Exception $e) {
            // Any other exception
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to save variety: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Failed to save variety.')->withInput();
        }
    }

    public function edit($id)
    {
        if (auth()->user()->role === 'farmer') {
            abort(403, 'Farmers cannot edit rice varieties.');
        }
        $variety = RiceVariety::findOrFail($id);
        return view('admin.rice-varieties.edit', compact('variety'));
    }

    public function update(Request $request, $id)
    {
        if (auth()->user()->role === 'farmer') {
            return response()->json([
                'success' => false,
                'error' => 'Farmers cannot edit rice varieties.'
            ], 403);
        }

        try {
            $variety = RiceVariety::findOrFail($id);

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('rice_varieties')->ignore($variety->id)],
                'classification' => 'required|in:Hybrid,Inbred',
                'growth_period' => 'nullable|integer|min:60|max:200',
                'growth_period_transplanted' => 'nullable|integer|min:60|max:200',
                'growth_period_direct' => 'nullable|integer|min:60|max:200',
                'description' => 'nullable|string',
                'avg_yield' => 'nullable|numeric|min:0|max:15',
                'max_yield' => 'nullable|numeric|min:0|max:15',
                'avg_yield_transplanted' => 'nullable|numeric|min:0|max:15',
                'max_yield_transplanted' => 'nullable|numeric|min:0|max:15',
                'avg_yield_direct' => 'nullable|numeric|min:0|max:15',
                'max_yield_direct' => 'nullable|numeric|min:0|max:15',
                'grain_quality' => 'nullable|string',
                'disease_susceptibility' => 'nullable|string|max:255',
                'optimal_temp_min' => 'nullable|numeric|min:10|max:40',
                'optimal_temp_max' => 'nullable|numeric|min:10|max:40',
                'resilience' => 'nullable|array',
            ]);

            // Set growth_period from method-specific values if not provided
            $validated['growth_period'] = $validated['growth_period_transplanted']
                                       ?? $validated['growth_period_direct']
                                       ?? $variety->growth_period
                                       ?? null;

            $variety->update($validated);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => '✅ Rice variety "' . $variety->name . '" updated successfully!',
                    'data' => $variety
                ]);
            }

            return redirect()->route('admin.rice-varieties.index')
                ->with('success', 'Rice variety updated successfully!');

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
                    'error' => 'Failed to update variety: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Failed to update variety.')->withInput();
        }
    }

    public function destroy($id)
    {
        if (auth()->user()->role === 'farmer') {
            return back()->with('error', 'Farmers cannot delete rice varieties.');
        }
        $variety = RiceVariety::findOrFail($id);
        $variety->delete();

        return redirect()->route('admin.rice-varieties.index')
            ->with('success', 'Rice variety deleted successfully!');
    }

    /**
     * Get yield data for a variety based on seeding method (AJAX).
     */
    public function getYield($id, Request $request)
    {
        try {
            $variety = RiceVariety::findOrFail($id);
            $method = $request->query('method', 'Transplanted');
            $yield = $variety->getYieldForMethod($method);
            return response()->json([
                'success' => true,
                'avg' => $yield->avg,
                'max' => $yield->max,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch yield data: ' . $e->getMessage()
            ], 500);
        }
    }
}