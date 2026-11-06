<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900">{{ __('Edit movement') }}</h2>
    </x-slot>

    <form method="post" action="{{ route('movimenti.update', $movimenta->id) }}" class="card max-w-2xl mx-auto p-6 sm:p-8">
        @csrf
        @method('PATCH')
        @include('movimenti._form', ['movimento' => $movimenta])
    </form>
</x-app-layout>
