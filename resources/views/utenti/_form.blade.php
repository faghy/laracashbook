{{-- Campi comuni a creazione e modifica utente --}}
<div class="space-y-5">
    <div>
        <label for="name" class="field-label">{{ __('Name') }}</label>
        <input id="name" name="name" type="text" class="w-full" required maxlength="255" value="{{ old('name', $user->name) }}">
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <label for="email" class="field-label">{{ __('Email') }}</label>
        <input id="email" name="email" type="email" class="w-full" required maxlength="255" value="{{ old('email', $user->email) }}">
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <fieldset>
        <legend class="field-label">{{ __('Role') }}</legend>
        @if ($isSelf)
            <input type="hidden" name="role" value="{{ $user->role }}">
            <x-role-badge :role="$user->role" />
            <p class="mt-1 text-sm text-slate-500">{{ __('You cannot change your own role.') }}</p>
        @else
            <div class="space-y-2">
                @foreach ($roles as $role)
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="radio" name="role" value="{{ $role }}" class="mt-1" @checked(old('role', $user->role) === $role)>
                        <span>
                            <x-role-badge :role="$role" />
                            <span class="block text-sm text-slate-600 mt-0.5">
                                @switch($role)
                                    @case('superadmin') {{ __('Sees and manages everything: all entities and all users.') }} @break
                                    @case('admin') {{ __('Dashboard and totals, categories, export; manages admins and operators of their entities.') }} @break
                                    @default {{ __('Only adds, edits and deletes movements; does not see totals.') }}
                                @endswitch
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>
        @endif
        <x-input-error :messages="$errors->get('role')" class="mt-2" />
    </fieldset>

    <fieldset>
        <legend class="field-label">{{ __('Entities') }}</legend>
        <div class="grid sm:grid-cols-2 gap-2">
            @foreach ($entities as $entity)
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="entities[]" value="{{ $entity->id }}" @checked(in_array($entity->id, array_map('intval', old('entities', $selected)), true))>
                    {{ $entity->name }}
                </label>
            @endforeach
        </div>
        <p class="mt-1 text-sm text-slate-500">{{ __('A super admin sees all entities anyway.') }}</p>
        <x-input-error :messages="array_merge($errors->get('entities'), $errors->get('entities.*'))" class="mt-2" />
    </fieldset>
</div>
