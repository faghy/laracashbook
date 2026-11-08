{{-- Eliminazione di una categoria: prima si spostano i suoi movimenti (anche quelli nel cestino) in altre categorie --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900">{{ __('Delete category :name', ['name' => $categoria->descriz_cat]) }}</h2>
    </x-slot>

    @if ($movs->total() === 0)
        <form method="POST" action="{{ route('categoria.destroy', $categoria->cat_id) }}" class="card max-w-xl mx-auto p-6 sm:p-8 space-y-5">
            @csrf
            @method('delete')
            <p>{{ __('The category has no movements and can be deleted.') }}</p>
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                <a class="btn btn-secondary" href="{{ route('categoria.index') }}">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-danger"
                        data-confirm="{{ __('Do you really want to delete the category :name?', ['name' => $categoria->descriz_cat]) }}"
                        onclick="return confirm(this.dataset.confirm)">{{ __('Delete category') }}</button>
            </div>
        </form>
    @else
        <div class="mb-5 px-4 py-3 border-l-4 rounded-lg bg-amber-50 border-amber-500 text-amber-900">
            {{ trans_choice('The category still has :count movement (including the trash). Move it to another category to delete the category.|The category still has :count movements (including the trash). Move them to another category to delete the category.', $movs->total()) }}
        </div>

        @if ($altre->isEmpty())
            <div class="card p-6 mb-5">
                <p class="mb-4">{{ __('There is no other category to move the movements to.') }}</p>
                <a href="{{ route('categoria.create') }}" class="btn btn-primary">{{ __('+ New category') }}</a>
            </div>
        @else
            {{-- Spostamento in blocco: le caselle delle righe appartengono a questo form (attributo form) --}}
            <form id="bulk-move" method="POST" action="{{ route('categoria.move', $categoria->cat_id) }}"
                  class="card p-4 mb-5 flex flex-wrap items-end gap-3">
                @csrf
                <div class="grow sm:grow-0 sm:w-72">
                    <label for="target" class="field-label">{{ __('Move to category') }}</label>
                    <select name="target" id="target" class="w-full" required>
                        @foreach ($altre as $cat)
                            <option value="{{ $cat->cat_id }}">{{ $cat->descriz_cat }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" name="scope" value="selected" class="btn btn-primary">{{ __('Move selected') }}</button>
                <button type="submit" name="scope" value="all" class="btn btn-secondary"
                        data-confirm="{{ trans_choice('Move :count movement to the chosen category?|Move all :count movements to the chosen category?', $movs->total()) }}"
                        onclick="return confirm(this.dataset.confirm)">{{ trans_choice('Move :count movement|Move all :count movements', $movs->total()) }}</button>
                <x-input-error :messages="array_merge($errors->get('ids'), $errors->get('target'))" class="w-full" />
            </form>

            <div class="card overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col" class="w-10">
                                <input type="checkbox" aria-label="{{ __('Select all on this page') }}"
                                       onclick="document.querySelectorAll('input[type=checkbox][name=\'ids[]\']').forEach(c => c.checked = this.checked)">
                            </th>
                            <th scope="col">{{ __('Date') }}</th>
                            <th scope="col">{{ __('Description') }}</th>
                            <th scope="col" class="text-right">{{ __('Amount') }}</th>
                            <th scope="col" class="text-right">{{ __('Move to') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($movs as $mov)
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="{{ $mov->id }}" form="bulk-move" aria-label="{{ __('Select') }}"></td>
                            <td class="whitespace-nowrap tabular-nums text-slate-600">{{ $mov->data_movimento->format('d/m/Y') }}</td>
                            <td>
                                <span class="font-medium">{{ $mov->descrizione }}</span>
                                @if ($mov->trashed())
                                    <span class="badge bg-slate-200 text-slate-700 ml-1">{{ __('In the trash') }}</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <span class="amount {{ $mov->entratauscita ? 'text-emerald-700' : 'text-rose-700' }}">@euro($mov->importo)</span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('categoria.move', $categoria->cat_id) }}" class="flex justify-end gap-2">
                                    @csrf
                                    <input type="hidden" name="scope" value="selected">
                                    <input type="hidden" name="ids[]" value="{{ $mov->id }}">
                                    <select name="target" class="py-1 text-sm" aria-label="{{ __('Move to') }}">
                                        @foreach ($altre as $cat)
                                            <option value="{{ $cat->cat_id }}">{{ $cat->descriz_cat }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-secondary">{{ __('Move') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $movs->links() }}</div>
        @endif

        <div class="mt-6">
            <a class="btn btn-secondary" href="{{ route('categoria.index') }}">{{ __('Back to categories') }}</a>
        </div>
    @endif
</x-app-layout>
