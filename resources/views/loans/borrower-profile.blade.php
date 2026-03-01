<x-app-layout>
  <x-slot name="header" class="bg-white">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Loans / Borrower Profile</h2>
  </x-slot>

  <div style="background: linear-gradient(45deg, black, transparent); padding-top: 5px; min-height: 100vh;">
    @include('loans._tabs')

    <div class="mx-auto sm:px-6 lg:px-8" x-data="loanPageState({
      editClient: null,
      searchTerm: '',
      riskFilter: ''
    })">
      {{-- Client-side search and risk filtering --}}
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
        <table class="min-w-full text-sm bg-pink-50">
          <thead class="bg-pink-500 text-white">
            <tr>
              <th class="px-4 py-3 text-left font-semibold">Project</th>
              <th class="px-4 py-3 text-left font-semibold">Client</th>
              <th class="px-4 py-3 text-left font-semibold">Risk</th>
              <th class="px-4 py-3 text-left font-semibold">Data Completeness</th>
              <th class="px-4 py-3 text-left font-semibold">Existing Loans</th>
              <th class="px-4 py-3 text-left font-semibold">Monthly Commitments</th>
              <th class="px-4 py-3 text-left font-semibold">Credit Card Limits</th>
              <th class="px-4 py-3 text-left font-semibold">Card Utilization %</th>
              <th class="px-4 py-3 text-left font-semibold">CCRIS</th>
              <th class="px-4 py-3 text-left font-semibold">CTOS</th>
              <th class="px-4 py-3 text-right font-semibold">Action</th>
            </tr>
          </thead>
          @php
            $sortedDeals = $deals->sort(function ($a, $b) {
              $aHasPre = !is_null($a->preQualification);
              $bHasPre = !is_null($b->preQualification);
              if ($aHasPre !== $bHasPre) {
                return $aHasPre <=> $bHasPre; // empty first
              }

              $aUpdated = optional($aHasPre ? $a->preQualification?->updated_at : $a->updated_at)->timestamp ?? 0;
              $bUpdated = optional($bHasPre ? $b->preQualification?->updated_at : $b->updated_at)->timestamp ?? 0;
              return $bUpdated <=> $aUpdated; // newest first
            })->values();
          @endphp
          {{-- Alpine x-show on each row uses the search and risk filter above --}}
          <tbody class="divide-y divide-gray-200">
            @forelse($sortedDeals as $deal)
              @php
                $client = $deal->client;
                $financial = $deal->preQualification;
                $hasFinancial = !is_null($financial);
                $riskGrade = $financial?->riskGrade() ?? $financial?->risk_grade;
                $riskClass = $riskGrade === 'C' ? 'bg-red-100 text-red-700' : ($riskGrade === 'B' ? 'bg-amber-100 text-amber-700' : ($riskGrade === 'A' ? 'bg-green-100 text-green-700' : ''));
                $clientPayload = [
                  'id' => $deal->id,
                  'deal_id' => $deal->deal_id,
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
                x-show="((('{{ strtolower((string) ($deal->deal_id ?? '')) }}' + ' {{ strtolower((string) ($deal->project_name ?? '')) }}' + ' {{ strtolower((string) ($deal->client?->name ?? '')) }}').includes((searchTerm || '').toLowerCase()))) && ((!riskFilter) || ('{{ $riskGrade ?? '-' }}' === riskFilter))">
                <td class="px-4 py-3">
                  <button type="button" class="text-left text-indigo-600 hover:underline"
                    @click="openLoanDetail({{ $deal->id }}, 'loan.borrower.detail')">
                    {{ $deal->deal_id }}
                  </button>:<br>
                  {{ $deal->project_name }}
                </td>
                <td class="px-4 py-3">{{ $deal->client?->name ?? '-' }}</td>
                <td class="px-4 py-3">
                  <span
                    class="inline-flex items-center px-2.5 py-1 rounded-full font-semibold {{ $riskClass }}">{{ $riskGrade ?? '-' }}</span>
                </td>
                <td class="px-4 py-3">
                  {{ is_null($client?->completeness_rate) ? '-' : $client->completeness_rate . '%' }}
                </td>
                <td class="px-4 py-3">{{ $financial?->existing_loans ?? '-' }}</td>
                <td class="px-4 py-3">{{ $financial?->monthly_commitments ?? '-' }}</td>
                <td class="px-4 py-3">{{ $financial?->credit_card_limits ?? '-' }}</td>
                <td class="px-4 py-3">
                  {{ $financial?->credit_card_utilization !== null ? $financial->credit_card_utilization . '%' : '-' }}
                </td>
                <td class="px-4 py-3">{{ $financial?->ccris ?? '-' }}</td>
                <td class="px-4 py-3">{{ $financial?->ctos ?? '-' }}</td>
                <td class="px-4 py-3 text-right">
                  <button type="button" data-client='@json($clientPayload)'
                    @click="editClient = JSON.parse($el.dataset.client); openModal('loan.borrower.edit')"
                    class="px-3 py-2 text-white rounded-md {{ $hasFinancial ? 'bg-indigo-600' : 'bg-green-600' }}">{{ $hasFinancial ? 'Edit' : 'Add' }}</button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="11" class="px-4 py-6 text-center text-gray-600">No deals found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>

        {{-- Loan detail modal --}}
        @include('loans.partials.loan-detail-modal', ['modalKey' => 'loan.borrower.detail'])

        {{-- Add/Edit financial profile modal --}}
        <div x-show="isModalOpen('loan.borrower.edit')" x-cloak x-transition:enter="transition ease-in-out duration-200"
          x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
          x-transition:leave="transition ease-in-out duration-150" x-transition:leave-start="opacity-100"
          x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
          @click.self="closeModal('loan.borrower.edit')">
          <div x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in-out duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
              <h4 class="text-lg font-semibold text-gray-900"
                x-text="editClient?.has_record ? 'Edit Financial Profile' : 'Add Financial Profile'">Edit Financial
                Profile</h4>
              <button type="button" class="text-gray-500 hover:text-gray-700"
                @click="closeModal('loan.borrower.edit')">X</button>
            </div>

            <form method="POST" :action="'{{ url('/loans/borrower-profile') }}/' + (editClient?.id ?? '')">
              @method('PUT')
              @csrf
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Existing Loans</label>
                  <input type="number" step="0.01" name="existing_loans" x-model="editClient.existing_loans"
                    class="w-full rounded-md border-gray-300" required />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Monthly Commitments</label>
                  <input type="number" step="0.01" name="monthly_commitments" x-model="editClient.monthly_commitments"
                    class="w-full rounded-md border-gray-300" required />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Credit Card Limits</label>
                  <input type="number" step="0.01" name="credit_card_limits" x-model="editClient.credit_card_limits"
                    class="w-full rounded-md border-gray-300" required />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Credit Card Utilization (%)</label>
                  <input type="number" name="credit_card_utilization" x-model="editClient.credit_card_utilization"
                    class="w-full rounded-md border-gray-300" required />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">CCRIS</label>
                  <input type="text" name="ccris" x-model="editClient.ccris"
                    class="w-full rounded-md border-gray-300" required />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">CTOS</label>
                  <input type="text" name="ctos" x-model="editClient.ctos" class="w-full rounded-md border-gray-300" required />
                </div>
              </div>

              <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="closeModal('loan.borrower.edit')"
                  class="px-4 py-2 bg-gray-200 rounded-md">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">Save</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
