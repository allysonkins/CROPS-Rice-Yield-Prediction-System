<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCaoVerified
{
    /**
     * Block write actions from farmers who haven't been CAO-verified.
     * Read-only routes are unaffected.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Only applies to farmers
        if (!$user || $user->role !== 'farmer') {
            return $next($request);
        }

        // Verified farmers pass through
        if ($user->verified_by_cao_at !== null) {
            return $next($request);
        }

        // ── Unverified farmer trying to write ──
        $message = 'Your account is awaiting CAO verification. You can view your data but cannot add or modify records yet.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'error'   => $message,
                'code'    => 'CAO_VERIFICATION_REQUIRED',
            ], 403);
        }

        return redirect()
            ->route('farmer.dashboard')
            ->with('verification_required', $message);
    }
}