<?php

namespace App\Http\Controllers;

use App\Models\Cat;
use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Tutte le query sono limitate all'entità selezionata in sessione: il middleware
 * InitializeSelectedEntity garantisce che l'utente sia associato a quell'entità.
 */
class CatController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $cats = Cat::forSelectedEntity()->orderBy('descriz_cat', 'ASC')->paginate(20);

        return view('movimenti.categorie',[
            'entities' => Entity::whereIn('id', auth()->user()->entityIds())->get(),
            'cats' => $cats
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('movimenti.creaCat');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Recupero l'identità corrente
        $selectedEntityId = session('selected_entity_id');

        // Valida i dati del form
        $validatedData = $request->validate([
            'descriz_cat' => [
                'required', 'string', 'max:255',
                Rule::unique('categorie', 'descriz_cat')->where('entity_id', $selectedEntityId),
            ],
        ], [
            'descriz_cat.required' => 'Inserisci il nome della categoria.',
            'descriz_cat.unique' => 'La categoria inserita già esiste.',
        ]);

        try {
            // Crea una nuova categoria con i dati validati
            $categoria = new Cat($validatedData);

            // Assegna l'entity_id corrente
            $categoria->entity_id = $selectedEntityId;
            $categoria->estero_senza_iva = $request->boolean('estero_senza_iva');

            // Salva la categoria
            if ($categoria->save()) {
                if ($request->input('source') === 'movimenti.create') {
                    return redirect()->route('movimenti.create')->with('categoria_created', 'La categoria è stata creata con successo.');
                } else {
                    return redirect()->route('categoria.index')->with('categoria_created', 'La categoria è stata creata con successo.');
                }
            } else {
                // Gestisci il caso in cui il salvataggio fallisce
                return redirect()->route('categoria.index')->with('categoria_created_error', 'Si è verificato un errore durante il salvataggio della categoria.');
            }
        } catch (\Exception $e) {
            // Gestisci eventuali eccezioni o errori
            return redirect()->route('categoria.index')->with('categoria_created_error', 'Si è verificato un errore durante il salvataggio della categoria.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(int $id)
    {
        $cat = Cat::forSelectedEntity()->findOrFail($id);

        return view('movimenti.editCat',[
            'cats' => $cat
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, int $id)
    {
        $cat = Cat::forSelectedEntity()->findOrFail($id);

        $validatedData = $request->validate([
            'descriz' => [
                'required', 'string', 'max:255',
                Rule::unique('categorie', 'descriz_cat')
                    ->where('entity_id', $cat->entity_id)
                    ->ignore($cat->cat_id, 'cat_id'),
            ],
        ], [
            'descriz.required' => 'Inserisci il nome della categoria.',
            'descriz.unique' => 'La categoria inserita già esiste.',
        ]);

        $cat->update(['descriz_cat' => $validatedData['descriz']]);
        $cat->forceFill(['estero_senza_iva' => $request->boolean('estero_senza_iva')])->save();

        return redirect()->route('categoria.index')->with('status', 'La categoria è stata aggiornata con successo!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(int $id)
    {
        $categoria = Cat::forSelectedEntity()->findOrFail($id);

        try {
            // Verifica se ci sono movimenti collegati alla categoria
            if ($categoria->movimenti()->withTrashed()->exists()) {
                return redirect('/categoria')->with('error', 'Impossibile eliminare la categoria. È collegata a movimenti (anche nel cestino).');
            }

            $categoria->delete();

            // Reindirizza con un messaggio di successo
            return redirect('/categoria')->with('status', 'La categoria è stata eliminata con successo!');
        } catch (\Exception $e) {
            // Gestisci eventuali eccezioni o errori
            return redirect('/categoria')->with('error', 'Si è verificato un errore durante l eliminazione della categoria');
        }
    }
}
