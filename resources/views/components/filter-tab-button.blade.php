@props([
    'stateKey',
    'value' => '',
    'requestValue' => '',
    'label' => '',
    'all' => false,
])

@php
    $targetValue = (string) $value;
    $requestedValue = (string) $requestValue;
    $baseClass = $all ? 'crm-filter-tab crm-filter-tab-all' : 'crm-filter-tab crm-filter-tab-stage';
@endphp

<button type="button"
    @click="
        $el.closest('.crm-filter-tabs')?.querySelectorAll('.crm-filter-tab').forEach((tab) => tab.classList.remove('crm-filter-tab-active'));
        $el.classList.add('crm-filter-tab-active');
        {{ $stateKey }} = @js($targetValue);
        refreshList();
    "
    :class="{{ $stateKey }} === @js($targetValue) ? 'crm-filter-tab-active' : 'crm-filter-tab-inactive'"
    class="{{ $baseClass }} {{ $requestedValue === $targetValue ? 'crm-filter-tab-active' : 'crm-filter-tab-inactive' }}">
    {{ $label }}
</button>
