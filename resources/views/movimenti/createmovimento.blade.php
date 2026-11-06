<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900">Nuovo movimento</h2>
    </x-slot>

    <form method="post" action="{{ route('movimenti.store') }}" class="card max-w-2xl mx-auto p-6 sm:p-8">
        @csrf
        @include('movimenti._form', ['movimento' => null])
    </form>
</x-app-layout>
