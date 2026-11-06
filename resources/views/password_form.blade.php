<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900">{{ __('Dashboard: authentication required') }}</h2>
    </x-slot>

    <form class="card max-w-md mx-auto p-6 sm:p-8 space-y-4 text-center" method="POST" action="{{ route('dashboard') }}">
        @csrf
        @if(session('password_error'))
            <p class="text-rose-700 font-medium">{{ session('password_error') }}</p>
        @endif

        <label for="password" class="block font-bold text-xl">{{ __('Enter the password') }}</label>
        <input class="w-full text-center" type="password" name="password" id="password" required autofocus>

        <div class="flex justify-center gap-3">
            <button type="submit" class="btn btn-primary px-8">{{ __('Submit') }}</button>
            <a class="btn btn-secondary" href="{{ route('movimenti.index') }}">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-app-layout>
