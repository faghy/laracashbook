<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900">{{ __('Edit user') }}</h2>
        @if ($user->isDisabled())
            <span class="badge bg-slate-200 text-slate-700">{{ __('Disabled') }}</span>
        @endif
    </x-slot>

    <div class="max-w-2xl mx-auto space-y-6">
        @if (session('new_password'))
            {{-- Mostrata una volta sola: dopo questa pagina non è più recuperabile --}}
            <div class="card p-5 border-l-4 border-l-emerald-500">
                <p class="font-semibold">{{ __('New password for :name', ['name' => $user->name]) }}</p>
                <p class="mt-2"><code class="text-lg font-mono bg-slate-100 px-2 py-1 rounded select-all">{{ session('new_password') }}</code></p>
                <p class="mt-2 text-sm text-slate-600">{{ __('Copy it now and give it to the user: it will not be shown again. They can change it from their profile.') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('utenti.update', $user) }}" class="card p-6 sm:p-8">
            @csrf
            @method('PATCH')
            @include('utenti._form')
            <div class="flex justify-end gap-3 pt-4 mt-5 border-t border-slate-200">
                <a class="btn btn-secondary" href="{{ route('utenti.index') }}">{{ __('Back to users') }}</a>
                <button class="btn btn-success" type="submit">{{ __('Save') }}</button>
            </div>
        </form>

        <div class="card p-6 sm:p-8 flex flex-wrap gap-3 items-center">
            <form method="POST" action="{{ route('utenti.password', $user) }}">
                @csrf
                <button type="submit" class="btn btn-secondary"
                        data-confirm="{{ __('Set a new random password for :name? The current one will stop working.', ['name' => $user->name]) }}"
                        onclick="return confirm(this.dataset.confirm)">{{ __('Reset password') }}</button>
            </form>
            @unless ($isSelf)
                <form method="POST" action="{{ route('utenti.toggle', $user) }}">
                    @csrf
                    @if ($user->isDisabled())
                        <button type="submit" class="btn btn-secondary">{{ __('Enable user') }}</button>
                    @else
                        <button type="submit" class="btn btn-danger"
                                data-confirm="{{ __('Disable :name? They will no longer be able to sign in; their movements stay.', ['name' => $user->name]) }}"
                                onclick="return confirm(this.dataset.confirm)">{{ __('Disable user') }}</button>
                    @endif
                </form>
            @endunless
        </div>
    </div>
</x-app-layout>
