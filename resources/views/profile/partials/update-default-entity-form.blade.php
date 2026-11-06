<section>
    <header>
        <h2 class="text-lg font-semibold text-slate-900">{{ __('Default entity') }}</h2>
        <p class="mt-1 text-sm text-slate-600">
            {{ __('The entity selected automatically every time you sign in. You can always switch it from the menu at the top.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.default-entity') }}" class="mt-6 space-y-4">
        @csrf
        @method('patch')

        <div>
            <label for="default_entity_id" class="field-label">{{ __('Entity') }}</label>
            <select id="default_entity_id" name="default_entity_id" class="w-full">
                <option value="">{{ __('None (the first available)') }}</option>
                @foreach ($entities as $entity)
                    <option value="{{ $entity->id }}" @selected(old('default_entity_id', $user->default_entity_id) == $entity->id)>{{ $entity->name }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('default_entity_id')" />
        </div>

        <x-primary-button>{{ __('Save') }}</x-primary-button>
    </form>
</section>
