<?php

namespace App\Http\Middleware;

use App\Models\Entity;
use Closure;
use Database\Seeders\DemoSeeder;
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
                session(['selected_entity_id' => self::demoEntityId($associatedEntityIds) ?? $user->initialEntityId()]);
            }
        }

        return $next($request);
    }

    /**
     * Sulla demo (DEMO_MODE) l'entità di esempio nella lingua dell'interfaccia, se l'utente la vede.
     */
    public static function demoEntityId(array $associatedEntityIds): ?int
    {
        if (! config('app.demo') || ! $name = DemoSeeder::homeEntityName(app()->getLocale())) {
            return null;
        }

        $id = Entity::whereIn('id', $associatedEntityIds)->where('name', $name)->value('id');

        return $id === null ? null : (int) $id;
    }
}
