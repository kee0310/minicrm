<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
      {{ __('Deals') }}
    </h2>
  </x-slot>

  @if(session('warning'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
      class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4 transition duration-500 ease-in-out">
      <p>{{ session('warning') }}</p>
    </div>
  @endif

  @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
      class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 transition duration-500 ease-in-out">
      <p>{{ session('success') }}</p>
    </div>
  @endif

  <div class="py-12">
    <div class="mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900" x-data="{ showClientModal: false, selectedClient: null }">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium">{{ __('List of deals') }}</h3>

            <div class="flex items-center justify-end">
              <a href="{{ route('deals.create') }}"
                class="inline-flex items-center px-4 py-2 my-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-wider hover:bg-green-800 focus:bg-green-800 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Create Deal') }}
              </a>
            </div>
          </div>

          <div class="mb-4">
            <form method="GET" action="{{ route('deals.index') }}" class="flex items-center space-x-2 text-xs">
              <div>
                <input type="search" name="search" placeholder="Search..." value="{{ request('search') }}"
                  class="w-full rounded-md border-gray-300 shadow-sm px-3 py-2 text-xs" />
              </div>
              <div>
                <select name="stage" type="filter" class="rounded-md border-gray-300 shadow-sm px-3 py-2 pr-8 text-xs">
                  <option value="">All Stages</option>
                  @foreach($stages as $stage)
                    <option value="{{ $stage }}" @selected(request('stage') == $stage)>{{ $stage }}</option>
                  @endforeach
                </select>
              </div>
              <div class="flex items-center space-x-2 text-[0.6rem]">
                <button type="submit"
                  class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white uppercase tracking-wider hover:bg-indigo-700 focus:outline-none">Filter</button>
                <a href="{{ route('deals.index') }}"
                  class="inline-flex items-center px-3 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-gray-700 hover:bg-gray-300">Clear</a>
              </div>
            </form>
          </div>

          <div id="live-table-container">
            @if(isset($deals) && $deals->count())
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <tr>
                      <th class="px-6 py-3">Deal ID</th>
                      <th class="px-6 py-3">Client</th>
                      <th class="px-6 py-3">Project</th>
                      <th class="px-6 py-3">Selling Price</th>
                      <th class="px-6 py-3">Commission</th>
                      <th class="px-6 py-3">Stage</th>
                      <th class="px-6 py-3">Salesperson</th>
                      <th class="px-6 py-3">Leader</th>
                      <th class="px-6 py-3">Created</th>
                      <th class="px-6 py-3">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200 text-sm text-gray-500 whitespace-nowrap">
                    @foreach($deals as $deal)
                      <tr>
                        <td class="px-6 py-4 text-gray-900">{{ $deal->deal_id }}</td>
                        @php
                          $client = $deal->client;
                          $financial = $client?->financialCondition;
                          $clientPayload = [
                            'client_id' => $client?->client_id,
                            'name' => $client?->name,
                            'email' => $client?->email,
                            'phone' => $client?->phone,
                            'age' => $client?->age,
                            'ic_passport' => $client?->ic_passport,
                            'occupation' => $client?->occupation,
                            'company' => $client?->company,
                            'monthly_income' => $client?->monthly_income,
                            'completeness_rate' => $client?->completeness_rate,
                            'existing_loans' => $financial?->existing_loans,
                            'monthly_commitments' => $financial?->monthly_commitments,
                            'credit_card_limits' => $financial?->credit_card_limits,
                            'credit_card_utilization' => $financial?->credit_card_utilization,
                            'ccris' => $financial?->ccris,
                            'ctos' => $financial?->ctos,
                            'risk_grade' => $financial?->risk_grade,
                          ];
                        @endphp
                        <td class="px-6 py-4">
                          <button type="button" class="text-indigo-600 hover:underline" data-client='@json($clientPayload)'
                            @click="selectedClient = JSON.parse($el.dataset.client); showClientModal = true">
                            {{ $client?->name ?? '-' }}
                          </button>
                        </td>
                        <td class="px-6 py-4">{{ $deal->project_name }}</td>
                        <td class="px-6 py-4">{{ number_format($deal->selling_price, 2) }}</td>
                        <td class="px-6 py-4">{{ number_format($deal->commission_amount, 2) }}</td>
                        <td class="px-6 py-4">
                          <span class="{{ $deal->pipeline->badge() }}">
                            {{ $deal->pipeline->value }}
                          </span>
                        </td>
                        <td class="px-6 py-4">{{ $deal->salesperson?->name }}</td>
                        <td class="px-6 py-4">{{ $deal->leader?->name }}</td>
                        <td class="px-6 py-4">{{ optional($deal->created_at)->format('Y-m-d') }}</td>
                        <td class="px-6 py-4">
                          <a href="{{ route('deals.edit', $deal) }}" class="text-indigo-600 hover:underline">Edit</a> |
                          <form method="POST" action="{{ route('deals.destroy', $deal) }}" class="inline"
                            onsubmit="return confirm('Confirm to delete deal {{ $deal->deal_id }}?');">
                            @method('DELETE')
                            @csrf
                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                          </form>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>

              <div class="mt-4">
                {{ $deals->links() }}
              </div>
            @else
              <div class="text-gray-600">{{ __('No deals found.') }}</div>
            @endif
          </div>


          <div x-show="showClientModal" x-cloak x-transition:enter="transition ease-in-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in-out duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            @click.self="showClientModal = false">
            <div x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0 scale-95"
              x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in-out duration-150"
              x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
              class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl">
              <div class="mb-4 flex items-center justify-between">
                <h4 class="text-lg font-semibold text-gray-900">Client Details</h4>
                <button type="button" class="text-gray-500 hover:text-gray-700"
                  @click="showClientModal = false">X</button>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-y-2 gap-x-6 text-sm text-gray-700">
                <p><span class="font-semibold">Client ID:</span> <span x-text="selectedClient?.client_id ?? '-'"></span>
                </p>
                <p><span class="font-semibold">Data Completeness:</span> <span
                    x-text="selectedClient?.completeness_rate != null ? (selectedClient.completeness_rate + '%') : '-'"></span>
                </p>
                <p><span class="font-semibold">Name:</span> <span x-text="selectedClient?.name ?? '-'"></span></p>
                <p><span class="font-semibold">Email:</span> <span x-text="selectedClient?.email ?? '-'"></span></p>
                <p><span class="font-semibold">Phone:</span> <span x-text="selectedClient?.phone ?? '-'"></span></p>
                <p><span class="font-semibold">Age:</span> <span x-text="selectedClient?.age ?? '-'"></span></p>
                <p><span class="font-semibold">IC/Passport:</span> <span
                    x-text="selectedClient?.ic_passport ?? '-'"></span></p>
                <p><span class="font-semibold">Occupation:</span> <span
                    x-text="selectedClient?.occupation ?? '-'"></span></p>
                <p><span class="font-semibold">Company:</span> <span x-text="selectedClient?.company ?? '-'"></span></p>
                <p><span class="font-semibold">Monthly Income:</span> <span
                    x-text="selectedClient?.monthly_income ?? '-'"></span></p>
                <p><span class="font-semibold">Existing Loans:</span> <span
                    x-text="selectedClient?.existing_loans ?? '-'"></span></p>
                <p><span class="font-semibold">Monthly Commitments:</span> <span
                    x-text="selectedClient?.monthly_commitments ?? '-'"></span></p>
                <p><span class="font-semibold">Credit Card Limits:</span> <span
                    x-text="selectedClient?.credit_card_limits ?? '-'"></span></p>
                <p><span class="font-semibold">Credit Card Utilization:</span> <span
                    x-text="selectedClient?.credit_card_utilization ?? '-'"></span></p>
                <p><span class="font-semibold">CCRIS:</span> <span x-text="selectedClient?.ccris ?? '-'"></span></p>
                <p><span class="font-semibold">CTOS:</span> <span x-text="selectedClient?.ctos ?? '-'"></span></p>
                <p><span class="font-semibold">Risk Grade:</span> <span
                    x-text="selectedClient?.risk_grade ?? '-'"></span></p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</x-app-layout>