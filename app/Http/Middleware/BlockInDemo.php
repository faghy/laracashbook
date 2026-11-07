<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * In demo mode (DEMO_MODE=true) blocks the actions that would change or lock the shared
 * demo account: profile, password, account deletion and password reset.
 */
class BlockInDemo
{
    public function handle(Request $request, Closure $next)
    {
        if (config('app.demo')) {
            return back()->with('error', __('This action is disabled in the demo.'));
        }

        return $next($request);
    }
}
