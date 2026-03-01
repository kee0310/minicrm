<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Loans / Disbursement</h2>
  </x-slot>

  <div style="background: linear-gradient(45deg, black, transparent); padding-top: 5px; min-height: 100vh;">
    @include('loans._tabs')

    <div class="mx-auto sm:px-6 lg:px-8" x-data="loanPageState({
      editDeal: null,
      searchTerm: '',
      disbursementFilter: ''
    })">
      {{-- Client-side search and disbursement completion filtering --}}
      <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <input type="text" x-model.debounce.300ms="searchTerm" placeholder="Search deal, project or client..."
          class="w-full sm:max-w-sm rounded-md border-gray-300" />
        <select x-model="disbursementFilter" class="w-full sm:w-52 rounded-md border-gray-300">
          <option value="">All</option>
          <option value="with">With Disbursement</option>
          <option value="without">Without Disbursement</option>
        </select>
      </div>

      <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
        <table class="min-w-full text-sm bg-sky-50">
          <thead class="bg-sky-500 text-white">
            <tr>
              <th class="px-4 py-3 text-left font-semibold">Project</th>
              <th class="px-4 py-3 text-left font-semibold">Client</th>
              <th class="px-4 py-3 text-left font-semibold">First Disbursement Date</th>
              <th class="px-4 py-3 text-left font-semibold">Full Disbursement Date</th>
              <th class="px-4 py-3 text-left font-semibold">SPA Completion Date</th>
              <th class="px-4 py-3 text-left font-semibold">Client Notification Date</th>
              <th class="px-4 py-3 text-right font-semibold">Action</th>
            </tr>
          </thead>
          @php
            $sortedApprovedSubmissions = $approvedSubmissions->sort(function ($a, $b) {
              $aIsEmpty = is_null($a->first_disbursement_date)
                && is_null($a->full_disbursement_date)
                && is_null($a->spa_completion_date)
                && is_null($a->client_notification_date);
              $bIsEmpty = is_null($b->first_disbursement_date)
                && is_null($b->full_disbursement_date)
                && is_null($b->spa_completion_date)
                && is_null($b->client_notification_date);
              if ($aIsEmpty !== $bIsEmpty) {
                return $bIsEmpty <=> $aIsEmpty; // empty first
              }

              $aUpdated = optional($a->updated_at)->timestamp ?? 0;
              $bUpdated = optional($b->updated_at)->timestamp ?? 0;
              return $bUpdated <=> $aUpdated; // newest first
            })->values();
          @endphp
          {{-- Alpine x-show on each row uses the search and disbursement filter above --}}
          <tbody class="divide-y divide-gray-200">
            @forelse($sortedApprovedSubmissions as $submission)
              @php
                $deal = $submission->deal;
                $hasDisbursement = !(
                  is_null($submission->first_disbursement_date)
                  && is_null($submission->full_disbursement_date)
                  && is_null($submission->spa_completion_date)
                  && is_null($submission->client_notification_date)
                );
                $disbursementPayload = [
                  'deal_id' => $deal?->id,
                  'loan_id' => $submission->loan_id,
                  'has_record' => $hasDisbursement,
                  'first_disbursement_date' => optional($submission->first_disbursement_date)->format('Y-m-d'),
                  'full_disbursement_date' => optional($submission->full_disbursement_date)->format('Y-m-d'),
                  'spa_completion_date' => optional($submission->spa_completion_date)->format('Y-m-d'),
                  'client_notification_date' => optional($submission->client_notification_date)->format('Y-m-d'),
                ];

                $i = 1;
              @endphp
              <tr
                x-show="((('{{ strtolower((string) ($deal->deal_id ?? '')) }}' + ' {{ strtolower((string) ($deal->project_name ?? '')) }}' + ' {{ strtolower((string) ($deal->client?->name ?? '')) }}').includes((searchTerm || '').toLowerCase()))) && ((!disbursementFilter) || (disbursementFilter === 'with' && {{ $hasDisbursement ? 'true' : 'false' }}) || (disbursementFilter === 'without' && {{ $hasDisbursement ? 'false' : 'true' }}))">
                <td class="px-4 py-3">
                  <button type="button" class="text-left text-indigo-600 hover:underline"
                    @click="openLoanDetail({{ $deal->id }}, 'loan.disbursement.detail', {{ $submission->loan_id }})">
                    {{ $deal->deal_id }}/{{ $i }}
                  </button>:<br>
                  {{ $deal->project_name }}
                </td>
                <td class="px-4 py-3">{{ $deal->client?->name ?? '-' }}</td>
                <td class="px-4 py-3">{{ optional($submission->first_disbursement_date)->format('d M Y') ?? '-' }}</td>
                <td class="px-4 py-3">{{ optional($submission->full_disbursement_date)->format('d M Y') ?? '-' }}</td>
                <td class="px-4 py-3">{{ optional($submission->spa_completion_date)->format('d M Y') ?? '-' }}</td>
                <td class="px-4 py-3">{{ optional($submission->client_notification_date)->format('d M Y') ?? '-' }}
                </td>
                <td class="px-4 py-3 text-right">
                  <button type="button" data-disbursement='@json($disbursementPayload)'
                    @click="editDeal = JSON.parse($el.dataset.disbursement); openModal('loan.disbursement.edit')"
                    class="px-3 py-2 text-white rounded-md {{ $hasDisbursement ? 'bg-indigo-600' : 'bg-green-600' }}">{{ $hasDisbursement ? 'Edit' : 'Add' }}</button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="px-4 py-6 text-center text-gray-600">No approved loans found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>

        {{-- Loan detail modal --}}
        @include('loans.partials.loan-detail-modal', ['modalKey' => 'loan.disbursement.detail'])

        {{-- Add/Edit disbursement modal --}}
        <div x-show="isModalOpen('loan.disbursement.edit')" x-cloak x-transition:enter="transition ease-in-out duration-200"
          x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
          x-transition:leave="transition ease-in-out duration-150" x-transition:leave-start="opacity-100"
          x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
          @click.self="closeModal('loan.disbursement.edit')">
          <div x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in-out duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
              <h4 class="text-lg font-semibold text-gray-900"
                x-text="editDeal?.has_record ? 'Edit Disbursement' : 'Add Disbursement'">Edit Disbursement</h4>
              <button type="button" class="text-gray-500 hover:text-gray-700" @click="closeModal('loan.disbursement.edit')">X</button>
            </div>

            <form method="POST" :action="'{{ url('/loans/disbursement') }}/' + (editDeal?.deal_id ?? '')">
              @method('PUT')
              @csrf
              <input type="hidden" name="loan_id" :value="editDeal?.loan_id ?? ''">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div><label class="block text-xs font-medium text-gray-600 mb-1">First Disbursement Date</label><input
                    type="date" name="first_disbursement_date" x-model="editDeal.first_disbursement_date"
                    class="w-full rounded-md border-gray-300" required /></div>
                <div><label class="block text-xs font-medium text-gray-600 mb-1">Full Disbursement Date</label><input
                    type="date" name="full_disbursement_date" x-model="editDeal.full_disbursement_date"
                    class="w-full rounded-md border-gray-300" required /></div>
                <div><label class="block text-xs font-medium text-gray-600 mb-1">SPA Completion Date</label><input
                    type="date" name="spa_completion_date" x-model="editDeal.spa_completion_date"
                    class="w-full rounded-md border-gray-300" required /></div>
                <div><label class="block text-xs font-medium text-gray-600 mb-1">Client Notification Date</label><input
                    type="date" name="client_notification_date" x-model="editDeal.client_notification_date"
                    class="w-full rounded-md border-gray-300" required /></div>
              </div>
              <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="closeModal('loan.disbursement.edit')"
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



