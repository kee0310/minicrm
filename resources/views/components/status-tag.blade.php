@props([
    'type' => 'default',
])

@php
    $classes = match ($type) {
        'success' => 'crm-badge bg-emerald-100 text-emerald-700',
        'danger' => 'crm-badge bg-rose-100 text-rose-700',
        'warning' => 'crm-badge bg-amber-100 text-amber-700',
        'info' => 'crm-badge bg-blue-100 text-blue-700',
        default => 'crm-badge bg-slate-100 text-slate-700',
    };
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
