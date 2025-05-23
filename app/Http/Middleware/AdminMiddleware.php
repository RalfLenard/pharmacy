<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle($request, Closure $next)
    {
        // Check if user is authenticated and has the role 'admin'
        if (Auth::check() && Auth::user()->usertype === 'admin') {
            return $next($request);
        }

        // Optionally redirect or abort
        return redirect('/dashboard')->with('error', 'Unauthorized access');
        // or use: abort(403, 'Unauthorized');
    }
}

