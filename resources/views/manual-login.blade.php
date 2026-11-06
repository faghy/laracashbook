<x-guest-layout>

    <h2 class="text-xl font-bold text-slate-900 mb-6">Accedi</h2>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('manual.login.post') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="" name="remember">
                <span class="ml-2 text-sm text-slate-600">Ricordami</span>
            </label>
        </div>

        <div class="flex justify-between items-center mt-6">
{{--            <a class="basis-3 text-sm text-slate-600 hover:text-gray-900 rounded-md" href="register">Registrati</a>--}}
            @if (Route::has('password.request'))
                <a class="underline text-sm text-slate-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    {{ __('Recupera password') }}
                </a>
            @endif

            <x-primary-button class=" ml-3">
                Accedi
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
