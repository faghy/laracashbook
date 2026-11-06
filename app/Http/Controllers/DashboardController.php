<?php

namespace App\Http\Controllers;

use App\Models\Cat;
use Illuminate\Http\Request;
use App\Models\Entity;
use App\Models\Movimento;
use App\Models\User;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Il controllo password è gestito dal middleware PasswordProtect
        // Se arriviamo qui, la password è già stata verificata

        $request->validate([
            'start_year' => 'nullable|integer|min:1900|max:2100',
            'end_year' => 'nullable|integer|min:1900|max:2100',
            'start_month' => 'nullable|integer|between:1,12',
            'end_month' => 'nullable|integer|between:1,12',
            'start_day' => 'nullable|integer|between:1,31',
            'end_day' => 'nullable|integer|between:1,31',
            'categories' => 'nullable|array',
            'categories.*' => 'nullable|integer',
        ]);

        // Recupera l'utente autenticato
        $user = auth()->user();

        // Entità selezionata (il middleware InitializeSelectedEntity garantisce che l'utente sia associato)
        $selectedEntityId = session('selected_entity_id');
        $selectedEntity = Entity::find($selectedEntityId);
        $entities = Entity::whereIn('id', $user->entityIds())->get();

        // Movimenti totali dell'entità selezionata
        $entityMovements = Movimento::where('entity_id', $selectedEntityId);
        $movimentiTot = (clone $entityMovements)->sum('importo');
        $movimentiTotIn = (clone $entityMovements)->where('entratauscita', 1)->sum('importo');
        $movimentiTotOut = (clone $entityMovements)->where('entratauscita', 0)->sum('importo');

        // Utenti associati all'entità selezionata, con il numero di movimenti e di entità
        $users = User::whereHas('userEntities', fn ($q) => $q->where('entity_id', $selectedEntityId))
            ->withCount([
                'movimenti' => fn ($q) => $q->where('entity_id', $selectedEntityId),
                'userEntities',
            ])
            ->get();

        // Anno corrente
        $currentYear = Carbon::now()->year;

        // Recupera parametri dal form (con defaults)
        $startYear = (int) $request->input('start_year', $currentYear);
        $endYear = (int) $request->input('end_year', $currentYear);
        $startMonth = (int) $request->input('start_month', 1);
        $endMonth = (int) $request->input('end_month', 12);
        $startDay = (int) $request->input('start_day', 1);
        $endDay = (int) $request->input('end_day', 31);

        // Costruisci le date complete, limitando il giorno alla lunghezza del mese
        // (es. 31 febbraio diventa 28/29 febbraio invece di slittare a marzo)
        $dataInizio = Carbon::create($startYear, $startMonth, 1);
        $dataInizio->day(min($startDay, $dataInizio->daysInMonth))->startOfDay();
        $dataFine = Carbon::create($endYear, $endMonth, 1);
        $dataFine->day(min($endDay, $dataFine->daysInMonth))->endOfDay();

        // Formatta per la query SQL
        $startDate = $dataInizio->toDateString();
        $endDate = $dataFine->toDateString();

        // Categorie selezionate (rimuovi l'opzione vuota "Tutte le categorie" se presente)
        $selectedCategories = array_values(array_filter(
            $request->input('categories', []),
            fn ($cat) => $cat !== null && $cat !== ''
        ));

        // Query per calcolare entrate/uscite nel periodo selezionato
        // Colonne qualificate perché il riepilogo per categoria fa una join con 'categorie'
        $query = Movimento::where('movimenti.entity_id', $selectedEntityId)
            ->whereBetween('movimenti.data_movimento', [$startDate, $endDate]);

        // Filtro per categorie (la colonna nella tabella movimenti si chiama 'categoria')
        if (!empty($selectedCategories)) {
            $query->whereIn('movimenti.categoria', $selectedCategories);
        }

        // Calcola entrate, uscite e saldo
        $entrate = (clone $query)->where('importo', '>', 0)->sum('importo');
        $uscite = abs((clone $query)->where('importo', '<', 0)->sum('importo')); // Valore assoluto per visualizzazione
        $saldo = $entrate - $uscite; // Differenza tra entrate e uscite

        // Totale separato dei movimenti esteri senza IVA nel periodo (compresi nei totali sopra)
        $estero = (clone $query)->where('estero_senza_iva', true)->toBase()
            ->selectRaw('COUNT(*) as movimenti')
            ->selectRaw('COALESCE(SUM(CASE WHEN importo > 0 THEN importo ELSE 0 END), 0) as entrate')
            ->selectRaw('COALESCE(SUM(CASE WHEN importo < 0 THEN -importo ELSE 0 END), 0) as uscite')
            ->first();

        // Andamento mensile del periodo selezionato
        $monthly = (clone $query)
            ->selectRaw('YEAR(data_movimento) as anno, MONTH(data_movimento) as mese')
            ->selectRaw('SUM(CASE WHEN importo > 0 THEN importo ELSE 0 END) as entrate')
            ->selectRaw('SUM(CASE WHEN importo < 0 THEN -importo ELSE 0 END) as uscite')
            ->groupBy('anno', 'mese')
            ->orderBy('anno')
            ->orderBy('mese')
            ->get();
        $maxMonthly = max(1, (float) $monthly->max('entrate'), (float) $monthly->max('uscite'));

        // Riepilogo per categoria del periodo selezionato
        $byCategory = (clone $query)
            ->leftJoin('categorie', 'categorie.cat_id', '=', 'movimenti.categoria')
            ->selectRaw('categorie.descriz_cat as categoria')
            ->selectRaw('SUM(CASE WHEN importo > 0 THEN importo ELSE 0 END) as entrate')
            ->selectRaw('SUM(CASE WHEN importo < 0 THEN -importo ELSE 0 END) as uscite')
            ->selectRaw('COUNT(*) as movimenti')
            ->groupBy('categorie.cat_id', 'categorie.descriz_cat')
            ->orderByRaw('SUM(ABS(importo)) DESC')
            ->get();

        // Anno selezionato (mantenuto per retrocompatibilità)
        $selectedYear = $startYear;

        // Anni disponibili per il dropdown (dall'entità)
        $years = Movimento::where('entity_id', $selectedEntityId)
            ->selectRaw('YEAR(data_movimento) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            // L'anno corrente deve essere sempre selezionabile, anche senza movimenti
            ->push($currentYear)
            ->unique()
            ->sortDesc()
            ->values();

        // Categorie disponibili per il filtro
        $categories = Cat::where('entity_id', $selectedEntityId)->orderBy('descriz_cat')->get();

        // Totale importo anno corrente
        $totalImporto = Movimento::where('entity_id', $selectedEntityId)
            ->whereYear('data_movimento', $currentYear)
            ->sum('importo');

        return view('dashboard', compact(
            'users',
            'entrate',
            'uscite',
            'saldo',
            'estero',
            'selectedEntity',
            'years',
            'startYear',
            'endYear',
            'startDay',
            'endDay',
            'startMonth',
            'endMonth',
            'selectedYear',
            'startDate',
            'endDate',
            'categories',
            'entities',
            'totalImporto',
            'movimentiTot',
            'movimentiTotIn',
            'movimentiTotOut',
            'selectedCategories',
            'monthly',
            'maxMonthly',
            'byCategory'
        ));
    }
}
