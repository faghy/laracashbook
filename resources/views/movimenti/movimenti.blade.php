<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900">Movimenti</h2>
        <x-entity-choice :entities="$entities" />
    </x-slot>

    {{-- Filtri --}}
    <form method="GET" action="{{ route('movimenti.index') }}" class="card p-4 mb-5 grid grid-cols-2 md:grid-cols-6 gap-3 items-end">
        <div class="col-span-2">
            <label for="q" class="field-label">Cerca nella descrizione</label>
            <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}" class="w-full" placeholder="Es. fattura, affitto…">
        </div>
        <div>
            <label for="dal" class="field-label">Dal</label>
            <input type="date" name="dal" id="dal" value="{{ $filters['dal'] ?? '' }}" class="w-full">
        </div>
        <div>
            <label for="al" class="field-label">Al</label>
            <input type="date" name="al" id="al" value="{{ $filters['al'] ?? '' }}" class="w-full">
        </div>
        <div>
            <label for="categoria" class="field-label">Categoria</label>
            <select name="categoria" id="categoria" class="w-full">
                <option value="">Tutte</option>
                @foreach ($cats as $cat)
                    <option value="{{ $cat->cat_id }}" @selected(($filters['categoria'] ?? null) == $cat->cat_id)>{{ $cat->descriz_cat }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="tipo" class="field-label">Tipo</label>
            <select name="tipo" id="tipo" class="w-full">
                <option value="">Tutti</option>
                <option value="1" @selected(($filters['tipo'] ?? null) === '1')>Entrate</option>
                <option value="0" @selected(($filters['tipo'] ?? null) === '0')>Uscite</option>
            </select>
        </div>
        <div class="col-span-2 md:col-span-6 flex flex-wrap items-center gap-x-4 gap-y-3">
            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 cursor-pointer">
                <input type="checkbox" name="estero" value="1" @checked($filters['estero'] ?? false)>
                Solo movimenti esteri senza IVA
            </label>
            <div class="flex flex-wrap gap-2 md:ml-auto">
                <button type="submit" class="btn btn-primary">Filtra</button>
                <a href="{{ route('movimenti.index') }}" class="btn btn-secondary">Azzera</a>
                <a href="{{ route('movimenti.export', request()->query()) }}" class="btn btn-success">Esporta CSV</a>
            </div>
        </div>
    </form>

    @if ($day)
        {{-- Totali della giornata (Oggi, Ieri, ricerca per data) --}}
        <section class="mb-5" aria-labelledby="day-title">
            <h3 id="day-title" class="text-lg font-semibold text-slate-900 mb-3">
                {{ $day['label'] }} <span class="font-normal text-slate-500">· {{ $day['date'] }}</span>
            </h3>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="card p-5 border-t-4 border-t-emerald-500">
                    <p class="text-sm font-medium text-slate-600">Entrate</p>
                    <p class="amount text-3xl sm:text-4xl font-bold text-emerald-700">@euro($day['entrate'])</p>
                </div>
                <div class="card p-5 border-t-4 border-t-rose-500">
                    <p class="text-sm font-medium text-slate-600">Uscite</p>
                    <p class="amount text-3xl sm:text-4xl font-bold text-rose-700">@euro($day['uscite'])</p>
                </div>
                <div class="card p-5 border-t-4 border-t-indigo-500">
                    <p class="text-sm font-medium text-slate-600">Saldo della giornata</p>
                    <p class="amount text-3xl sm:text-4xl font-bold {{ $day['saldo'] < 0 ? 'text-rose-700' : 'text-slate-900' }}">@euro($day['saldo'])</p>
                </div>
            </div>
        </section>
    @endif

    <p class="text-sm font-medium text-slate-600 mb-2">{{ $movs->total() }} movimenti</p>

    {{-- Desktop: tabella --}}
    <div class="card overflow-hidden hidden md:block">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Data</th>
                    <th scope="col">Descrizione</th>
                    <th scope="col">Categoria</th>
                    <th scope="col" class="text-right">Importo</th>
                    <th scope="col" class="text-right"><span class="sr-only">Azioni</span></th>
                </tr>
            </thead>
            <tbody>
            @forelse($movs as $mov)
                <tr>
                    <td class="whitespace-nowrap tabular-nums text-slate-600">
                        <time datetime="{{ $mov->data_movimento->toDateString() }}">{{ $mov->data_movimento->format('d/m/Y') }}</time>
                    </td>
                    <td>
                        <span class="font-medium">{{ $mov->descrizione }}</span>
                        @if ($mov->estero_senza_iva)
                            <span class="badge bg-amber-100 text-amber-800 ml-1" title="Movimento estero senza IVA">Estero s/IVA</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-slate-100 text-slate-700 text-sm font-medium">{{ optional($mov->categorie)->descriz_cat ?? 'N/D' }}</span>
                    </td>
                    <td class="text-right">
                        <span class="amount {{ $mov->entratauscita ? 'text-emerald-700' : 'text-rose-700' }}">
                            <span aria-hidden="true">{{ $mov->entratauscita ? '▲' : '▼' }}</span>
                            @euro($mov->entratauscita ? abs($mov->importo) : -abs($mov->importo))
                        </span>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('movimenti.destroy', $mov->id) }}" class="flex justify-end gap-1">
                            @csrf
                            @method('delete')
                            <a href="{{ route('movimenti.edit', $mov->id) }}" class="btn btn-sm btn-secondary">Modifica</a>
                            <button type="submit"
                                    data-confirm="Spostare nel cestino il movimento {{ $mov->descrizione }}?"
                                    onclick="return confirm(this.dataset.confirm)"
                                    class="btn btn-sm btn-ghost-danger">Elimina</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-10 text-center text-slate-500">Nessun movimento trovato.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile: schede --}}
    <ul class="md:hidden space-y-3">
        @forelse($movs as $mov)
            <li class="card p-4 border-l-4 {{ $mov->entratauscita ? 'border-l-emerald-500' : 'border-l-rose-500' }}">
                <div class="flex justify-between items-start gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold break-words">{{ $mov->descrizione }}</p>
                        <p class="text-sm text-slate-600 mt-0.5">
                            {{ $mov->data_movimento->format('d/m/Y') }} · {{ optional($mov->categorie)->descriz_cat ?? 'N/D' }}
                        </p>
                        @if ($mov->estero_senza_iva)
                            <span class="badge bg-amber-100 text-amber-800 mt-1">Estero s/IVA</span>
                        @endif
                    </div>
                    <span class="amount text-lg {{ $mov->entratauscita ? 'text-emerald-700' : 'text-rose-700' }}">
                        @euro($mov->entratauscita ? abs($mov->importo) : -abs($mov->importo))
                    </span>
                </div>
                <form method="POST" action="{{ route('movimenti.destroy', $mov->id) }}" class="flex justify-end gap-2 mt-3">
                    @csrf
                    @method('delete')
                    <a href="{{ route('movimenti.edit', $mov->id) }}" class="btn btn-sm btn-secondary">Modifica</a>
                    <button type="submit"
                            data-confirm="Spostare nel cestino il movimento {{ $mov->descrizione }}?"
                            onclick="return confirm(this.dataset.confirm)"
                            class="btn btn-sm btn-ghost-danger">Elimina</button>
                </form>
            </li>
        @empty
            <li class="card p-6 text-center text-slate-500">Nessun movimento trovato.</li>
        @endforelse
    </ul>

    <div class="mt-4">{{ $movs->links() }}</div>
</x-app-layout>
