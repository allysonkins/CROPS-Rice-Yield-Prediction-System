<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RiceVariety;
use Illuminate\Http\Request;

class RiceVarietyController extends Controller
{
    // List all rice varieties
    public function index()
    {
        $varieties = RiceVariety::orderBy('name')->get();
        return view('admin.rice-varieties.index', compact('varieties'));
    }

    // Show the form to create a new variety
    public function create()
    {
        return view('admin.rice-varieties.create');
    }

    // Store a new variety in the database
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:rice_varieties',
            'classification' => 'required|in:Hybrid,Inbred',
            'growth_period' => 'required|integer|min:60|max:200',
            'disease_susceptibility' => 'nullable|string|max:255',
            'optimal_temp_min' => 'nullable|numeric|min:10|max:40',
            'optimal_temp_max' => 'nullable|numeric|min:10|max:40',
        ]);

        RiceVariety::create($request->all());

        return redirect()->route('admin.rice-varieties.index')
            ->with('success', 'Rice variety added successfully! 🌾');
    }

    // Show the form to edit an existing variety
    public function edit($id)
    {
        $variety = RiceVariety::findOrFail($id);
        return view('admin.rice-varieties.edit', compact('variety'));
    }

    // Update an existing variety in the database
    public function update(Request $request, $id)
    {
        $variety = RiceVariety::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:rice_varieties,name,' . $id,
            'classification' => 'required|in:Hybrid,Inbred',
            'growth_period' => 'required|integer|min:60|max:200',
            'disease_susceptibility' => 'nullable|string|max:255',
            'optimal_temp_min' => 'nullable|numeric|min:10|max:40',
            'optimal_temp_max' => 'nullable|numeric|min:10|max:40',
        ]);

        $variety->update($request->all());

        return redirect()->route('admin.rice-varieties.index')
            ->with('success', 'Rice variety updated successfully! 🔄');
    }

    // Delete a rice variety
    public function destroy($id)
    {
        $variety = RiceVariety::findOrFail($id);
        $variety->delete();

        return redirect()->route('admin.rice-varieties.index')
            ->with('success', 'Rice variety deleted successfully! 🗑️');
    }
}