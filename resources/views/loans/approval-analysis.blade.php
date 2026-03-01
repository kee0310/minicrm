<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Loans / Approval Analysis</h2>
  </x-slot>

  <div style="background: linear-gradient(45deg, black, transparent); padding-top: 5px; min-height: 100vh;">
    @include('loans._tabs')

    <div class="mx-auto sm:px-6 lg:px-8"
      x-data="{ showEditModal: false, editDeal: null, showDealModal: false, selectedDeal: null, searchTerm: '', bankFilter: '' }">
      {{-- Client-side search and approved bank filtering --}}
      <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <input type="text" x-model.debounce.300ms="searchTerm" placeholder="Search deal, project or client..."
          class="w-full sm:max-w-sm rounded-md border-gray-300" />
        <select x-model="bankFilter" class="w-full sm:w-52 rounded-md border-gray-300">
          <option value="">All Banks</option>
          @foreach($bankOptions as $bank)
            <option value="{{ $bank }}">{{ $bank }}</option>
          @endforeach
        </select>
      </div>

      <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
        <table class="min-w-full text-sm bg-teal-50">
          <thead class="bg-teal-500 text-white">
            <tr>
              <th class="px-4 py-3 text-left font-semibold">Project</th>
              <th class="px-4 py-3 text-left font-semibold">Client</th>
              <th class="px-4 py-3 text-left font-semibold">Approved Bank</th>
              <th class="px-4 py-3 text-left font-semibold">Applied Amount</th>
              <th class="px-4 py-3 text-left font-semibold">Approved Amount</th>
              <th class="px-4 py-3 text-left font-semibold">Interest Rate</th>
              <th class="px-4 py-3 text-left font-semibold">Lock-in Period</th>
              <th class="px-4 py-3 text-left font-semibold">MRTA / MLTA</th>
              <th class="px-4 py-3 text-left font-semibold">Special Conditions</th>
              <th class="px-4 py-3 text-left font-semibold">Deviation %</th>
              <th class="px-4 py-3 text-right font-semibold">Action</th>
            </tr>
          </thead>
          @php
            $sortedApprovedSubmissions = $approvedSubmissions->sort(function ($a, $b) {
              $aIsEmpty = is_null($a->applied_amount)
                && is_null($a->approved_amount)
                && is_null($a->interest_rate)
                && is_null($a->lock_in_period)
                && is_null($a->mrta_mlta)
                && is_null($a->special_conditions);
              $bIsEmpty = is_null($b->applied_amount)
                && is_null($b->approved_amount)
                && is_null($b->interest_rate)
                && is_null($b->lock_in_period)
                && is_null($b->mrta_mlta)
                && is_null($b->special_conditions);
              if ($aIsEmpty !== $bIsEmpty) {
                return $bIsEmpty <=> $aIsEmpty; // empty first
              }

              $aUpdated = optional($a->updated_at)->timestamp ?? 0;
              $bUpdated = optional($b->updated_at)->timestamp ?? 0;
              return $bUpdated <=> $aUpdated; // newest first
            })->values();
          @endphp
          {{-- Alpine x-show on each row uses the search and bank filter above --}}
          <tbody class="divide-y divide-gray-200">
            @forelse($sortedApprovedSubmissions as $submission)
              @php
                $deal = $submission->deal;
                $hasRecord = !(
                  is_null($submission->applied_amount)
                  && is_null($submission->approved_amount)
                  && is_null($submission->interest_rate)
                  && is_null($submission->lock_in_period)
                  && is_null($submission->mrta_mlta)
                  && is_null($submission->special_conditions)
                );
                $analysisPayload = [
                  'deal_id' => $deal?->id,
                  'loan_id' => $submission->loan_id,
                  'has_record' => $hasRecord,
                  'approved_bank' => $submission->approved_bank ?? $submission->bank_name,
                  'applied_amount' => $submission->applied_amount,
                  'approved_amount' => $submission->approved_amount,
                  'interest_rate' => $submission->interest_rate,
                  'lock_in_period' => $submission->lock_in_period,
                  'mrta_mlta' => $submission->mrta_mlta,
                  'special_conditions' => $submission->special_conditions,
                  'approval_deviation_percentage' => $submission->approval_deviation_percentage,
                ];

                $dealPayload = [
                  'deal_code' => $deal->deal_id,
                  'deal_status' => $deal->pipeline?->value,
                  'project_name' => $deal->project_name,
                  'developer' => $deal->developer,
                  'unit_number' => $deal->unit_number,
                  'selling_price' => $deal->selling_price,
                  'created_at' => optional($deal->created_at)->format('Y-m-d'),
                ];
                $i = 0;
                $i++;
              @endphp
              <tr
                x-show="((('{{ strtolower((string) ($deal->deal_id ?? '')) }}' + ' {{ strtolower((string) ($deal->project_name ?? '')) }}' + ' {{ strtolower((string) ($deal->client?->name ?? '')) }}').includes((searchTerm || '').toLowerCase()))) && ((!bankFilter) || ('{{ $submission->approved_bank ?? $submission->bank_name ?? '' }}' === bankFilter))">
                <td class="px-4 py-3">
                  <button type="button" class="text-left text-indigo-600 hover:underline" data-deal='@json($dealPayload)'
                    @click="selectedDeal = JSON.parse($el.dataset.deal); showDealModal = true">
                    {{ $deal->deal_id }}/{{ $i }}
                  </button>:<br>
                  {{ $deal->project_name }}
                </td>
                <td class="px-4 py-3">{{ $deal->client?->name ?? '-' }}</td>
              <td class="px-4 py-3"><b>{{ $submission->approved_bank ?? $submission->bank_name ?? '-' }}</b></td>
              <td class="px-4 py-3">{{ $submission->applied_amount ?? '-' }}</td>
              <td class="px-4 py-3">{{ $submission->approved_amount ?? '-' }}</td>
              <td class="px-4 py-3">{{ $submission->interest_rate ?? '-' }}</td>
              <td class="px-4 py-3">{{ $submission->lock_in_period ?? '-' }}</td>
              <td class="px-4 py-3">{{ $submission->mrta_mlta ?? '-' }}</td>
              <td class="px-4 py-3">{{ $submission->special_conditions ?? '-' }}</td>
              <td class="px-4 py-3">{{ $submission->approval_deviation_percentage ?? '-' }}</td>
                <td class="px-4 py-3 text-right">
                  <button type="button" data-analysis='@json($analysisPayload)'
                    @click="editDeal = JSON.parse($el.dataset.analysis); showEditModal = true"
                    class="px-3 py-2 text-white rounded-md {{ $hasRecord ? 'bg-indigo-600' : 'bg-green-600' }}">
                    {{ $hasRecord ? 'Edit' : 'Add' }}
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="11" class="px-4 py-6 text-center text-gray-600">
                  No approved loans found.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>

        {{-- Deal detail modal --}}
        <div x-show="showDealModal" x-cloak x-transition:enter="transition ease-in-out duration-200"
          x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
          x-transition:leave="transition ease-in-out duration-150" x-transition:leave-start="opacity-100"
          x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
          @click.self="showDealModal = false">
          <div x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in-out duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="w-max rounded-lg bg-white p-6 shadow-xl max-h-[90vh] overflow-y-auto">
            <div class="mb-4 flex items-center justify-between">
              <h4 class="text-lg font-semibold text-gray-900">
                <span x-text="selectedDeal?.deal_code ?? '-'"></span>:
                <span x-text="selectedDeal?.project_name ?? '-'"></span>
                <span class="inline-flex items-center rounded-full mx-2 px-2.5 py-1 text-xs font-semibold" :class="{
                  'bg-gray-100 text-gray-800': selectedDeal?.deal_status === 'New',
                  'bg-blue-100 text-blue-800': selectedDeal?.deal_status === 'Viewing',
                  'bg-yellow-100 text-yellow-800': selectedDeal?.deal_status === 'Booking',
                  'bg-purple-100 text-purple-800': selectedDeal?.deal_status === 'SPA Signed',
                  'bg-orange-100 text-orange-800': selectedDeal?.deal_status === 'Loan Submitted',
                  'bg-green-100 text-green-800': selectedDeal?.deal_status === 'Loan Approved',
                  'bg-indigo-100 text-indigo-800': selectedDeal?.deal_status === 'Legal Processing',
                  'bg-emerald-100 text-emerald-800': selectedDeal?.deal_status === 'Completed',
                  'bg-teal-100 text-teal-800': selectedDeal?.deal_status === 'Commission Paid',
                  'bg-gray-100 text-gray-600': !selectedDeal?.deal_status
                }" x-text="selectedDeal?.deal_status ?? '-'"></span>
              </h4>
              <button type="button" class="text-gray-500 hover:text-gray-700 ml-3"
                @click="showDealModal = false">X</button>
            </div>
            <div class="grid grid-cols-1 gap-y-2 gap-x-6 text-sm">
              <p><span class="font-semibold">Developer:</span> <span x-text="selectedDeal?.developer ?? '-'"></span></p>
              <p><span class="font-semibold">Unit Number:</span> <span x-text="selectedDeal?.unit_number ?? '-'"></span>
              </p>
              <p><span class="font-semibold">Selling Price:</span> <span
                  x-text="selectedDeal?.selling_price ?? '-'"></span></p>
              <p><span class="font-semibold">Created:</span> <span x-text="selectedDeal?.created_at ?? '-'"></span></p>
            </div>
          </div>
        </div>

        {{-- Add/Edit approval analysis modal --}}
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
                x-text="editDeal?.has_record ? 'Edit Approval Analysis' : 'Add Approval Analysis'">Edit Approval
                Analysis</h4>
              <button type="button" class="text-gray-500 hover:text-gray-700" @click="showEditModal = false">X</button>
            </div>

            <form method="POST" :action="'{{ url('/loans/approval-analysis') }}/' + (editDeal?.deal_id ?? '')">
              @csrf
              <input type="hidden" name="_method" :value="editDeal?.has_record ? 'PUT' : 'POST'">
              <input type="hidden" name="loan_id" :value="editDeal?.loan_id ?? ''">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div><label class="block text-xs font-medium text-gray-600 mb-1">Approved Bank</label><select
                    name="approved_bank" x-model="editDeal.approved_bank" class="w-full rounded-md border-gray-300">
                    <option value="">-</option>
                    @foreach($bankOptions as $bank)
                      <option value="{{ $bank }}">{{ $bank }}</option>
                    @endforeach
                  </select>
                </div>
                <div><label class="block text-xs font-medium text-gray-600 mb-1">Applied Amount</label><input
                    type="number" step="0.01" name="applied_amount" x-model="editDeal.applied_amount"
                    class="w-full rounded-md border-gray-300" /></div>
                <div><label class="block text-xs font-medium text-gray-600 mb-1">Approved Amount</label><input
                    type="number" step="0.01" name="approved_amount" x-model="editDeal.approved_amount"
                    class="w-full rounded-md border-gray-300" /></div>
                <div><label class="block text-xs font-medium text-gray-600 mb-1">Interest Rate</label><input
                    type="number" step="0.01" name="interest_rate" x-model="editDeal.interest_rate"
                    class="w-full rounded-md border-gray-300" /></div>
                <div><label class="block text-xs font-medium text-gray-600 mb-1">Lock-in Period</label><input
                    type="text" name="lock_in_period" x-model="editDeal.lock_in_period"
                    class="w-full rounded-md border-gray-300" />
                </div>
                <div><label class="block text-xs font-medium text-gray-600 mb-1">MRTA / MLTA</label><input type="text"
                    name="mrta_mlta" x-model="editDeal.mrta_mlta" class="w-full rounded-md border-gray-300" /></div>
                <div class="md:col-span-2"><label class="block text-xs font-medium text-gray-600 mb-1">Special
                    Conditions</label><input type="text" name="special_conditions" x-model="editDeal.special_conditions"
                    class="w-full rounded-md border-gray-300" /></div>
              </div>
              <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="showEditModal = false"
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
