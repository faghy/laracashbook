{{-- Logo configurabile (config/cashbook.php): se non impostato mostra solo il nome dell'applicazione --}}
@if (config('cashbook.logo_url'))
    <img {{ $attributes->merge(['class' => 'h-8 w-auto']) }} src="{{ config('cashbook.logo_url') }}" alt="{{ config('cashbook.logo_alt') }}">
@endif
