<form action="{{ route('salvaEntita') }}" method="post" class="flex items-center gap-2 ml-auto" id="formEntity">
    @csrf

    <label for="entity" class="text-sm font-semibold text-slate-600">{{ __('Entity') }}</label>
    <select name="entity" id="entity" class="font-semibold py-1.5" onchange="this.form.submit()">
        @foreach($entities ?? [] as $entity)
            <option value="{{ $entity->id }}" @selected($entity->id == session('selected_entity_id'))>
                {{ $entity->name }}
            </option>
        @endforeach
    </select>

    <noscript><button type="submit" class="btn btn-sm btn-secondary">{{ __('Select') }}</button></noscript>
</form>
