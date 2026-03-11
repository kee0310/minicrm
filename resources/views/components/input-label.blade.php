@props(['value'])

<label {{ $attributes->merge(['class' => 'crm-form-label']) }}>
    {{ $value ?? $slot }}
</label>
