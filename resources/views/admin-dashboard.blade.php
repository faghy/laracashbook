<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900">
            {{ __('Admin dashboard') }}
        </h2>
        <a class="btn btn-secondary ml-auto" href="{{ route('admin.logout') }}">{{ __('Log out') }}</a>
    </x-slot>

    <div class="card p-6">
        {{ __('Administrator area.') }}
    </div>
</x-app-layout>
