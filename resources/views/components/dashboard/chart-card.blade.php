@props([
    'title' => null,
])

<section {{ $attributes->merge(['class' => 'crm-card']) }}>
    @if (isset($header) && trim((string) $header) !== '')
        <header class="border-b border-slate-200 px-6 py-4">
            {{ $header }}
        </header>
    @elseif ($title)
        <header class="border-b border-slate-200 px-6 py-4">
            <h3>{{ $title }}</h3>
        </header>
    @endif
    <div class="crm-card-body">
        {{ $slot }}
    </div>
</section>
