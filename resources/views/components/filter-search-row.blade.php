@props([
    'model' => 'searchTerm',
    'placeholder' => 'Search...',
    'requestValue' => '',
    'submitAction' => 'refreshList()',
    'clearAction' => null,
])

@php
    $clearAction ??= "{$model} = ''; {$submitAction}";
    $isEmpty = trim((string) $requestValue) === '';
@endphp

<div class="crm-filter-search-row">
    <div class="crm-filter-search-input-wrap">
        <input type="text" x-model="{{ $model }}" value="{{ $requestValue }}"
            @keydown.enter.prevent="{{ $submitAction }}" placeholder="{{ $placeholder }}"
            class="crm-filter-search-input" />
        <button type="button" :class="{{ $model }} ? 'opacity-100' : 'opacity-0 pointer-events-none'"
            @click="{{ $clearAction }}"
            class="crm-filter-search-clear {{ $isEmpty ? 'opacity-0 pointer-events-none' : 'opacity-100' }}"
            aria-label="Clear search">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <button type="button" @click="{{ $submitAction }}" class="crm-btn-secondary crm-filter-search-submit"
        aria-label="Search">
        <i class="fa-solid fa-magnifying-glass"></i>
    </button>
</div>
