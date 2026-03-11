@props([
    'title' => null,
    'subtitle' => null,
])

<section {{ $attributes->merge(['class' => 'crm-card']) }}>
    @if ($title || $subtitle)
        <header class="border-b border-slate-200  px-6 py-4 ">
            @if ($title)
                <h3>{{ $title }}</h3>
            @endif
            @if ($subtitle)
                <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
            @endif
        </header>
    @endif
    <div class="crm-card-body">
        {{ $slot }}
    </div>
</section>
