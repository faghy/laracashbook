<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900">Dashboard</h2>
        <x-entity-choice :entities="$entities" />
    </x-slot>

    @php
        // Nomi dei mesi nella lingua dell'applicazione
        $mese = fn (int $n, string $format) => ucfirst(\Illuminate\Support\Carbon::create(2000, $n, 1)->locale(app()->getLocale())->isoFormat($format));
        $mesi = collect(range(1, 12))->mapWithKeys(fn ($n) => [$n => $mese($n, 'MMMM')])->all();
        $mesiBrevi = collect(range(1, 12))->mapWithKeys(fn ($n) => [$n => rtrim($mese($n, 'MMM'), '.')])->all();
        $fieldClass = 'w-full';
    @endphp

    @if(auth()->user()->role !== 'stagista')
        {{-- Totali generali dell'entità --}}
        <section class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="card p-4 border-l-4 border-l-indigo-500">
                <p class="text-sm font-medium text-slate-600">{{ __('Total this year') }}</p>
                <p class="amount text-2xl {{ $totalImporto < 0 ? 'text-rose-700' : 'text-slate-900' }}">@euro($totalImporto)</p>
            </div>
            <div class="card p-4 border-l-4 border-l-violet-500">
                <p class="text-sm font-medium text-slate-600">{{ __('Total since the start') }}</p>
                <p class="amount text-2xl {{ $movimentiTot < 0 ? 'text-rose-700' : 'text-slate-900' }}">@euro($movimentiTot)</p>
            </div>
            <div class="card p-4 border-l-4 border-l-emerald-500">
                <p class="text-sm font-medium text-slate-600">{{ __('Income since the start') }}</p>
                <p class="amount text-2xl text-emerald-700">@euro($movimentiTotIn)</p>
            </div>
            <div class="card p-4 border-l-4 border-l-rose-500">
                <p class="text-sm font-medium text-slate-600">{{ __('Expenses since the start') }}</p>
                <p class="amount text-2xl text-rose-700">@euro(abs($movimentiTotOut))</p>
            </div>
        </section>

        {{-- Filtri del periodo --}}
        <form method="GET" action="{{ route('dashboard') }}" class="card p-4 sm:p-5 mb-6" id="filterForm">
            <h3 class="text-lg font-bold mb-3">{{ __('Advanced search') }}</h3>
            <div class="grid grid-cols-3 md:grid-cols-6 gap-3">
                <div>
                    <label for="start_day" class="field-label">{{ __('Start day') }}</label>
                    <input type="number" name="start_day" id="start_day" min="1" max="31" value="{{ $startDay }}" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label for="start_month" class="field-label">{{ __('Start month') }}</label>
                    <select name="start_month" id="start_month" class="{{ $fieldClass }}">
                        @foreach ($mesi as $n => $mese)
                            <option value="{{ $n }}" @selected($startMonth == $n)>{{ $mese }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="start_year" class="field-label">{{ __('Start year') }}</label>
                    <select name="start_year" id="start_year" class="{{ $fieldClass }}">
                        @foreach($years as $year)
                            <option value="{{ $year }}" @selected($startYear == $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="end_day" class="field-label">{{ __('End day') }}</label>
                    <input type="number" name="end_day" id="end_day" min="1" max="31" value="{{ $endDay }}" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label for="end_month" class="field-label">{{ __('End month') }}</label>
                    <select name="end_month" id="end_month" class="{{ $fieldClass }}">
                        @foreach ($mesi as $n => $mese)
                            <option value="{{ $n }}" @selected($endMonth == $n)>{{ $mese }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="end_year" class="field-label">{{ __('End year') }}</label>
                    <select name="end_year" id="end_year" class="{{ $fieldClass }}">
                        @foreach($years as $year)
                            <option value="{{ $year }}" @selected($endYear == $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <label for="categories" class="field-label">
                    {{ __('Categories') }} <span class="font-normal text-slate-500">{{ __('(none selected = all; hold Ctrl to select more than one)') }}</span>
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
                <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                <p class="text-slate-700 md:ml-auto">
                    <strong>{{ __('Period:') }}</strong>
                    {{ \Illuminate\Support\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Illuminate\Support\Carbon::parse($endDate)->format('d/m/Y') }}
                    @if(!empty($selectedCategories))
                        | <strong>{{ __('Filtered categories:') }}</strong> {{ count($selectedCategories) }}
                    @endif
                </p>
            </div>
        </form>

        {{-- Risultati del periodo --}}
        <section class="grid md:grid-cols-3 gap-4 mb-6">
            <div class="rounded-xl shadow-md p-6 text-white bg-linear-to-br from-emerald-600 to-emerald-700">
                <h4 class="text-base font-semibold mb-1 text-white/90">{{ __('Total income') }}</h4>
                <p class="amount text-4xl">@euro($entrate)</p>
            </div>
            <div class="rounded-xl shadow-md p-6 text-white bg-linear-to-br from-rose-600 to-rose-700">
                <h4 class="text-base font-semibold mb-1 text-white/90">{{ __('Total expenses') }}</h4>
                <p class="amount text-4xl">@euro($uscite)</p>
            </div>
            <div class="rounded-xl shadow-md p-6 text-white bg-linear-to-br {{ $saldo >= 0 ? 'from-indigo-600 to-violet-700' : 'from-amber-600 to-orange-700' }}">
                <h4 class="text-base font-semibold mb-1 text-white/90">{{ __('Balance') }}</h4>
                <p class="amount text-4xl">@euro($saldo)</p>
            </div>
        </section>

        {{-- Esteri senza IVA nel periodo --}}
        <section class="card p-4 sm:p-5 mb-6 border-l-4 border-l-amber-500">
            <div class="flex flex-wrap items-baseline justify-between gap-2 mb-3">
                <h3 class="text-lg font-bold">
                    {{ __('Foreign VAT-exempt movements') }}
                    <span class="badge bg-amber-100 text-amber-800 ml-1">{{ trans_choice(':count movement|:count movements', $estero->movimenti) }}</span>
                </h3>
                <p class="text-sm text-slate-600">{{ __('In the filtered period and categories; already included in the totals above.') }}</p>
            </div>
            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <p class="text-sm font-medium text-slate-600">{{ __('Income') }}</p>
                    <p class="amount text-2xl text-emerald-700">@euro($estero->entrate)</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-600">{{ __('Expenses') }}</p>
                    <p class="amount text-2xl text-rose-700">@euro($estero->uscite)</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-600">{{ __('Balance') }}</p>
                    <p class="amount text-2xl {{ $estero->entrate - $estero->uscite < 0 ? 'text-rose-700' : 'text-slate-900' }}">@euro($estero->entrate - $estero->uscite)</p>
                </div>
            </div>
        </section>

        {{-- Andamento mensile --}}
        <section class="card p-4 sm:p-5 mb-6">
            <h3 class="text-lg font-bold mb-1">{{ __('Monthly trend') }}</h3>
            <p class="text-sm text-slate-600 mb-4">
                <span class="inline-block w-3 h-3 bg-emerald-500 rounded-sm align-middle"></span> {{ __('Income') }}
                <span class="inline-block w-3 h-3 bg-rose-500 rounded-sm align-middle ml-3"></span> {{ __('Expenses') }}
            </p>
            @if ($monthly->isEmpty())
                <p class="text-slate-500">{{ __('No movements in the selected period.') }}</p>
            @else
                <div class="space-y-3">
                    @foreach ($monthly as $row)
                        <div class="grid grid-cols-[5rem_1fr] gap-3 items-center">
                            <span class="text-sm font-semibold text-slate-700">{{ $mesiBrevi[$row->mese] }} {{ $row->anno }}</span>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2" title="{{ __('Income') }}">
                                    <div class="h-3 bg-emerald-500 rounded-sm" style="width: {{ round($row->entrate / $maxMonthly * 85, 2) }}%"></div>
                                    <span class="text-xs font-medium tabular-nums text-slate-700 whitespace-nowrap">@euro($row->entrate)</span>
                                </div>
                                <div class="flex items-center gap-2" title="{{ __('Expenses') }}">
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
            <h3 class="text-lg font-bold px-4 sm:px-5 pt-4 pb-3">{{ __('Summary by category') }}</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('Category') }}</th>
                        <th class="text-right">{{ __('Movements') }}</th>
                        <th class="text-right">{{ __('Income') }}</th>
                        <th class="text-right">{{ __('Expenses') }}</th>
                        <th class="text-right">{{ __('Balance') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($byCategory as $row)
                    <tr>
                        <td class="font-medium">{{ $row->categoria ?? __('N/A') }}</td>
                        <td class="text-right">{{ $row->movimenti }}</td>
                        <td class="text-right amount text-emerald-700">@euro($row->entrate)</td>
                        <td class="text-right amount text-rose-700">@euro($row->uscite)</td>
                        <td class="text-right amount">@euro($row->entrate - $row->uscite)</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-slate-500">{{ __('No movements in the selected period.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
    @endif

    {{-- Utenti dell'entità --}}
    <section class="card overflow-x-auto">
        <h3 class="text-lg font-bold px-4 sm:px-5 pt-4 pb-3">{{ __('Users of this entity') }}</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Created on') }}</th>
                    <th class="text-right">{{ __('Movements') }}</th>
                    <th class="text-right">{{ __('Entities') }}</th>
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
