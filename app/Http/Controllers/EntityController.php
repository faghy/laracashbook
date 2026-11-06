<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EntityController extends Controller
{
    public function storeSelectedEntity(Request $request)
    {
        $request->validate(['entity' => 'required|integer']);

        $selectedEntityId = (int) $request->input('entity');

        // L'utente può selezionare solo le entità a cui è associato
        if (! $request->user()->hasEntity($selectedEntityId)) {
            abort(403, 'Non sei autorizzato a selezionare questa entità.');
        }

        session(['selected_entity_id' => $selectedEntityId]);

        return redirect()->back()->with('success', 'Entità selezionata con successo.');
    }
}
