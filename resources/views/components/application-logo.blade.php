{{-- Logo configurabile (config/cashbook.php): predefinito quello di LaraCashbook, "none" mostra solo il nome --}}
@if ($logo = config('cashbook.logo_url'))
    @php
        // Il logo predefinito ha lo sfondo viola come la barra: un bordo chiaro lo stacca
        $default = $logo === '/images/laracashbook-logo.png';
    @endphp
    <img {{ $attributes->merge(['class' => 'h-8 w-auto'.($default ? ' rounded-[22%] ring-2 ring-white/70 shadow-md' : '')]) }}
         src="{{ preg_match('#^(https?:)?//#', $logo) ? $logo : asset($logo) }}" alt="{{ config('cashbook.logo_alt') }}">
@endif
