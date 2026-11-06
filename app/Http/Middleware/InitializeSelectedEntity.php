<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InitializeSelectedEntity
{
    /**
     * Garantisce che in sessione ci sia sempre un'entità a cui l'utente è associato.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user) {
            $associatedEntityIds = $user->entityIds();

            if (empty($associatedEntityIds)) {
                abort(403, 'Nessuna entità associata al tuo utente.');
            }

            // Se in sessione non c'è un'entità valida (es. dopo il login), usa quella predefinita dell'utente
            if (! in_array((int) session('selected_entity_id'), $associatedEntityIds, true)) {
                session(['selected_entity_id' => $user->initialEntityId()]);
            }
        }

        return $next($request);
    }
}
