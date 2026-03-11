<div class="crm-dash-month-nav inline-flex items-center rounded-lg border border-slate-300 bg-white p-1 shadow-sm">
    <a href="{{ $monthNav['prev_url'] }}"
        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-600 hover:bg-slate-100">
        <i class="fa-solid fa-chevron-left text-xs"></i>
    </a>
    <span
        class="inline-flex min-w-28 items-center justify-center rounded-md px-3 py-1.5 text-sm font-semibold text-slate-800">
        {{ $monthNav['label'] }}
    </span>
    <a href="{{ $monthNav['next_url'] }}"
        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-600 hover:bg-slate-100">
        <i class="fa-solid fa-chevron-right text-xs"></i>
    </a>
</div>
