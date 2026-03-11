<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Leads') }}
        </h2>
    </x-slot>

    <div class="space-y-6">

        <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            <article class="crm-kpi">
                <p class="crm-kpi-label">Total Leads</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($summary['total'] ?? 0) }}</p>
            </article>
            @foreach ($statuses as $status)
                @php
                    $key = strtolower(str_replace(' ', '_', $status));
                @endphp
                <article class="crm-kpi">
                    <p class="crm-kpi-label">{{ $status }}</p>
                    <p
                        class="mt-2 text-2xl font-semibold {{ strtolower($status) === 'lost' ? 'text-red-600' : (strtolower($status) === 'deal' ? 'text-green-600' : 'text-slate-900') }}">
                        {{ number_format($summary[$key] ?? 0) }}
                    </p>
                </article>
            @endforeach
        </section>

        @php
            $leadSourceOptions = [
                'Facebook',
                'Friend Referral',
                'Exhibition/Fair',
                'Company Assigned',
                'Old Client Referral',
            ];
        @endphp
        <x-card>
            <div class="text-gray-900" x-data="{
                leadFormOpen: false,
                leadFormMode: 'create',
                currentUserId: @js((int) (auth()->id() ?? 0)),
                canEditSalesperson: @js(auth()->user()?->hasRole(\App\Enums\RoleEnum::ADMIN->value) || auth()->user()?->hasRole(\App\Enums\RoleEnum::LEADER->value)),
                defaultLeadStatus: @js($statuses[0] ?? 'New'),
                leadForm: {},
                initialLeadFormOpen: @js($errors->any()),
                initialLeadFormMode: @js(old('_method') === 'PUT' ? 'edit' : 'create'),
                initialLeadForm: @js([
                    'id' => old('lead_id'),
                    'name' => old('name'),
                    'email' => old('email'),
                    'phone' => old('phone'),
                    'source' => old('source'),
                    'salesperson_id' => old('salesperson_id'),
                    'status' => old('status'),
                    'age' => old('age'),
                    'ic_passport' => old('ic_passport'),
                    'occupation' => old('occupation'),
                    'company' => old('company'),
                    'monthly_income' => old('monthly_income'),
                    'working_years' => old('working_years'),
                    'fixed_income' => old('fixed_income'),
                ]),
                searchTerm: @js(request('search', '')),
                statusFilter: @js(request('status', '')),
                ...tableListState({
                    endpoint: '{{ route('leads.index') }}',
                    filters: { statusFilter: 'status' },
                }),
                init() {
                    this.leadForm = this.emptyLeadForm();
                    if (this.initialLeadFormOpen) {
                        this.leadFormMode = this.initialLeadFormMode;
                        this.leadForm = { ...this.leadForm, ...this.initialLeadForm };
                        this.leadFormOpen = true;
                    }
                    this.$nextTick(() => this.toggleDealFields());
                },
                emptyLeadForm() {
                    const defaultSalespersonId = this.salespersonOptions.some(user => Number(user.id) === Number(this.currentUserId))
                        ? String(this.currentUserId)
                        : String(this.salespersonOptions[0]?.id ?? '');
                    return {
                        id: null,
                        name: '',
                        email: '',
                        phone: '',
                        source: '{{ $leadSourceOptions[0] ?? '' }}',
                        salesperson_id: defaultSalespersonId,
                        status: this.defaultLeadStatus,
                        age: '',
                        ic_passport: '',
                        occupation: '',
                        company: '',
                        monthly_income: '',
                        working_years: '',
                        fixed_income: '',
                    };
                },
                salespersonOptions: @js($salespersons->map(fn($user) => ['id' => $user->id, 'name' => $user->name])->values()),
                openCreateLead() {
                    this.leadFormMode = 'create';
                    this.leadForm = this.emptyLeadForm();
                    this.leadFormOpen = true;
                    this.$nextTick(() => this.toggleDealFields());
                },
                openEditLead(lead) {
                    this.leadFormMode = 'edit';
                    this.leadForm = { ...this.emptyLeadForm(), ...lead };
                    this.leadFormOpen = true;
                    this.$nextTick(() => this.toggleDealFields());
                },
                toggleDealFields() {
                    const wrap = document.getElementById('lead_deal_fields');
                    const status = this.leadForm?.status;
                    if (wrap) wrap.style.display = status === 'Deal' ? '' : 'none';
                }
            }">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium">{{ __('List of leads') }}</h3>
                </div>

                <div class="crm-filter-block">
                    <div class="crm-filter-toolbar">
                        <x-filter-search-row model="searchTerm" placeholder="Search name, email or phone..."
                            :request-value="request('search', '')" />
                        <button class="crm-create-btn sm:w-full" type="button"
                            @click="openCreateLead()">
                            {{ __('Create Lead') }}
                        </button>
                    </div>
                    <div class="crm-filter-tabs-scroll scrollbar-hide">
                        <div class="crm-filter-tabs">
                            <x-filter-tab-button state-key="statusFilter" value="" label="All"
                                :request-value="request('status', '')" all />
                            @if (!empty($statuses))
                                @foreach ($statuses as $s)
                                    <x-filter-tab-button state-key="statusFilter" :value="$s" :label="$s"
                                        :request-value="request('status', '')" />
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                <div id="live-table-container" @click="handleTableClick($event)">@include('leads.partials.leads-table', ['leads' => $leads])</div>
                @include('leads.partials.lead-form-modals', [
                    'leadSourceOptions' => $leadSourceOptions,
                    'salespersons' => $salespersons,
                    'statuses' => $statuses,
                ])


            </div>
    </div>
    </x-card>
</x-app-layout>
