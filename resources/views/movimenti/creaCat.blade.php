<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900">{{ __('Add category') }}</h2>
    </x-slot>

    <form action="{{ route('categoria.store') }}" method="POST" class="card max-w-xl mx-auto p-6 sm:p-8 space-y-5">
        @csrf
        @if(request('source'))
            <input type="hidden" name="source" value="{{ request('source') }}">
        @endif

        <div>
            <label for="descriz" class="field-label">{{ __('Category name') }}</label>
            <input class="w-full" type="text" name="descriz_cat" id="descriz"
                   value="{{ old('descriz_cat') }}" maxlength="255" required autofocus>
            <x-input-error :messages="$errors->get('descriz_cat')" class="mt-2" />
        </div>

        <label for="estero_senza_iva" class="flex items-start gap-3 p-4 rounded-lg ring-1 ring-slate-200 bg-slate-50 cursor-pointer
                                         has-checked:ring-amber-400 has-checked:bg-amber-50">
            <input type="checkbox" id="estero_senza_iva" name="estero_senza_iva" value="1" class="mt-0.5 size-5 accent-amber-600" @checked(old('estero_senza_iva'))>
            <span>
                <span class="block font-semibold">{{ __('Foreign VAT-exempt movements') }}</span>
                <span class="block text-sm text-slate-600">{{ __('New movements in this category will have "Foreign movement, VAT exempt" already ticked.') }}</span>
            </span>
        </label>

        <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
            <a class="btn btn-secondary"
               href="{{ request('source') === 'movimenti.create' ? route('movimenti.create') : route('categoria.index') }}">{{ __('Cancel') }}</a>
            <button class="btn btn-primary" type="submit">{{ __('Add') }}</button>
        </div>
    </form>
</x-app-layout>
