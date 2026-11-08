<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900">{{ __('New user') }}</h2>
    </x-slot>

    <form method="POST" action="{{ route('utenti.store') }}" class="card max-w-2xl mx-auto p-6 sm:p-8">
        @csrf
        @include('utenti._form')
        <p class="mt-5 text-sm text-slate-600">{{ __('A random password is generated and shown once after saving.') }}</p>
        <div class="flex justify-end gap-3 pt-4 mt-5 border-t border-slate-200">
            <a class="btn btn-secondary" href="{{ route('utenti.index') }}">{{ __('Cancel') }}</a>
            <button class="btn btn-primary" type="submit">{{ __('Create user') }}</button>
        </div>
    </form>
</x-app-layout>
