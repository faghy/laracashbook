<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900">
            Admin Dashboard
        </h2>
        <a class="btn btn-secondary ml-auto" href="{{ route('admin.logout') }}">Effettua logout</a>
    </x-slot>

    <div class="card p-6">
        Area amministratore.
    </div>
</x-app-layout>
