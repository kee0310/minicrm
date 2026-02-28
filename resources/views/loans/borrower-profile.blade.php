<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Loans / Borrower Profile</h2>
  </x-slot>

  @include('loans._tabs')

  <div class="mx-auto sm:px-6 lg:px-8"
    x-data="{ showClientModal: false, selectedClient: null, showEditModal: false, editClient: null, searchTerm: '', riskFilter: '' }">
    <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
      <input type="text" x-model.debounce.300ms="searchTerm" placeholder="Search client id or name..."
        class="w-full sm:max-w-sm rounded-md border-gray-300" />
      <select x-model="riskFilter" class="w-full sm:w-44 rounded-md border-gray-300">
        <option value="">All Risk</option>
        <option value="A">A</option>
        <option value="B">B</option>
        <option value="C">C</option>
        <option value="-">-</option>
      </select>
    </div>

    <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Client ID</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Client</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Risk</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Data Completeness</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Existing Loans</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Monthly Commitments</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Credit Card Limits</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Card Utilization %</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">CCRIS</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">CTOS</th>
            <th class="px-4 py-3 text-right font-semibold text-gray-700">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          @forelse($clients as $client)
            @php
              $financial = $client->financialCondition;
              $hasFinancial = !is_null($financial);
              $riskGrade = $financial?->riskGrade();
              $riskClass = $riskGrade === 'C' ? 'bg-red-100 text-red-700' : ($riskGrade === 'B' ? 'bg-amber-100 text-amber-700' : ($riskGrade === 'A' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'));
              $clientPayload = [
                'id' => $client->id,
                'client_id' => $client->client_id,
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'age' => $client->age,
                'ic_passport' => $client->ic_passport,
                'occupation' => $client->occupation,
                'company' => $client->company,
                'monthly_income' => $client->monthly_income,
                'risk_grade' => $riskGrade,
                'existing_loans' => $financial?->existing_loans,
                'monthly_commitments' => $financial?->monthly_commitments,
                'credit_card_limits' => $financial?->credit_card_limits,
                'credit_card_utilization' => $financial?->credit_card_utilization,
                'ccris' => $financial?->ccris,
                'ctos' => $financial?->ctos,
                'has_record' => $hasFinancial,
              ];
            @endphp
            <tr
              x-show="((('{{ strtolower((string) ($client->client_id ?? '')) }}' + ' {{ strtolower((string) ($client->name ?? '')) }}').includes((searchTerm || '').toLowerCase()))) && ((!riskFilter) || ('{{ $riskGrade ?? '-' }}' === riskFilter))">
              <td class="px-4 py-3">{{ $client->client_id ?? '-' }}</td>
              <td class="px-4 py-3">
                <button type="button" class="text-indigo-600 hover:underline" data-client='@json($clientPayload)'
                  @click="selectedClient = JSON.parse($el.dataset.client); showClientModal = true">
                  {{ $client->name ?? '-' }}
                </button>
              </td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex items-center px-2.5 py-1 rounded-full font-semibold {{ $riskClass }}">{{ $riskGrade ?? '-' }}</span>
              </td>
              <td class="px-4 py-3">
                {{ is_null($client->completeness_rate) ? '-' : $client->completeness_rate . '%' }}
              </td>
              <td class="px-4 py-3">{{ $financial?->existing_loans ?? '-' }}</td>
              <td class="px-4 py-3">{{ $financial?->monthly_commitments ?? '-' }}</td>
              <td class="px-4 py-3">{{ $financial?->credit_card_limits ?? '-' }}</td>
              <td class="px-4 py-3">{{ $financial?->credit_card_utilization ?? '-' }}</td>
              <td class="px-4 py-3">{{ $financial?->ccris ?? '-' }}</td>
              <td class="px-4 py-3">{{ $financial?->ctos ?? '-' }}</td>
              <td class="px-4 py-3 text-right">
                <button type="button" data-client='@json($clientPayload)'
                  @click="editClient = JSON.parse($el.dataset.client); showEditModal = true"
                  class="px-3 py-2 text-white rounded-md {{ $hasFinancial ? 'bg-indigo-600' : 'bg-green-600' }}">{{ $hasFinancial ? 'Edit' : 'Add' }}</button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="11" class="px-4 py-6 text-center text-gray-600">No clients found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>

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
            <h4 class="text-lg font-semibold text-gray-900"><span
                x-text="selectedClient?.client_id + ': ' + selectedClient?.name?? '-'"></h4>
            <button type="button" class="text-gray-500 hover:text-gray-700" @click="showClientModal = false">X</button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-y-2 gap-x-6 text-sm text-gray-700">
            <p><span class="font-semibold">Email:</span> <span x-text="selectedClient?.email ?? '-'"></span></p>
            <p><span class="font-semibold">Phone:</span> <span x-text="selectedClient?.phone ?? '-'"></span></p>
            <p><span class="font-semibold">Age:</span> <span x-text="selectedClient?.age ?? '-'"></span></p>
            <p><span class="font-semibold">IC/Passport:</span> <span x-text="selectedClient?.ic_passport ?? '-'"></span>
            </p>
            <p><span class="font-semibold">Occupation:</span> <span x-text="selectedClient?.occupation ?? '-'"></span>
            </p>
            <p><span class="font-semibold">Company:</span> <span x-text="selectedClient?.company ?? '-'"></span></p>
            <p><span class="font-semibold">Monthly Income:</span> <span
                x-text="selectedClient?.monthly_income ?? '-'"></span></p>
          </div>
        </div>
      </div>

      <div x-show="showEditModal" x-cloak x-transition:enter="transition ease-in-out duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in-out duration-150" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
        @click.self="showEditModal = false">
        <div x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0 scale-95"
          x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in-out duration-150"
          x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
          class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl">
          <div class="mb-4 flex items-center justify-between">
            <h4 class="text-lg font-semibold text-gray-900"
              x-text="editClient?.has_record ? 'Edit Financial Profile' : 'Add Financial Profile'">Edit Financial
              Profile</h4>
            <button type="button" class="text-gray-500 hover:text-gray-700" @click="showEditModal = false">X</button>
          </div>

          <form method="POST" :action="'{{ url('/loans/borrower-profile') }}/' + (editClient?.id ?? '')">
            @method('PUT')
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Existing Loans</label>
                <input type="number" step="0.01" name="existing_loans" x-model="editClient.existing_loans"
                  class="w-full rounded-md border-gray-300" />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Monthly Commitments</label>
                <input type="number" step="0.01" name="monthly_commitments" x-model="editClient.monthly_commitments"
                  class="w-full rounded-md border-gray-300" />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Credit Card Limits</label>
                <input type="number" step="0.01" name="credit_card_limits" x-model="editClient.credit_card_limits"
                  class="w-full rounded-md border-gray-300" />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Credit Card Utilization (%)</label>
                <input type="number" step="0.01" name="credit_card_utilization"
                  x-model="editClient.credit_card_utilization" class="w-full rounded-md border-gray-300" />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">CCRIS</label>
                <input type="text" name="ccris" x-model="editClient.ccris" class="w-full rounded-md border-gray-300" />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">CTOS</label>
                <input type="text" name="ctos" x-model="editClient.ctos" class="w-full rounded-md border-gray-300" />
              </div>
            </div>

            <div class="mt-5 flex justify-end gap-2">
              <button type="button" @click="showEditModal = false"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md">Cancel</button>
              <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">Save</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>