{{-- Selettore della lingua: un pulsante per lingua, quella attiva evidenziata. $light per gli sfondi scuri --}}
@props(['light' => false])
<form method="POST" action="{{ route('locale.update') }}" {{ $attributes->merge(['class' => 'flex items-center gap-1 text-xs']) }}>
    @csrf
    <span class="sr-only">{{ __('Language') }}</span>
    @foreach (config('app.locales') as $code => $name)
        @php $active = app()->getLocale() === $code; @endphp
        <button type="submit" name="locale" value="{{ $code }}" lang="{{ $code }}" title="{{ $name }}"
                @if ($active) aria-current="true" @endif
                class="px-2 py-1 rounded-md font-semibold uppercase transition-colors {{ $active
                    ? ($light ? 'bg-white/25 text-white' : 'bg-indigo-100 text-indigo-700')
                    : ($light ? 'text-white/70 hover:bg-white/10 hover:text-white' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700') }}">{{ $code }}</button>
    @endforeach
</form>
