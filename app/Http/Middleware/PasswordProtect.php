<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PasswordProtect
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Se la password è già stata verificata in una richiesta precedente, continua
        if (session('password_verified', false)) {
            return $next($request);
        }
        // Se è una POST con password (form di verifica)
        if ($request->isMethod('post') && $request->has('password')) {
            $password = (string) config('app.dashboard_password');
            $inputPassword = (string) $request->input('password');

            // Nessuna password configurata: accesso negato invece di usare un valore di default
            if ($password === '') {
                return back()->with('password_error', __('Dashboard access is not configured.'));
            }

            if (hash_equals($password, $inputPassword)) {
                // Password corretta: memorizza in sessione
                $request->session()->regenerate();
                session(['password_verified' => true]);

                // Redirect alla dashboard con GET (pulisce il POST)
                return redirect()->route('dashboard');
            } else {
                // Password errata: torna al form con errore
                return back()->with('password_error', __('Wrong password.'));
            }
        }

        // Se non è autenticato, mostra il form password
        return response()->view('password_form');
    }
}
