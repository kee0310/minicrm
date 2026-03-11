<button {{ $attributes->merge(['type' => 'submit', 'class' => 'crm-btn-danger']) }}>
    {{ $slot }}
</button>
