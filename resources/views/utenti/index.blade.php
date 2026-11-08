<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900">{{ __('Users') }}</h2>
        <a href="{{ route('utenti.create') }}" class="btn btn-primary ml-auto">{{ __('+ New user') }}</a>
    </x-slot>

    <div class="card overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">{{ __('Name') }}</th>
                    <th scope="col">{{ __('Email') }}</th>
                    <th scope="col">{{ __('Role') }}</th>
                    <th scope="col">{{ __('Entities') }}</th>
                    <th scope="col" class="text-right"><span class="sr-only">{{ __('Actions') }}</span></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($users as $u)
                <tr @class(['opacity-60' => $u->isDisabled()])>
                    <td class="font-medium">
                        {{ $u->name }}
                        @if ($u->isDisabled())
                            <span class="badge bg-slate-200 text-slate-700 ml-1">{{ __('Disabled') }}</span>
                        @endif
                    </td>
                    <td>{{ $u->email }}</td>
                    <td><x-role-badge :role="$u->role" /></td>
                    <td class="text-sm text-slate-600">
                        @if ($u->isSuperadmin())
                            {{ __('All') }}
                        @else
                            {{ $u->userEntities->map(fn ($ue) => $entityNames[$ue->entity_id] ?? '')->filter()->sort()->implode(', ') }}
                        @endif
                    </td>
                    <td class="text-right">
                        @can('manage-user', $u)
                            <a href="{{ route('utenti.edit', $u) }}" class="btn btn-sm btn-secondary">{{ __('Edit') }}</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-10 text-center text-slate-500">{{ __('No users.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
