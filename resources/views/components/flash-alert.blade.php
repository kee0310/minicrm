@props([
    'message' => '',
    'title' => '',
    'icon' => '',
    'classes' => '',
    'autoDismiss' => true,
    'dismissMs' => 3200,
])

@php
    $xInit = $autoDismiss ? "setTimeout(() => show = false, {$dismissMs})" : '';
@endphp

<div x-data="{ show: true }" x-show="show" x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-2" x-init="{{ $xInit }}"
    class="pointer-events-auto flex items-start justify-between gap-3 rounded-xl border px-4 py-3 shadow-sm {{ $classes }}">
    <div class="flex items-start gap-3">
        <i class="fa-solid {{ $icon }} mt-0.5"></i>
        <div>
            <p class="text-sm font-semibold leading-5">{{ $title }}</p>
            <p class="text-sm leading-5">{{ $message }}</p>
        </div>
    </div>
    <button type="button" @click="show = false" class="text-current/70 hover:text-current" aria-label="Dismiss">
        <i class="fa-solid fa-xmark"></i>
    </button>
</div>
