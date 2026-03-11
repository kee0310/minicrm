<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Deals') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            <article class="crm-kpi">
                <p class="crm-kpi-label">Total Deals</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($summary['total'] ?? 0) }}</p>
            </article>
            @foreach ($stages as $stage)
                @php $key = strtolower(str_replace(' ', '_', $stage)); @endphp
                <article class="crm-kpi">
                    <p class="crm-kpi-label">{{ $stage }}</p>
                    <p
                        class="mt-2 text-2xl font-semibold {{ strtolower($stage) === 'completed' ? 'text-green-600' : 'text-slate-900' }}"">
                        {{ number_format($summary[$key] ?? 0) }}</p>
                </article>
            @endforeach
        </section>

        <x-card>
            <div class="text-gray-900" x-data="loanPageState({
                dealFormOpen: false,
                dealFormMode: 'create',
                canEditSalesperson: @js(auth()->user()?->hasRole(\App\Enums\RoleEnum::ADMIN->value) || auth()->user()?->hasRole(\App\Enums\RoleEnum::LEADER->value)),
                currentUserId: @js((int) (auth()->id() ?? 0)),
                defaultDealPipeline: @js($pipelines[0]->value ?? ''),
                dealForm: {},
                searchTerm: @js(request('search', '')),
                stageFilter: @js(request('stage', '')),
                ...tableListState({
                    endpoint: '{{ route('deals.index') }}',
                    filters: { stageFilter: 'stage' },
                }),
                emptyDealForm() {
                    const defaultSalespersonId = this.salespersonOptions.some(user => Number(user.id) === Number(this.currentUserId))
                        ? String(this.currentUserId)
                        : String(this.salespersonOptions[0]?.id ?? '');
                    return {
                        id: null,
                        pipeline: this.defaultDealPipeline,
                        lead_id: '',
                        project_name: '',
                        developer: '',
                        unit_number: '',
                        selling_price: '',
                        commission_percentage: '',
                        commission_amount: '',
                        booking_fee: '',
                        spa_date: '',
                        pipeline_locked: false,
                        salesperson_id: defaultSalespersonId,
                    };
                },
                salespersonOptions: @js($salespersons->map(fn($user) => ['id' => $user->id, 'name' => $user->name])->values()),
                openCreateDeal() {
                    this.dealFormMode = 'create';
                    this.dealForm = this.emptyDealForm();
                    this.dealFormOpen = true;
                    this.$nextTick(() => {
                        this.recalc();
                        this.toggleBookingAndSpa();
                    });
                },
                openEditDeal(deal) {
                    this.dealFormMode = 'edit';
                    this.dealForm = { ...this.emptyDealForm(), ...deal };
                    this.dealFormOpen = true;
                    this.$nextTick(() => {
                        this.recalc();
                        this.toggleBookingAndSpa();
                    });
                },
                toggleBookingAndSpa() {
                    const pipeline = this.dealForm?.pipeline;
                    const bookingGroup = document.getElementById('booking_fee_group');
                    const bookingInput = document.getElementById('booking_fee');
                    const spaGroup = document.getElementById('spa_date_group');
                    const spaInput = document.getElementById('spa_date');
                    const hideBoth = pipeline === 'Lead' || pipeline === 'Viewing';
                    const hideSpaOnly = pipeline === 'Booking';
                    const showBooking = !hideBoth;
                    const showSpa = !hideBoth && !hideSpaOnly;
                    const needsBooking = pipeline === 'Booking' || pipeline === 'SPA Signed';
                    const needsSpa = pipeline === 'SPA Signed';
                    if (bookingGroup && bookingInput) {
                        bookingGroup.style.display = showBooking ? '' : 'none';
                        bookingInput.required = needsBooking;
                    }
                    if (spaGroup && spaInput) {
                        spaGroup.style.display = showSpa ? '' : 'none';
                        spaInput.required = needsSpa;
                    }
                },
                recalc() {
                    const priceInput = document.getElementById('selling_price');
                    const pctInput = document.getElementById('commission_percentage');
                    const amountInput = document.getElementById('commission_amount');
                    const price = parseFloat(priceInput?.value || 0) || 0;
                    const pct = parseFloat(pctInput?.value || 0) || 0;
                    if (amountInput) amountInput.value = (price * pct / 100).toFixed(2);
                    if (this.dealForm) {
                        this.dealForm.commission_amount = amountInput?.value;
                    }
                }
            })">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium">{{ __('List of deals') }}</h3>
                </div>

                <div class="crm-filter-block">
                    <div class="crm-filter-toolbar">
                        <x-filter-search-row model="searchTerm" placeholder="Search project or lead..."
                            :request-value="request('search', '')" />
                        <button type="button"
                            @click="openCreateDeal()"
                            class="crm-create-btn">
                            {{ __('Create Deal') }}
                        </button>
                    </div>
                    <div class="crm-filter-tabs-scroll scrollbar-hide">
                        <div class="crm-filter-tabs">
                            <x-filter-tab-button state-key="stageFilter" value="" label="All" :request-value="request('stage', '')"
                                all />
                            @foreach ($stages as $stage)
                                <x-filter-tab-button state-key="stageFilter" :value="$stage" :label="$stage"
                                    :request-value="request('stage', '')" />
                            @endforeach
                        </div>
                    </div>
                </div>

                <div id="live-table-container" @click="handleTableClick($event)">@include('deals.partials.deals-table', ['deals' => $deals])</div>
                @include('deals.partials.deal-detail-modal', ['modalKey' => 'deal.detail'])
                @include('deals.partials.deal-form-modals', [
                    'pipelines' => $pipelines,
                    'leads' => $leads,
                    'salespersons' => $salespersons,
                ])

            </div>
        </x-card>
    </div>
</x-app-layout>
