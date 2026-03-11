@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
    'simple' => false,
    'lockScroll' => true,
    'centered' => false,
])

@php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
    '3xl' => 'sm:max-w-3xl',
    '4xl' => 'sm:max-w-4xl',
    '5xl' => 'sm:max-w-5xl',
    '6xl' => 'sm:max-w-6xl',
][$maxWidth];
@endphp

<div
    x-cloak
    x-data="{
        show: @js($show),
        focusables() {
            // All focusable element types...
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
            return [...$el.querySelectorAll(selector)]
                // All non-disabled elements...
                .filter(el => ! el.hasAttribute('disabled'))
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) -1 },
    }"
    x-init="$watch('show', value => {
        if (@js($lockScroll)) {
            if (value) {
                document.body.classList.add('overflow-y-hidden');
            } else {
                document.body.classList.remove('overflow-y-hidden');
            }
        }
        {{ $attributes->has('focusable') ? 'if (value) setTimeout(() => firstFocusable().focus(), 100)' : '' }}
    })"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-on:keydown.tab.prevent="$event.shiftKey || nextFocusable().focus()"
    x-on:keydown.shift.tab.prevent="prevFocusable().focus()"
    x-show="show"
    x-bind:style="show
        ? '{{ $centered ? 'display: flex !important;' : 'display: block !important;' }}'
        : 'display: none !important;'"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-8 sm:px-6"
    style="display: none;"
>
    <div
        x-show="show"
        class="fixed inset-0 transform transition-all"
        x-on:click="show = false"
        x-transition:enter="{{ $simple ? 'ease-out duration-120' : 'ease-out duration-300' }}"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="{{ $simple ? 'ease-in duration-100' : 'ease-in duration-200' }}"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="absolute inset-0 {{ $simple ? 'bg-slate-900/35' : 'bg-slate-900/45 backdrop-blur-sm' }}"></div>
    </div>

    <div
        x-show="show"
        class="{{ $centered ? 'fixed left-1/2 top-1/2 z-[60] mb-0 w-[calc(100vw-2rem)] -translate-x-1/2 -translate-y-1/2 sm:w-full' : 'mb-6 sm:mx-auto sm:w-full' }} overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl transform transition-all {{ $maxWidth }}"
        x-transition:enter="{{ $simple ? 'ease-out duration-120' : 'ease-out duration-250' }}"
        x-transition:enter-start="{{ $simple ? 'opacity-0' : 'opacity-0 translate-y-3 sm:translate-y-0 sm:scale-95' }}"
        x-transition:enter-end="{{ $simple ? 'opacity-100' : 'opacity-100 translate-y-0 sm:scale-100' }}"
        x-transition:leave="{{ $simple ? 'ease-in duration-100' : 'ease-in duration-180' }}"
        x-transition:leave-start="{{ $simple ? 'opacity-100' : 'opacity-100 translate-y-0 sm:scale-100' }}"
        x-transition:leave-end="{{ $simple ? 'opacity-0' : 'opacity-0 translate-y-3 sm:translate-y-0 sm:scale-95' }}"
    >
        {{ $slot }}
    </div>
</div>
