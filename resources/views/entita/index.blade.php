<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900">{{ __('Entities') }}</h2>
    </x-slot>

    <form method="POST" action="{{ route('entita.store') }}" class="card p-4 mb-5 flex flex-wrap items-end gap-3 max-w-3xl">
        @csrf
        <div class="grow">
            <label for="name" class="field-label">{{ __('New entity') }}</label>
            <input id="name" name="name" type="text" class="w-full" required maxlength="255" value="{{ old('name') }}">
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <button type="submit" class="btn btn-primary">{{ __('Add') }}</button>
    </form>

    <div class="card overflow-x-auto max-w-3xl">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">{{ __('Name') }}</th>
                    <th scope="col" class="text-right">{{ __('Users') }}</th>
                    <th scope="col" class="text-right">{{ __('Categories') }}</th>
                    <th scope="col" class="text-right">{{ __('Movements') }}</th>
                    <th scope="col" class="text-right"><span class="sr-only">{{ __('Actions') }}</span></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($entities as $entity)
                <tr>
                    <td>
                        <form method="POST" action="{{ route('entita.update', $entity) }}" class="flex gap-2">
                            @csrf
                            @method('PATCH')
                            <input name="name" type="text" class="py-1 w-full" required maxlength="255" value="{{ $entity->name }}" aria-label="{{ __('Name') }}">
                            <button type="submit" class="btn btn-sm btn-secondary">{{ __('Rename') }}</button>
                        </form>
                    </td>
                    <td class="text-right tabular-nums">{{ $entity->users_count }}</td>
                    <td class="text-right tabular-nums">{{ $entity->categories_count }}</td>
                    <td class="text-right tabular-nums">{{ $entity->movements_count }}</td>
                    <td class="text-right">
                        @if ($entity->categories_count === 0 && $entity->movements_count === 0)
                            <form method="POST" action="{{ route('entita.destroy', $entity) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-ghost-danger"
                                        data-confirm="{{ __('Delete the entity :name?', ['name' => $entity->name]) }}"
                                        onclick="return confirm(this.dataset.confirm)">{{ __('Delete') }}</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-10 text-center text-slate-500">{{ __('No entities yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
