<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Un utente disattivato viene disconnesso alla richiesta successiva, anche se aveva
 * una sessione aperta o il "ricordami".
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->isDisabled()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('manual.login')->withErrors(['email' => __('This account has been disabled.')]);
        }

        return $next($request);
    }
}
