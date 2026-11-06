{{-- Messaggi di esito delle operazioni, mostrati una sola volta dopo un redirect --}}
@php
    // Codici di stato di Breeze gestiti direttamente dai form del profilo
    $internalCodes = ['profile-updated', 'password-updated', 'verification-link-sent'];
    $ok = ['bg-emerald-50 border-emerald-500 text-emerald-900', '✓'];
    $ko = ['bg-rose-50 border-rose-500 text-rose-900', '!'];
@endphp
@foreach ([
    'status' => $ok,
    'success' => $ok,
    'categoria_created' => $ok,
    'error' => $ko,
    'categoria_created_error' => $ko,
] as $key => [$classes, $icon])
    @if (is_string(session($key)) && ! in_array(session($key), $internalCodes, true))
        <div class="mb-4 px-4 py-3 border-l-4 rounded-lg shadow-sm flex items-center gap-3 {{ $classes }}" role="alert">
            <span class="font-bold text-lg" aria-hidden="true">{{ $icon }}</span>
            <span>{{ session($key) }}</span>
        </div>
    @endif
@endforeach
