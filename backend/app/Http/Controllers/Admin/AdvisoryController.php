<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advisory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdvisoryController extends Controller
{
    /**
     * Display a listing of advisories.
     * Admin/Staff: View all. Farmer: View only active advisories for them.
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->role === 'farmer') {
            $advisories = Advisory::where('is_active', true)
                ->where(function ($q) {
                    $q->where('target_audience', 'farmers')
                      ->orWhere('target_audience', 'all');
                })
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $advisories = Advisory::with('user')->orderBy('created_at', 'desc')->get();
        }

        return view('admin.advisories.index', compact('advisories'));
    }

    /**
     * Show the form for creating a new advisory.
     * Accessible to: Admin, Staff only
     */
    public function create()
    {
        if (auth()->user()->role === 'farmer') {
            abort(403, 'Farmers cannot create advisories.');
        }

        return view('admin.advisories.create');
    }

    /**
     * Store a newly created advisory in storage.
     * Accessible to: Admin, Staff only
     */
    public function store(Request $request)
    {
        if (auth()->user()->role === 'farmer') {
            abort(403, 'Farmers cannot create advisories.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_audience' => 'required|in:all,farmers,staff,admin',
            'expiry_date' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        Log::info('Advisory store request:', $request->all());

        $userId = auth()->id();
        if (!$userId) {
            Log::warning('No authenticated user found, using fallback user_id = 1');
            $userId = 1;
        }

        $advisory = Advisory::create([
            'title' => $request->title,
            'content' => $request->content,
            'target_audience' => $request->target_audience,
            'expiry_date' => $request->expiry_date,
            'is_active' => $request->is_active ?? true,
            'published_at' => now(),
            'user_id' => $userId,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Advisory published successfully!',
                'data' => $advisory
            ]);
        }

        return redirect()->route('admin.advisories.index')
            ->with('success', 'Advisory published successfully!');
    }

    /**
     * Show the form for editing the specified advisory.
     * Accessible to: Admin, Staff only
     */
    public function edit($id)
    {
        if (auth()->user()->role === 'farmer') {
            abort(403, 'Farmers cannot edit advisories.');
        }

        $advisory = Advisory::with('user')->findOrFail($id);
        return view('admin.advisories.edit', compact('advisory'));
    }

    /**
     * Update the specified advisory in storage.
     * Accessible to: Admin, Staff only
     */
    public function update(Request $request, $id)
    {
        if (auth()->user()->role === 'farmer') {
            abort(403, 'Farmers cannot edit advisories.');
        }

        $advisory = Advisory::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_audience' => 'required|in:all,farmers,staff,admin',
            'expiry_date' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $advisory->update([
            'title' => $request->title,
            'content' => $request->content,
            'target_audience' => $request->target_audience,
            'expiry_date' => $request->expiry_date,
            'is_active' => $request->is_active ?? $advisory->is_active,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Advisory updated successfully!',
                'data' => $advisory
            ]);
        }

        return redirect()->route('admin.advisories.index')
            ->with('success', 'Advisory updated successfully!');
    }

    /**
     * Remove the specified advisory from storage.
     * Accessible to: Admin, Staff only
     */
    public function destroy($id)
    {
        if (auth()->user()->role === 'farmer') {
            abort(403, 'Farmers cannot delete advisories.');
        }

        $advisory = Advisory::findOrFail($id);
        $advisory->delete();

        return redirect()->route('admin.advisories.index')
            ->with('success', 'Advisory deleted successfully!');
    }
}