<button {{ $attributes->merge(['type' => 'submit', 'class' => 'crm-btn-primary']) }}>
    {{ $slot }}
</button>
