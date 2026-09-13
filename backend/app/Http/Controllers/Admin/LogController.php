<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class LogController extends Controller
{
    /**
     * Display a listing of activity logs.
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('user');

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Date range
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(20);

        // For filter dropdowns
        $users = User::orderBy('name')->pluck('name', 'id');
        $actions = ActivityLog::distinct()->pluck('action');

        return view('admin.logs.index', compact('logs', 'users', 'actions'));
    }

    /**
     * Display a single log entry.
     */
    public function show($id)
{
    $log = ActivityLog::with('user')->findOrFail($id);

    if (request()->ajax()) {
        return view('admin.logs._show', compact('log'));
    }

    // Fallback full page view (if you want to keep it)
    return view('admin.logs.show', compact('log'));
}

    /**
     * Clear logs older than given days.
     */
    public function clear(Request $request)
    {
        $days = $request->input('days', 30);
        ActivityLog::where('created_at', '<', now()->subDays($days))->delete();

        return redirect()->route('admin.logs.index')
            ->with('success', "Logs older than {$days} days cleared.");
    }
}