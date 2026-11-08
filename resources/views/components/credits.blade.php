{{-- Crediti in fondo alla pagina: una riga sottile. $light per gli sfondi scuri (pagina di accesso) --}}
@props(['light' => false])
<p {{ $attributes->merge(['class' => 'text-xs text-center '.($light ? 'text-white/60' : 'text-slate-400')]) }}>
    <a href="https://laracashbook.it" target="_blank" rel="noopener" class="hover:underline {{ $light ? 'hover:text-white' : 'hover:text-slate-600' }}">LaraCashbook</a>
    · {{ __('Made by') }}
    <a href="https://faghy.it" target="_blank" rel="noopener" class="hover:underline {{ $light ? 'hover:text-white' : 'hover:text-slate-600' }}">Faghy – Christian Serafino</a>
</p>
