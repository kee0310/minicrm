@php
    $flashItems = [
        [
            'key' => 'success',
            'icon' => 'fa-circle-check',
            'title' => 'Success',
            'classes' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        ],
        [
            'key' => 'warning',
            'icon' => 'fa-triangle-exclamation',
            'title' => 'Warning',
            'classes' => 'border-amber-200 bg-amber-50 text-amber-900',
        ],
        [
            'key' => 'error',
            'icon' => 'fa-circle-xmark',
            'title' => 'Error',
            'classes' => 'border-rose-200 bg-rose-50 text-rose-900',
        ],
    ];
@endphp

<div class="space-y-2">
    @if ($errors->any())
        <div x-data="{ show: true }" x-show="show" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2" x-init="setTimeout(() => show = false, 4200)"
            class="pointer-events-auto flex items-start justify-between gap-3 rounded-xl border px-4 py-3 shadow-sm border-rose-200 bg-rose-50 text-rose-900">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-circle-xmark mt-0.5"></i>
                <div>
                    <p class="text-sm font-semibold leading-5">Validation Error</p>
                    <p class="text-sm leading-5">{{ $errors->first() }}</p>
                </div>
            </div>
            <button type="button" @click="show = false" class="text-current/70 hover:text-current" aria-label="Dismiss">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @foreach ($flashItems as $item)
        @if (session($item['key']))
            @php
                $message = (string) session($item['key']);
                $isDeleteSuccess = $item['key'] === 'success' && str_contains(strtolower($message), 'deleted');
                $classes = $isDeleteSuccess ? 'border-rose-200 bg-rose-50 text-rose-900' : $item['classes'];
                $icon = $isDeleteSuccess ? 'fa-trash-can' : $item['icon'];
                $title = $isDeleteSuccess ? 'Deleted' : $item['title'];
            @endphp
            <div x-data="{ show: true }" x-show="show" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2" x-init="setTimeout(() => show = false, 3200)"
                class="pointer-events-auto flex items-start justify-between gap-3 rounded-xl border px-4 py-3 shadow-sm {{ $classes }}">
                <div class="flex items-start gap-3">
                    <i class="fa-solid {{ $icon }} mt-0.5"></i>
                    <div>
                        <p class="text-sm font-semibold leading-5">{{ $title }}</p>
                        <p class="text-sm leading-5">{{ $message }}</p>
                    </div>
                </div>
                <button type="button" @click="show = false" class="text-current/70 hover:text-current"
                    aria-label="Dismiss">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        @endif
    @endforeach
</div>
