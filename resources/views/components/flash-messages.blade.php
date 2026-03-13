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
        [
            'key' => 'deleted',
            'icon' => 'fa-trash-can',
            'title' => 'Deleted',
            'classes' => 'border-rose-200 bg-rose-50 text-rose-900',
        ],
    ];
    $flashItemsByKey = collect($flashItems)->keyBy('key');
    $deletedItem = $flashItemsByKey->get('deleted', $flashItems[0]);
    $resolveFlash = function (string $message, array $baseItem, bool $allowSticky) use ($deletedItem) {
        $isDeleteMessage = str_contains(strtolower($message), 'deleted');
        $item = $isDeleteMessage ? $deletedItem : $baseItem;
        $autoDismiss = $allowSticky ? !$isDeleteMessage : true;

        return [
            'message' => $message,
            'title' => $item['title'],
            'icon' => $item['icon'],
            'classes' => $item['classes'],
            'autoDismiss' => $autoDismiss,
        ];
    };
@endphp

<div class="space-y-2">
    @if ($errors->any())
        @php
            $validationMessage = (string) $errors->first();
            $validationClasses = 'border-rose-200 bg-rose-50 text-rose-900';
            $validationIcon = 'fa-circle-xmark';
            $validationTitle = 'Validation Error';
        @endphp
        <x-flash-alert :title="$validationTitle" :message="$validationMessage" :icon="$validationIcon" :classes="$validationClasses" :dismiss-ms="4200" />
    @endif

    @php
        $queryFlashMessage = (string) request('flash_message', '');
        $queryFlashType = (string) request('flash_type', 'success');
        $hasSessionFlash = $flashItemsByKey->keys()->contains(fn($key) => session($key));
        $queryFlashItem = $flashItemsByKey->get($queryFlashType, $flashItems[0]);
    @endphp
    @if (!$hasSessionFlash && $queryFlashMessage !== '')
        @php
            $resolved = $resolveFlash($queryFlashMessage, $queryFlashItem, false);
        @endphp
        <x-flash-alert :title="$resolved['title']" :message="$resolved['message']" :icon="$resolved['icon']" :classes="$resolved['classes']" :auto-dismiss="$resolved['autoDismiss']"
            :dismiss-ms="3200" />
        <script>
            (function() {
                const url = new URL(window.location.href);
                url.searchParams.delete('flash_message');
                url.searchParams.delete('flash_type');
                window.history.replaceState({}, '', url.toString());
            })();
        </script>
    @endif

    @foreach ($flashItems as $item)
        @if (session($item['key']))
            @php
                $resolved = $resolveFlash((string) session($item['key']), $item, true);
            @endphp
            <x-flash-alert :title="$resolved['title']" :message="$resolved['message']" :icon="$resolved['icon']" :classes="$resolved['classes']"
                :auto-dismiss="$resolved['autoDismiss']" :dismiss-ms="3200" />
        @endif
    @endforeach
</div>
