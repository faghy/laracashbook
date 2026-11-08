{{-- Ruolo dell'utente come badge colorato --}}
@props(['role'])
@php
    $classes = ['superadmin' => 'bg-violet-100 text-violet-800', 'admin' => 'bg-indigo-100 text-indigo-800', 'operatore' => 'bg-slate-100 text-slate-700'];
    $labels = ['superadmin' => __('Super admin'), 'admin' => __('Admin'), 'operatore' => __('Operator')];
@endphp
<span class="badge {{ $classes[$role] ?? 'bg-slate-100 text-slate-700' }}">{{ $labels[$role] ?? $role }}</span>
