<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900">{{ __('Movements') }}</h2>
        <x-entity-choice :entities="$entities" />
    </x-slot>

    {{-- Filtri --}}
    <form method="GET" action="{{ route('movimenti.index') }}" class="card p-4 mb-5 grid grid-cols-2 md:grid-cols-6 gap-3 items-end">
        <div class="col-span-2">
            <label for="q" class="field-label">{{ __('Search description') }}</label>
            <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}" class="w-full" placeholder="{{ __('E.g. invoice, rent…') }}">
        </div>
        <div>
            <label for="dal" class="field-label">{{ __('From') }}</label>
            <input type="date" name="dal" id="dal" value="{{ $filters['dal'] ?? '' }}" class="w-full">
        </div>
        <div>
            <label for="al" class="field-label">{{ __('To') }}</label>
            <input type="date" name="al" id="al" value="{{ $filters['al'] ?? '' }}" class="w-full">
        </div>
        <div>
            <label for="categoria" class="field-label">{{ __('Category') }}</label>
            <select name="categoria" id="categoria" class="w-full">
                <option value="">{{ __('All categories') }}</option>
                @foreach ($cats as $cat)
                    <option value="{{ $cat->cat_id }}" @selected(($filters['categoria'] ?? null) == $cat->cat_id)>{{ $cat->descriz_cat }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="tipo" class="field-label">{{ __('Type') }}</label>
            <select name="tipo" id="tipo" class="w-full">
                <option value="">{{ __('All') }}</option>
                <option value="1" @selected(($filters['tipo'] ?? null) === '1')>{{ __('Income') }}</option>
                <option value="0" @selected(($filters['tipo'] ?? null) === '0')>{{ __('Expenses') }}</option>
            </select>
        </div>
        <div class="col-span-2 md:col-span-6 flex flex-wrap items-center gap-x-4 gap-y-3">
            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 cursor-pointer">
                <input type="checkbox" name="estero" value="1" @checked($filters['estero'] ?? false)>
                {{ __('Only foreign VAT-exempt movements') }}
            </label>
            <div class="flex flex-wrap gap-2 md:ml-auto">
                <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                <a href="{{ route('movimenti.index') }}" class="btn btn-secondary">{{ __('Clear') }}</a>
                <a href="{{ route('movimenti.export', request()->query()) }}" class="btn btn-success">{{ __('Export CSV') }}</a>
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
                    <p class="text-sm font-medium text-slate-600">{{ __('Income') }}</p>
                    <p class="amount text-3xl sm:text-4xl font-bold text-emerald-700">@euro($day['entrate'])</p>
                </div>
                <div class="card p-5 border-t-4 border-t-rose-500">
                    <p class="text-sm font-medium text-slate-600">{{ __('Expenses') }}</p>
                    <p class="amount text-3xl sm:text-4xl font-bold text-rose-700">@euro($day['uscite'])</p>
                </div>
                <div class="card p-5 border-t-4 border-t-indigo-500">
                    <p class="text-sm font-medium text-slate-600">{{ __('Balance of the day') }}</p>
                    <p class="amount text-3xl sm:text-4xl font-bold {{ $day['saldo'] < 0 ? 'text-rose-700' : 'text-slate-900' }}">@euro($day['saldo'])</p>
                </div>
            </div>
        </section>
    @endif

    <p class="text-sm font-medium text-slate-600 mb-2">{{ trans_choice(':count movement|:count movements', $movs->total()) }}</p>

    {{-- Desktop: tabella --}}
    <div class="card overflow-hidden hidden md:block">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">{{ __('Date') }}</th>
                    <th scope="col">{{ __('Description') }}</th>
                    <th scope="col">{{ __('Category') }}</th>
                    <th scope="col" class="text-right">{{ __('Amount') }}</th>
                    <th scope="col" class="text-right"><span class="sr-only">{{ __('Actions') }}</span></th>
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
                            <span class="badge bg-amber-100 text-amber-800 ml-1" title="{{ __('Foreign movement, VAT exempt') }}">{{ __('Foreign, no VAT') }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-slate-100 text-slate-700 text-sm font-medium">{{ optional($mov->categorie)->descriz_cat ?? __('N/A') }}</span>
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
                            <a href="{{ route('movimenti.edit', $mov->id) }}" class="btn btn-sm btn-secondary">{{ __('Edit') }}</a>
                            <button type="submit"
                                    data-confirm="{{ __('Move the movement :name to the trash?', ['name' => $mov->descrizione]) }}"
                                    onclick="return confirm(this.dataset.confirm)"
                                    class="btn btn-sm btn-ghost-danger">{{ __('Delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-10 text-center text-slate-500">{{ __('No movements found.') }}</td>
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
                            {{ $mov->data_movimento->format('d/m/Y') }} · {{ optional($mov->categorie)->descriz_cat ?? __('N/A') }}
                        </p>
                        @if ($mov->estero_senza_iva)
                            <span class="badge bg-amber-100 text-amber-800 mt-1">{{ __('Foreign, no VAT') }}</span>
                        @endif
                    </div>
                    <span class="amount text-lg {{ $mov->entratauscita ? 'text-emerald-700' : 'text-rose-700' }}">
                        @euro($mov->entratauscita ? abs($mov->importo) : -abs($mov->importo))
                    </span>
                </div>
                <form method="POST" action="{{ route('movimenti.destroy', $mov->id) }}" class="flex justify-end gap-2 mt-3">
                    @csrf
                    @method('delete')
                    <a href="{{ route('movimenti.edit', $mov->id) }}" class="btn btn-sm btn-secondary">{{ __('Edit') }}</a>
                    <button type="submit"
                            data-confirm="{{ __('Move the movement :name to the trash?', ['name' => $mov->descrizione]) }}"
                            onclick="return confirm(this.dataset.confirm)"
                            class="btn btn-sm btn-ghost-danger">{{ __('Delete') }}</button>
                </form>
            </li>
        @empty
            <li class="card p-6 text-center text-slate-500">{{ __('No movements found.') }}</li>
        @endforelse
    </ul>

    <div class="mt-4">{{ $movs->links() }}</div>
</x-app-layout>
