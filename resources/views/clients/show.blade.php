<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Client Profile') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <a href="{{ url()->previous() }}"
            class="ml-4 inline-flex items-center gap-2 border border-slate-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 shadow-sm transition hover:bg-blue-500 hover:text-white">
            <span aria-hidden="true">&larr;</span>
            Back
        </a>

        <div class="grid grid-cols-3 gap-3" x-data="loanPageState()">
            <x-card class="crm-card-body grid-cols-1 h-min">
                <div class="grid items-center justify-between gap-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500">Client</p>
                    <h3 class="mt-1 text-2xl font-semibold text-slate-900">{{ $client->name ?? '-' }}</h3>
                </div>

                <div class="grid gap-2 mt-8 text-sm text-slate-700">
                    <div><span class="font-semibold">Lead ID:</span>
                        {{ $client->id ?? '-' }}</div>
                    <div><span class="font-semibold">Email:</span>
                        {{ $client->email ?? '-' }}</div>
                    <div><span class="font-semibold">Phone:</span>
                        {{ $client->phone ?? '-' }}</div>
                    <div><span class="font-semibold">Age:</span>
                        {{ $client->age ?? '-' }}</div>
                    <div><span class="font-semibold">IC/Passport:</span>
                        {{ $client->ic_passport ?? '-' }}</div>
                    <div><span class="font-semibold">Occupation:</span>
                        {{ $client->occupation ?? '-' }}</div>
                    <div><span class="font-semibold">Company:</span>
                        {{ $client->company ?? '-' }}</div>
                    <div><span class="font-semibold">Working Years:</span>
                        {{ $client->working_years ?? '-' }}</div>
                    <div><span class="font-semibold">Monthly Income:</span>
                        {{ $client->monthly_income ? 'RM ' . number_format($client->monthly_income, 2) : '-' }}</div>
                    <div><span class="font-semibold">Fixed Income:</span>
                        {{ $client->fixed_income ? 'RM ' . number_format($client->fixed_income, 2) : '-' }}</div>
                </div>
            </x-card>

            <x-card class="crm-card-body col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900">Deals</h3>
                    <span class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500">
                        {{ $deals->count() }} {{ \Illuminate\Support\Str::plural('Record', $deals->count()) }}
                    </span>
                </div>

                @if ($deals->count())
                    <div class="space-y-4">
                        @foreach ($deals as $deal)
                            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500">
                                            {{ $deal->deal_id }}</p>
                                        <p class="text-base font-semibold text-slate-900">{{ $deal->project_name }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="{{ $deal->pipeline->badge() }}">{{ $deal->pipeline->value }}</span>
                                        <button type="button" class="crm-btn-secondary text-xs text-blue-600"
                                            @click="openLoanDetail({{ $deal->id }}, 'client.deal.detail')">
                                            Details
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-4 grid grid-cols-1 gap-4 text-sm text-slate-700 lg:grid-cols-2">
                                    <div class="space-y-2">
                                        <p><span class="font-semibold">Developer:</span>
                                            {{ $deal->developer ?? '-' }}</p>
                                        <p><span class="font-semibold">Unit Number:</span>
                                            {{ $deal->unit_number ?? '-' }}</p>
                                        <p><span class="font-semibold">Selling Price:</span> RM
                                            {{ number_format($deal->selling_price, 2) }}</p>
                                        <p><span class="font-semibold">Created:</span>
                                            {{ optional($deal->created_at)->format('Y-m-d') ?? '-' }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <p><span class="font-semibold">Salesperson:</span>
                                            {{ $deal->salesperson?->name ?? '-' }}</p>
                                        <p><span class="font-semibold">Leader:</span>
                                            {{ $deal->leader?->name ?? '-' }}</p>
                                        <p><span class="font-semibold">Loan Officer:</span>
                                            {{ $deal->loanOfficer?->name ?? '-' }}</p>
                                        <p><span class="font-semibold">Legal Officer:</span>
                                            {{ $deal->legalOfficer?->name ?? '-' }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="crm-table-empty-inline">No deals found for this client.</div>
                @endif
            </x-card>

            @include('deals.partials.deal-detail-modal', ['modalKey' => 'client.deal.detail'])
        </div>
    </div>
</x-app-layout>
