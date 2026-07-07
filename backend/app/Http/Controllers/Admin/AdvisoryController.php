<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advisory;
use Illuminate\Http\Request;

class AdvisoryController extends Controller
{
    public function index()
    {
        $advisories = Advisory::orderBy('created_at', 'desc')->get();
        return view('admin.advisories.index', compact('advisories'));
    }

    public function create()
    {
        return view('admin.advisories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_audience' => 'required|in:all,farmers,staff,admin',
        ]);

        Advisory::create([
            'title' => $request->title,
            'content' => $request->content,
            'target_audience' => $request->target_audience,
            'published_at' => now(),
        ]);

        return redirect()->route('admin.advisories.index')
            ->with('success', 'Advisory published successfully! 📢');
    }

    public function edit($id)
    {
        $advisory = Advisory::findOrFail($id);
        return view('admin.advisories.edit', compact('advisory'));
    }

    public function update(Request $request, $id)
    {
        $advisory = Advisory::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_audience' => 'required|in:all,farmers,staff,admin',
        ]);

        $advisory->update($request->all());

        return redirect()->route('admin.advisories.index')
            ->with('success', 'Advisory updated successfully! 🔄');
    }

    public function destroy($id)
    {
        Advisory::findOrFail($id)->delete();
        return redirect()->route('admin.advisories.index')
            ->with('success', 'Advisory deleted successfully! 🗑️');
    }

    public function toggleActive($id)
    {
        $advisory = Advisory::findOrFail($id);
        $advisory->is_active = !$advisory->is_active;
        $advisory->save();

        return back()->with('success', 'Advisory status updated!');
    }
}