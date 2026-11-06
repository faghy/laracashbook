<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900">{{ __('Categories') }}</h2>
        <x-entity-choice :entities="$entities" />
        <a href="{{ route('categoria.create') }}" class="btn btn-primary">{{ __('+ New category') }}</a>
    </x-slot>

    <div class="card overflow-hidden max-w-3xl">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">{{ __('Name') }}</th>
                    <th scope="col" class="text-right"><span class="sr-only">{{ __('Actions') }}</span></th>
                </tr>
            </thead>
            <tbody>
            @forelse($cats as $cat)
                <tr>
                    <td class="font-medium">
                        {{ $cat->descriz_cat }}
                        @if ($cat->estero_senza_iva)
                            <span class="badge bg-amber-100 text-amber-800 ml-1" title="{{ __('New movements are foreign and VAT exempt') }}">{{ __('Foreign, no VAT') }}</span>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('categoria.destroy', $cat->cat_id) }}" class="flex justify-end gap-1">
                            @csrf
                            @method('delete')
                            <a href="{{ route('categoria.edit', $cat->cat_id) }}" class="btn btn-sm btn-secondary">{{ __('Edit') }}</a>
                            <button type="submit"
                                    data-confirm="{{ __('Do you really want to delete the category :name?', ['name' => $cat->descriz_cat]) }}"
                                    onclick="return confirm(this.dataset.confirm)"
                                    class="btn btn-sm btn-ghost-danger">{{ __('Delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="py-10 text-center text-slate-500">{{ __('No categories.') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 max-w-3xl">{{ $cats->links() }}</div>
</x-app-layout>
