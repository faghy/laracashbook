{{-- Campi comuni ai form di inserimento e modifica movimento. $movimento è null in inserimento. --}}
@php
    $categoria = (int) old('categoria', $movimento?->categoria);
    // In inserimento tipo e flag partono dalle impostazioni della categoria proposta
    $catProposta = $cats->firstWhere('cat_id', $categoria) ?? $cats->first();
    $tipo = (string) old('entratauscita', $movimento?->entratauscita ?? $catProposta?->tipo_predefinito ?? 1);
    $data = old('data', $movimento?->data_movimento?->toDateString() ?? now()->toDateString());
    $catEstero = (bool) $catProposta?->estero_senza_iva;
    $estero = (bool) old('estero_senza_iva', $movimento ? $movimento->estero_senza_iva : $catEstero);
@endphp

@if ($errors->any())
    <div class="bg-rose-50 border-l-4 border-rose-500 text-rose-900 px-4 py-3 rounded-lg mb-6" role="alert">
        <strong class="font-bold">{{ __('There were some problems with the data you entered.') }}</strong>
        <ul class="mt-2 list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="space-y-6" x-data>
    <fieldset>
        <legend class="field-label">{{ __('Type') }}</legend>
        <div class="grid grid-cols-2 gap-3">
            <label class="cursor-pointer">
                <input type="radio" id="IN" name="entratauscita" value="1" class="peer sr-only" x-ref="entrata" @checked($tipo === '1')>
                <span class="block text-center py-3 rounded-lg border-2 border-slate-300 text-lg font-semibold text-slate-600 hover:border-slate-400
                             peer-checked:border-emerald-600 peer-checked:bg-emerald-50 peer-checked:text-emerald-800
                             peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-300">{{ __('+ Income') }}</span>
            </label>
            <label class="cursor-pointer">
                <input type="radio" id="OUT" name="entratauscita" value="0" class="peer sr-only" x-ref="uscita" @checked($tipo === '0')>
                <span class="block text-center py-3 rounded-lg border-2 border-slate-300 text-lg font-semibold text-slate-600 hover:border-slate-400
                             peer-checked:border-rose-600 peer-checked:bg-rose-50 peer-checked:text-rose-800
                             peer-focus-visible:ring-2 peer-focus-visible:ring-rose-300">{{ __('− Expense') }}</span>
            </label>
        </div>
    </fieldset>

    <div>
        <label for="select-categoria" class="field-label">{{ __('Category') }}</label>
        <div class="flex flex-col sm:flex-row gap-3">
            <select name="categoria" id="select-categoria" class="w-full sm:flex-1" required
                    @unless ($movimento) @change="const o = $event.target.selectedOptions[0].dataset;
                        $refs.estero.checked = o.estero === '1';
                        if (o.tipo === '1') $refs.entrata.checked = true;
                        if (o.tipo === '0') $refs.uscita.checked = true" @endunless>
                @foreach($cats as $cat)
                    <option value="{{ $cat->cat_id }}" data-estero="{{ $cat->estero_senza_iva ? 1 : 0 }}" data-tipo="{{ $cat->tipo_predefinito }}" @selected($categoria === (int) $cat->cat_id)>{{ $cat->descriz_cat }}</option>
                @endforeach
            </select>
            @if (! $movimento && Gate::allows('admin'))
                <a href="{{ route('categoria.create', ['source' => 'movimenti.create']) }}"
                   class="btn btn-secondary">{{ __('+ New category') }}</a>
            @endif
        </div>
    </div>

    <div>
        <label for="descrizione" class="field-label">{{ __('Description') }}</label>
        <input class="w-full" id="descrizione" type="text" name="descrizione" maxlength="255" required
               placeholder="{{ __('E.g. Groceries') }}"
               value="{{ old('descrizione', $movimento?->descrizione) }}">
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <label for="import" class="field-label">{{ __('Amount') }}</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-3 flex items-center text-slate-500 pointer-events-none">€</span>
                <input class="w-full pl-8 text-right text-lg" id="import" type="number" step="0.01" min="0" name="importo" required
                       inputmode="decimal" placeholder="0,00"
                       value="{{ old('importo', $movimento ? number_format(abs($movimento->importo), 2, '.', '') : null) }}">
            </div>
        </div>

        <div>
            <label for="data" class="field-label">{{ __('Date') }}</label>
            <input class="w-full text-lg" id="data" type="date" name="data" value="{{ $data }}">
        </div>
    </div>

    <label for="estero_senza_iva" class="flex items-start gap-3 p-4 rounded-lg ring-1 ring-slate-200 bg-slate-50 cursor-pointer
                                     has-checked:ring-amber-400 has-checked:bg-amber-50">
        <input type="hidden" name="estero_senza_iva" value="0">
        <input type="checkbox" id="estero_senza_iva" name="estero_senza_iva" value="1" class="mt-0.5 size-5 accent-amber-600" x-ref="estero" @checked($estero)>
        <span>
            <span class="block font-semibold">{{ __('Foreign movement, VAT exempt') }}</span>
            <span class="block text-sm text-slate-600">{{ __('Tick it for transactions with foreign parties that are not subject to VAT.') }}</span>
        </span>
    </label>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-4 border-t border-slate-200">
        <a class="btn btn-secondary text-lg" href="{{ route('movimenti.index') }}">{{ __('Cancel') }}</a>
        <button class="btn btn-success text-lg px-8" type="submit">{{ __('Save') }}</button>
    </div>
</div>
