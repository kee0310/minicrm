<x-app-layout>
    @php
        $headerSubtitle = $pageSubtitle ?? '';
    @endphp
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $pageTitle ?? 'Salesperson Performance' }}
                </h2>
            </div>
        </div>
    </x-slot>

    <a href="{{ url()->route('dashboard.index') }}"
        class="ml-4 inline-flex items-center gap-2 border border-slate-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 shadow-sm transition hover:bg-blue-500 hover:text-white">
        <span aria-hidden="true">&larr;</span>
        Back
    </a>

    <div class="space-y-6">
        <x-card>
            @php
                $activeTab = $activeTab ?? request('tab', 'salesperson');
                $isLeaderTab = $activeTab === 'leader';
                $listTitle = $isLeaderTab ? 'List of leaders' : 'List of salesperson';
                $emptyMessage = $isLeaderTab ? 'No leader data available.' : 'No salesperson data available.';
                $searchPlaceholder = $isLeaderTab ? 'Search leader...' : 'Search salesperson or leader...';
            @endphp
            <div class="text-gray-900" x-data="{
                searchTerm: @js(request('search', '')),
                monthFilter: @js($selectedMonth ?? ''),
                monthPickerOpen: false,
                monthPickerYear: null,
                monthPickerMonth: null,
                monthLabels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                initMonthPicker() {
                    const parts = String(this.monthFilter || '').split('-');
                    const year = Number(parts[0]) || new Date().getFullYear();
                    const month = Number(parts[1]) || (new Date().getMonth() + 1);
                    this.monthPickerYear = year;
                    this.monthPickerMonth = month;
                },
                monthPickerLabel() {
                    const label = this.monthLabels[(this.monthPickerMonth || 1) - 1] || 'Jan';
                    return `${label} ${this.monthPickerYear}`;
                },
                selectMonth(index) {
                    const monthValue = String(index + 1).padStart(2, '0');
                    this.monthPickerMonth = index + 1;
                    this.monthFilter = `${this.monthPickerYear}-${monthValue}`;
                    this.monthPickerOpen = false;
                    this.refreshList();
                },
                currentTab: @js($activeTab),
                ...tableListState({
                    endpoint: '{{ route('dashboard.salespeople') }}',
                    filters: { currentTab: 'tab', monthFilter: 'month' },
                }),
            }" x-init="initMonthPicker()">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h3 class="text-lg font-medium">{{ $listTitle }}</h3>
                </div>

                <div class="crm-filter-block">
                    <div class="crm-filter-toolbar">
                        <x-filter-search-row model="searchTerm" :placeholder="$searchPlaceholder" :request-value="request('search', '')" />
                        <div class="crm-month-picker" @click.outside="monthPickerOpen = false">
                            <span class="crm-form-label">Month</span>
                            <button type="button" class="crm-month-picker-trigger"
                                @click="monthPickerOpen = !monthPickerOpen" :aria-expanded="monthPickerOpen">
                                <span x-text="monthPickerLabel()"></span>
                                <i class="fa-solid fa-chevron-down text-[10px] text-slate-500"></i>
                            </button>
                            <div class="crm-month-picker-panel" x-show="monthPickerOpen" x-transition.origin.top.right>
                                <div class="crm-month-picker-year">
                                    <button type="button" class="crm-month-picker-year-btn" @click="monthPickerYear -= 1"
                                        aria-label="Previous year">
                                        <i class="fa-solid fa-chevron-left"></i>
                                    </button>
                                    <span class="crm-month-picker-year-label" x-text="monthPickerYear"></span>
                                    <button type="button" class="crm-month-picker-year-btn" @click="monthPickerYear += 1"
                                        aria-label="Next year">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </button>
                                </div>
                                <div class="crm-month-picker-grid">
                                    <template x-for="(label, index) in monthLabels" :key="label">
                                        <button type="button" class="crm-month-picker-month"
                                            :class="monthPickerMonth === (index + 1) ? 'is-active' : ''"
                                            @click="selectMonth(index)" x-text="label"></button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="crm-filter-tabs-scroll scrollbar-hide">
                        <div class="crm-filter-tabs">
                            <x-filter-tab-button state-key="currentTab" value="salesperson" label="Salesperson"
                                :request-value="$activeTab" />
                            <x-filter-tab-button state-key="currentTab" value="leader" label="Leader"
                                :request-value="$activeTab" />
                        </div>
                    </div>
                </div>


                <div class="crm-table-wrap">
                    @if ($isLeaderTab)
                        <table class="crm-table" data-sortable-table="true">
                            <thead>
                                <tr>
                                    <th class="w-[50px]"><span class="crm-sort-btn pointer-events-none">No.</span></th>
                                    <th data-sort-index="1">
                                        <span class="crm-sort-btn">Leader <span data-sort-indicator></span></span>
                                    </th>
                                    <th data-sort-index="2" data-sort-type="number">
                                        <span class="crm-sort-btn">Team Size <span data-sort-indicator></span></span>
                                    </th>
                                    <th data-sort-index="3" data-sort-type="number">
                                        <span class="crm-sort-btn">Team Converted Lead <span
                                                data-sort-indicator></span></span>
                                    </th>
                                    <th data-sort-index="4" data-sort-type="number">
                                        <span class="crm-sort-btn">Team Completed Deal <span
                                                data-sort-indicator></span></span>
                                    </th>
                                    <th data-sort-index="5" data-sort-type="number">
                                        <span class="crm-sort-btn">Team Deal Close Rate <span
                                                data-sort-indicator></span></span>
                                    </th>
                                    <th data-sort-index="6" data-sort-type="number">
                                        <span class="crm-sort-btn">Team Avg Complete Day <span
                                                data-sort-indicator></span></span>
                                    </th>
                                    <th data-sort-index="7" data-sort-type="number">
                                        <span class="crm-sort-btn">Team Total Commission <span
                                                data-sort-indicator></span></span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $index => $row)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td data-sort-value="{{ strtolower((string) $row['leader']) }}">
                                            {{ $row['leader'] }}
                                        </td>
                                        <td data-sort-value="{{ $row['team_size'] }}">
                                            {{ number_format($row['team_size']) }}
                                        </td>
                                        <td data-sort-value="{{ $row['converted_leads'] }}">
                                            {{ number_format($row['converted_leads']) }}
                                        </td>
                                        <td data-sort-value="{{ $row['completed_deals'] }}">
                                            {{ number_format($row['completed_deals']) }}
                                        </td>
                                        <td data-sort-value="{{ $row['close_rate'] }}">
                                            {{ number_format($row['close_rate'], 2) }}%
                                        </td>
                                        <td data-sort-value="{{ $row['avg_complete_days'] ?? '' }}">
                                            {{ $row['avg_complete_days'] !== null ? number_format($row['avg_complete_days'], 1) : '-' }}
                                        </td>
                                        <td data-sort-value="{{ $row['total_commission'] }}">
                                            RM {{ number_format($row['total_commission'], 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="crm-table-empty">{{ $emptyMessage }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @else
                        <table class="crm-table" data-sortable-table="true">
                            <thead>
                                <tr>
                                    <th class="w-[50px]"><span class="crm-sort-btn pointer-events-none">No.</span></th>
                                    <th data-sort-index="1">
                                        <span class="crm-sort-btn">Salesperson <span data-sort-indicator></span></span>
                                    </th>
                                    <th data-sort-index="2">
                                        <span class="crm-sort-btn">Leader <span data-sort-indicator></span></span>
                                    </th>
                                    <th data-sort-index="3" data-sort-type="number">
                                        <span class="crm-sort-btn">Converted Lead <span
                                                data-sort-indicator></span></span>
                                    </th>
                                    <th data-sort-index="4" data-sort-type="number">
                                        <span class="crm-sort-btn">Completed Deal <span
                                                data-sort-indicator></span></span>
                                    </th>
                                    <th data-sort-index="5" data-sort-type="number">
                                        <span class="crm-sort-btn">Deal Close Rate <span
                                                data-sort-indicator></span></span>
                                    </th>
                                    <th data-sort-index="6" data-sort-type="number">
                                        <span class="crm-sort-btn">Avg Complete Day <span
                                                data-sort-indicator></span></span>
                                    </th>
                                    <th data-sort-index="7" data-sort-type="number">
                                        <span class="crm-sort-btn">Total Commission <span
                                                data-sort-indicator></span></span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $index => $row)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td data-sort-value="{{ strtolower((string) $row['name']) }}">
                                            {{ $row['name'] }}
                                        </td>
                                        <td data-sort-value="{{ strtolower((string) $row['leader']) }}">
                                            {{ $row['leader'] }}
                                        </td>
                                        <td data-sort-value="{{ $row['converted_leads'] }}">
                                            {{ number_format($row['converted_leads']) }}
                                        </td>
                                        <td data-sort-value="{{ $row['completed_deals'] }}">
                                            {{ number_format($row['completed_deals']) }}
                                        </td>
                                        <td data-sort-value="{{ $row['close_rate'] }}">
                                            {{ number_format($row['close_rate'], 2) }}%
                                        </td>
                                        <td data-sort-value="{{ $row['avg_complete_days'] ?? '' }}">
                                            {{ $row['avg_complete_days'] !== null ? number_format($row['avg_complete_days'], 1) : '-' }}
                                        </td>
                                        <td data-sort-value="{{ $row['total_commission'] }}">
                                            RM {{ number_format($row['total_commission'], 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="crm-table-empty">{{ $emptyMessage }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endif
                </div>

                <div class="mt-4">
                    {{ $rows->onEachSide(1)->links() }}
                </div>
            </div>
        </x-card>
    </div>
</x-app-layout>
