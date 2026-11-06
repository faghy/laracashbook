<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900">Dashboard</h2>
        <x-entity-choice :entities="$entities" />
    </x-slot>

    @php
        $mesi = [1 => 'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];
        $mesiBrevi = [1 => 'Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'];
        $fieldClass = 'w-full';
    @endphp

    @if(auth()->user()->role !== 'stagista')
        {{-- Totali generali dell'entità --}}
        <section class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="card p-4 border-l-4 border-l-indigo-500">
                <p class="text-sm font-medium text-slate-600">Totale anno in corso</p>
                <p class="amount text-2xl {{ $totalImporto < 0 ? 'text-rose-700' : 'text-slate-900' }}">@euro($totalImporto)</p>
            </div>
            <div class="card p-4 border-l-4 border-l-violet-500">
                <p class="text-sm font-medium text-slate-600">Totale dall'inizio</p>
                <p class="amount text-2xl {{ $movimentiTot < 0 ? 'text-rose-700' : 'text-slate-900' }}">@euro($movimentiTot)</p>
            </div>
            <div class="card p-4 border-l-4 border-l-emerald-500">
                <p class="text-sm font-medium text-slate-600">Entrate dall'inizio</p>
                <p class="amount text-2xl text-emerald-700">@euro($movimentiTotIn)</p>
            </div>
            <div class="card p-4 border-l-4 border-l-rose-500">
                <p class="text-sm font-medium text-slate-600">Uscite dall'inizio</p>
                <p class="amount text-2xl text-rose-700">@euro(abs($movimentiTotOut))</p>
            </div>
        </section>

        {{-- Filtri del periodo --}}
        <form method="GET" action="{{ route('dashboard') }}" class="card p-4 sm:p-5 mb-6" id="filterForm">
            <h3 class="text-lg font-bold mb-3">Ricerca avanzata</h3>
            <div class="grid grid-cols-3 md:grid-cols-6 gap-3">
                <div>
                    <label for="start_day" class="field-label">Giorno inizio</label>
                    <input type="number" name="start_day" id="start_day" min="1" max="31" value="{{ $startDay }}" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label for="start_month" class="field-label">Mese inizio</label>
                    <select name="start_month" id="start_month" class="{{ $fieldClass }}">
                        @foreach ($mesi as $n => $mese)
                            <option value="{{ $n }}" @selected($startMonth == $n)>{{ $mese }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="start_year" class="field-label">Anno inizio</label>
                    <select name="start_year" id="start_year" class="{{ $fieldClass }}">
                        @foreach($years as $year)
                            <option value="{{ $year }}" @selected($startYear == $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="end_day" class="field-label">Giorno fine</label>
                    <input type="number" name="end_day" id="end_day" min="1" max="31" value="{{ $endDay }}" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label for="end_month" class="field-label">Mese fine</label>
                    <select name="end_month" id="end_month" class="{{ $fieldClass }}">
                        @foreach ($mesi as $n => $mese)
                            <option value="{{ $n }}" @selected($endMonth == $n)>{{ $mese }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="end_year" class="field-label">Anno fine</label>
                    <select name="end_year" id="end_year" class="{{ $fieldClass }}">
                        @foreach($years as $year)
                            <option value="{{ $year }}" @selected($endYear == $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <label for="categories" class="field-label">
                    Categorie <span class="font-normal text-slate-500">(nessuna selezionata = tutte; tieni premuto Ctrl per selezionarne più di una)</span>
                </label>
                <select name="categories[]" id="categories" multiple size="5" class="{{ $fieldClass }} md:w-1/2">
                    @foreach($categories as $category)
                        <option value="{{ $category->cat_id }}" @selected(in_array($category->cat_id, $selectedCategories))>
                            {{ $category->descriz_cat }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mt-4 flex flex-wrap gap-2 items-center">
                <button type="submit" class="btn btn-primary">Filtra</button>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">Reset</a>
                <p class="text-slate-700 md:ml-auto">
                    <strong>Periodo:</strong>
                    {{ \Illuminate\Support\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Illuminate\Support\Carbon::parse($endDate)->format('d/m/Y') }}
                    @if(!empty($selectedCategories))
                        | <strong>Categorie filtrate:</strong> {{ count($selectedCategories) }}
                    @endif
                </p>
            </div>
        </form>

        {{-- Risultati del periodo --}}
        <section class="grid md:grid-cols-3 gap-4 mb-6">
            <div class="rounded-xl shadow-md p-6 text-white bg-linear-to-br from-emerald-600 to-emerald-700">
                <h4 class="text-base font-semibold mb-1 text-white/90">Totale entrate</h4>
                <p class="amount text-4xl">@euro($entrate)</p>
            </div>
            <div class="rounded-xl shadow-md p-6 text-white bg-linear-to-br from-rose-600 to-rose-700">
                <h4 class="text-base font-semibold mb-1 text-white/90">Totale uscite</h4>
                <p class="amount text-4xl">@euro($uscite)</p>
            </div>
            <div class="rounded-xl shadow-md p-6 text-white bg-linear-to-br {{ $saldo >= 0 ? 'from-indigo-600 to-violet-700' : 'from-amber-600 to-orange-700' }}">
                <h4 class="text-base font-semibold mb-1 text-white/90">Saldo</h4>
                <p class="amount text-4xl">@euro($saldo)</p>
            </div>
        </section>

        {{-- Esteri senza IVA nel periodo --}}
        <section class="card p-4 sm:p-5 mb-6 border-l-4 border-l-amber-500">
            <div class="flex flex-wrap items-baseline justify-between gap-2 mb-3">
                <h3 class="text-lg font-bold">
                    Movimenti esteri senza IVA
                    <span class="badge bg-amber-100 text-amber-800 ml-1">{{ $estero->movimenti }} {{ $estero->movimenti == 1 ? 'movimento' : 'movimenti' }}</span>
                </h3>
                <p class="text-sm text-slate-600">Nel periodo e nelle categorie filtrate; già compresi nei totali sopra.</p>
            </div>
            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <p class="text-sm font-medium text-slate-600">Entrate</p>
                    <p class="amount text-2xl text-emerald-700">@euro($estero->entrate)</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-600">Uscite</p>
                    <p class="amount text-2xl text-rose-700">@euro($estero->uscite)</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-600">Saldo</p>
                    <p class="amount text-2xl {{ $estero->entrate - $estero->uscite < 0 ? 'text-rose-700' : 'text-slate-900' }}">@euro($estero->entrate - $estero->uscite)</p>
                </div>
            </div>
        </section>

        {{-- Andamento mensile --}}
        <section class="card p-4 sm:p-5 mb-6">
            <h3 class="text-lg font-bold mb-1">Andamento mensile</h3>
            <p class="text-sm text-slate-600 mb-4">
                <span class="inline-block w-3 h-3 bg-emerald-500 rounded-sm align-middle"></span> Entrate
                <span class="inline-block w-3 h-3 bg-rose-500 rounded-sm align-middle ml-3"></span> Uscite
            </p>
            @if ($monthly->isEmpty())
                <p class="text-slate-500">Nessun movimento nel periodo selezionato.</p>
            @else
                <div class="space-y-3">
                    @foreach ($monthly as $row)
                        <div class="grid grid-cols-[5rem_1fr] gap-3 items-center">
                            <span class="text-sm font-semibold text-slate-700">{{ $mesiBrevi[$row->mese] }} {{ $row->anno }}</span>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2" title="Entrate">
                                    <div class="h-3 bg-emerald-500 rounded-sm" style="width: {{ round($row->entrate / $maxMonthly * 85, 2) }}%"></div>
                                    <span class="text-xs font-medium tabular-nums text-slate-700 whitespace-nowrap">@euro($row->entrate)</span>
                                </div>
                                <div class="flex items-center gap-2" title="Uscite">
                                    <div class="h-3 bg-rose-500 rounded-sm" style="width: {{ round($row->uscite / $maxMonthly * 85, 2) }}%"></div>
                                    <span class="text-xs font-medium tabular-nums text-slate-700 whitespace-nowrap">@euro($row->uscite)</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Riepilogo per categoria --}}
        <section class="card mb-6 overflow-x-auto">
            <h3 class="text-lg font-bold px-4 sm:px-5 pt-4 pb-3">Riepilogo per categoria</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Categoria</th>
                        <th class="text-right">Movimenti</th>
                        <th class="text-right">Entrate</th>
                        <th class="text-right">Uscite</th>
                        <th class="text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($byCategory as $row)
                    <tr>
                        <td class="font-medium">{{ $row->categoria ?? 'N/D' }}</td>
                        <td class="text-right">{{ $row->movimenti }}</td>
                        <td class="text-right amount text-emerald-700">@euro($row->entrate)</td>
                        <td class="text-right amount text-rose-700">@euro($row->uscite)</td>
                        <td class="text-right amount">@euro($row->entrate - $row->uscite)</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-slate-500">Nessun movimento nel periodo selezionato.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
    @endif

    {{-- Utenti dell'entità --}}
    <section class="card overflow-x-auto">
        <h3 class="text-lg font-bold px-4 sm:px-5 pt-4 pb-3">Utenti dell'entità</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Creato il</th>
                    <th class="text-right">Movimenti</th>
                    <th class="text-right">Entità</th>
                </tr>
            </thead>
            <tbody>
            @foreach($users as $user)
                <tr>
                    <td class="font-medium">{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->created_at?->format('d/m/Y') }}</td>
                    <td class="text-right">{{ $user->movimenti_count }}</td>
                    <td class="text-right">{{ $user->user_entities_count }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>
</x-app-layout>
