<?php

namespace App\Http\Controllers;

use App\Http\Requests\MovimentoRequest;
use App\Models\Cat;
use App\Models\Entity;
use App\Models\Movimento;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class MovimentoController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Movimento::class);

        $filters = $this->validatedFilters($request);
        $query = Movimento::forSelectedEntity()->filter($filters);
        $day = $this->dayTotals($query, $filters);

        $movs = $query->with('categorie')
            ->orderBy('data_movimento', 'DESC')
            ->orderBy('id', 'DESC')
            ->paginate(20)
            ->withQueryString();

        return view('movimenti.movimenti', [
            'movs' => $movs,
            'day' => $day,
            'entities' => $this->userEntities(),
            'cats' => Cat::forSelectedEntity()->orderBy('descriz_cat')->get(),
            'filters' => $filters,
        ]);
    }

    public function today()
    {
        return $this->redirectToDay(Carbon::today());
    }

    public function yesterday()
    {
        return $this->redirectToDay(Carbon::yesterday());
    }

    public function beforeyesterday()
    {
        return $this->redirectToDay(Carbon::today()->subDays(2));
    }

    /**
     * La ricerca per data è ora un filtro dell'elenco movimenti.
     */
    public function cercadata(Request $request)
    {
        $request->validate(['data' => 'nullable|date']);

        return $request->filled('data')
            ? $this->redirectToDay(Carbon::parse($request->input('data')))
            : redirect()->route('movimenti.index');
    }

    /**
     * Totali di entrate, uscite e saldo quando l'elenco mostra una sola giornata (Oggi, Ieri, ricerca per data).
     */
    private function dayTotals($query, array $filters): ?array
    {
        if (empty($filters['dal']) || ($filters['dal'] ?? null) !== ($filters['al'] ?? null)) {
            return null;
        }

        $date = Carbon::parse($filters['dal']);
        $sums = (clone $query)->toBase()
            ->selectRaw('COALESCE(SUM(CASE WHEN entratauscita = 1 THEN ABS(importo) ELSE 0 END), 0) as entrate')
            ->selectRaw('COALESCE(SUM(CASE WHEN entratauscita = 0 THEN ABS(importo) ELSE 0 END), 0) as uscite')
            ->first();

        return [
            'label' => match (true) {
                $date->isToday() => __('Today'),
                $date->isYesterday() => __('Yesterday'),
                default => ucfirst($date->locale(app()->getLocale())->isoFormat('dddd')),
            },
            'date' => $date->locale(app()->getLocale())->isoFormat('LL'),
            'entrate' => (float) $sums->entrate,
            'uscite' => (float) $sums->uscite,
            'saldo' => (float) $sums->entrate - (float) $sums->uscite,
        ];
    }

    private function redirectToDay(Carbon $date)
    {
        $day = $date->toDateString();

        return redirect()->route('movimenti.index', ['dal' => $day, 'al' => $day]);
    }

    /**
     * Esporta in CSV i movimenti filtrati. In italiano usa il formato di Excel italiano
     * (separatore ";" e virgola decimale), nelle altre lingue "," e punto decimale.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Movimento::class);

        $filters = $this->validatedFilters($request);
        $entity = Entity::find(session('selected_entity_id'));
        $filename = str(__('movements'))->slug().'-'.str($entity->name ?? 'entity')->slug().'-'.now()->format('Y-m-d').'.csv';
        $italian = app()->getLocale() === 'it';
        $separator = $italian ? ';' : ',';
        $decimal = $italian ? ',' : '.';

        $query = Movimento::forSelectedEntity()->filter($filters)
            ->with('categorie')
            ->orderBy('data_movimento')
            ->orderBy('id');

        return response()->streamDownload(function () use ($query, $separator, $decimal) {
            $out = fopen('php://output', 'w');
            // BOM per far riconoscere a Excel la codifica UTF-8
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [__('Date'), __('Type'), __('Category'), __('Description'), __('Amount'), __('Foreign, VAT exempt')], $separator);

            foreach ($query->lazy() as $mov) {
                fputcsv($out, [
                    $mov->data_movimento->format('d/m/Y'),
                    $mov->entratauscita ? ucfirst(__('income')) : ucfirst(__('expense')),
                    optional($mov->categorie)->descriz_cat,
                    $mov->descrizione,
                    number_format((float) $mov->importo, 2, $decimal, ''),
                    $mov->estero_senza_iva ? __('Yes') : __('No'),
                ], $separator);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function create()
    {
        $this->authorize('create', Movimento::class);

        return view('movimenti.createmovimento', [
            'cats' => Cat::forSelectedEntity()->orderBy('descriz_cat', 'ASC')->get(),
        ]);
    }

    public function store(MovimentoRequest $request)
    {
        $this->authorize('create', Movimento::class);
        $validatedData = $request->validated();

        Movimento::create([
            'user_id' => Auth::id(),
            'entity_id' => session('selected_entity_id'),
            'entratauscita' => $validatedData['entratauscita'],
            'categoria' => $validatedData['categoria'],
            'descrizione' => $validatedData['descrizione'],
            'importo' => $validatedData['importo'],
            'data_movimento' => $validatedData['data'] ?? now()->toDateString(),
            'estero_senza_iva' => $request->boolean('estero_senza_iva'),
        ]);

        return redirect()->route('movimenti.index')->with('status', __('Movement created.'));
    }

    public function edit($id)
    {
        $movimento = Movimento::find($id);

        if (! $movimento) {
            abort(404, "ERRORE: Il movimento con ID $id non è stato trovato nel database.");
        }

        $selectedEntityId = session('selected_entity_id');

        if ($movimento->entity_id != $selectedEntityId) {
            $actualEntityName = Entity::find($movimento->entity_id)->name ?? 'Sconosciuta';
            $selectedEntityName = Entity::find($selectedEntityId)->name ?? 'Sconosciuta';
            $errorMessage = "ACCESSO NEGATO: Stai provando a modificare un movimento che appartiene all'entità '$actualEntityName' (ID: $movimento->entity_id), ma la tua entità attiva è '$selectedEntityName' (ID: $selectedEntityId). Per modificare questo movimento, seleziona l'entità corretta dal menu in alto.";
            abort(403, $errorMessage);
        }

        $this->authorize('update', $movimento);

        return view('movimenti.editmovimenti', [
            'movimenta' => $movimento,
            'cats' => Cat::forSelectedEntity()->orderBy('descriz_cat', 'ASC')->get(),
        ]);
    }

    public function update(MovimentoRequest $request, $id)
    {
        $movimento = Movimento::findOrFail($id);
        $this->authorize('update', $movimento);

        $validatedData = $request->validated();

        $movimento->update([
            'entratauscita' => $validatedData['entratauscita'],
            'categoria' => $validatedData['categoria'],
            'descrizione' => $validatedData['descrizione'],
            'importo' => $validatedData['importo'],
            'data_movimento' => $validatedData['data'] ?? $movimento->data_movimento,
            'estero_senza_iva' => $request->boolean('estero_senza_iva'),
        ]);

        return redirect()->route('movimenti.index')->with('status', __('Movement updated.'));
    }

    /**
     * Sposta il movimento nel cestino (soft delete).
     */
    public function destroy($id)
    {
        $movimento = Movimento::findOrFail($id);
        $this->authorize('delete', $movimento);

        $movimento->delete();

        return redirect()->back()->with('status', __('The movement has been moved to the trash.'));
    }

    /**
     * Elenco dei movimenti nel cestino.
     */
    public function trash()
    {
        $this->authorize('viewAny', Movimento::class);

        $movs = Movimento::onlyTrashed()->forSelectedEntity()
            ->with('categorie')
            ->orderBy('deleted_at', 'DESC')
            ->paginate(20);

        return view('movimenti.cestino', [
            'movs' => $movs,
            'entities' => $this->userEntities(),
        ]);
    }

    public function restore($id)
    {
        $movimento = Movimento::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $movimento);

        $movimento->restore();

        return redirect()->route('movimenti.cestino')->with('status', __('The movement has been restored.'));
    }

    public function forceDestroy($id)
    {
        $movimento = Movimento::onlyTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $movimento);

        $movimento->forceDelete();

        return redirect()->route('movimenti.cestino')->with('status', __('The movement has been deleted for good.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'q' => 'nullable|string|max:255',
            'dal' => 'nullable|date',
            'al' => 'nullable|date',
            'categoria' => 'nullable|integer',
            'tipo' => 'nullable|in:0,1',
            'estero' => 'nullable|boolean',
        ]);
    }

    private function userEntities()
    {
        return Entity::whereIn('id', Auth::user()->entityIds())->get();
    }
}
