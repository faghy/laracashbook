<?php

namespace App\Http\Controllers;

use App\Models\Cat;
use App\Models\Entity;
use App\Models\Movimento;
use App\Models\UserEntity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EntityController extends Controller
{
    public function storeSelectedEntity(Request $request)
    {
        $request->validate(['entity' => 'required|integer']);

        $selectedEntityId = (int) $request->input('entity');

        // L'utente può selezionare solo le entità a cui è associato
        if (! $request->user()->hasEntity($selectedEntityId)) {
            abort(403, __('You are not allowed to select this entity.'));
        }

        session(['selected_entity_id' => $selectedEntityId]);

        return redirect()->back()->with('success', __('Entity selected.'));
    }

    /**
     * Elenco delle entità (solo superadmin), con il numero di utenti, categorie e movimenti.
     */
    public function index()
    {
        return view('entita.index', [
            'entities' => Entity::orderBy('name')->get()->map(fn (Entity $entity) => tap($entity, function ($e) {
                $e->users_count = UserEntity::where('entity_id', $e->id)->count();
                $e->categories_count = Cat::where('entity_id', $e->id)->count();
                $e->movements_count = Movimento::withTrashed()->where('entity_id', $e->id)->count();
            })),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255|unique:entities,name']);
        Entity::create($data);

        return redirect()->route('entita.index')->with('status', __('Entity created.'));
    }

    public function update(Request $request, Entity $entity)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255', Rule::unique('entities', 'name')->ignore($entity->id)]]);
        $entity->update($data);

        return redirect()->route('entita.index')->with('status', __('Entity renamed.'));
    }

    /**
     * Si elimina solo un'entità senza categorie né movimenti (anche nel cestino).
     */
    public function destroy(Entity $entity)
    {
        if (Cat::where('entity_id', $entity->id)->exists() || Movimento::withTrashed()->where('entity_id', $entity->id)->exists()) {
            return redirect()->route('entita.index')->with('error', __('Only an entity without categories and movements can be deleted.'));
        }

        UserEntity::where('entity_id', $entity->id)->delete();
        $entity->delete();

        return redirect()->route('entita.index')->with('status', __('Entity deleted.'));
    }
}
