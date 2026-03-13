<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Clients') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <x-card>
            <div class="text-gray-900" x-data="{
                searchTerm: @js(request('search', '')),
                ...tableListState({
                    endpoint: '{{ route('clients.index') }}',
                }),
            }">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium">{{ __('List of clients') }}</h3>
                </div>

                <div class="crm-filter-block">
                    <div class="crm-filter-toolbar">
                        <x-filter-search-row model="searchTerm" placeholder="Search name, email or phone..."
                            :request-value="request('search', '')" />
                    </div>
                </div>

                @include('clients.partials.clients-table', ['clients' => $clients])
            </div>
        </x-card>
    </div>
</x-app-layout>
