<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotFarmer
{
    /**
     * Block farmers from staff/admin-only pages (email verification, etc.).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === 'farmer') {
            return redirect()->route('farmer.dashboard');
        }

        return $next($request);
    }
}