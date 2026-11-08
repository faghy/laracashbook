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
                // Il superadmin di un'installazione nuova crea prima un'entità
                if ($user->isSuperadmin()) {
                    return redirect()->route('entita.index')->with('status', __('Create the first entity to start.'));
                }

                abort(403, __('Your user is not linked to any entity.'));
            }

            // Se in sessione non c'è un'entità valida (es. dopo il login), usa quella predefinita dell'utente
            if (! in_array((int) session('selected_entity_id'), $associatedEntityIds, true)) {
                session(['selected_entity_id' => $user->initialEntityId()]);
            }
        }

        return $next($request);
    }
}
