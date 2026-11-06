<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900">Profilo</h2>
        <span class="text-slate-600">{{ $user->name }}</span>
    </x-slot>

    <div class="max-w-3xl space-y-6">
        <div class="card p-4 sm:p-8 border-l-4 border-l-indigo-500">
            <div class="max-w-xl">
                @include('profile.partials.update-default-entity-form')
            </div>
        </div>

        <div class="card p-4 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="card p-4 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="card p-4 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
