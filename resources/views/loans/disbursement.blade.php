<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Loans / Disbursement</h2>
  </x-slot>

  @include('loans._tabs')

  <div class="mx-auto sm:px-6 lg:px-8"
    x-data="{ showEditModal: false, editDeal: null, showDealModal: false, selectedDeal: null, searchTerm: '', disbursementFilter: '' }">
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
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Project</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Client</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">First Disbursement Date</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Full Disbursement Date</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">SPA Completion Date</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Client Notification Date</th>
            <th class="px-4 py-3 text-right font-semibold text-gray-700">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          @forelse($approvedSubmissions as $submission)
            @php
              $deal = $submission->deal;
              $disbursement = $submission->disbursement;
              $hasDisbursement = !is_null($disbursement);
              $disbursementPayload = [
                'deal_id' => $deal?->id,
                'loan_id' => $submission->loan_id,
                'has_record' => $hasDisbursement,
                'first_disbursement_date' => optional($disbursement?->first_disbursement_date)->format('Y-m-d'),
                'full_disbursement_date' => optional($disbursement?->full_disbursement_date)->format('Y-m-d'),
                'spa_completion_date' => optional($disbursement?->spa_completion_date)->format('Y-m-d'),
                'client_notification_date' => optional($disbursement?->client_notification_date)->format('Y-m-d'),
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

              $i = 1;
            @endphp
            <tr
              x-show="((('{{ strtolower((string) ($deal->deal_id ?? '')) }}' + ' {{ strtolower((string) ($deal->project_name ?? '')) }}' + ' {{ strtolower((string) ($deal->client?->name ?? '')) }}').includes((searchTerm || '').toLowerCase()))) && ((!disbursementFilter) || (disbursementFilter === 'with' && {{ $hasDisbursement ? 'true' : 'false' }}) || (disbursementFilter === 'without' && {{ $hasDisbursement ? 'false' : 'true' }}))">
              <td class="px-4 py-3">
                <button type="button" class="text-left text-indigo-600 hover:underline" data-deal='@json($dealPayload)'
                  @click="selectedDeal = JSON.parse($el.dataset.deal); showDealModal = true">
                  {{ $deal->deal_id }}/{{ $i }}
                </button>:<br>
                {{ $deal->project_name }}
              </td>
              <td class="px-4 py-3">{{ $deal->client?->name ?? '-' }}</td>
              <td class="px-4 py-3">{{ optional($disbursement?->first_disbursement_date)->format('d M Y') ?? '-' }}</td>
              <td class="px-4 py-3">{{ optional($disbursement?->full_disbursement_date)->format('d M Y') ?? '-' }}</td>
              <td class="px-4 py-3">{{ optional($disbursement?->spa_completion_date)->format('d M Y') ?? '-' }}</td>
              <td class="px-4 py-3">{{ optional($disbursement?->client_notification_date)->format('d M Y') ?? '-' }}
              </td>
              <td class="px-4 py-3 text-right">
                <button type="button" data-disbursement='@json($disbursementPayload)'
                  @click="editDeal = JSON.parse($el.dataset.disbursement); showEditModal = true"
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
          <div class="grid grid-cols-1 gap-y-2 gap-x-6 text-sm text-gray-700">
            <p><span class="font-semibold">Developer:</span> <span x-text="selectedDeal?.developer ?? '-'"></span></p>
            <p><span class="font-semibold">Unit Number:</span> <span x-text="selectedDeal?.unit_number ?? '-'"></span>
            </p>
            <p><span class="font-semibold">Selling Price:</span> <span
                x-text="selectedDeal?.selling_price ?? '-'"></span></p>
            <p><span class="font-semibold">Created:</span> <span x-text="selectedDeal?.created_at ?? '-'"></span></p>
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
              x-text="editDeal?.has_record ? 'Edit Disbursement' : 'Add Disbursement'">Edit Disbursement</h4>
            <button type="button" class="text-gray-500 hover:text-gray-700" @click="showEditModal = false">X</button>
          </div>

          <form method="POST" :action="'{{ url('/loans/disbursement') }}/' + (editDeal?.deal_id ?? '')">
            @method('PUT')
            @csrf
            <input type="hidden" name="loan_id" :value="editDeal?.loan_id ?? ''">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div><label class="block text-xs font-medium text-gray-600 mb-1">First Disbursement Date</label><input
                  type="date" name="first_disbursement_date" x-model="editDeal.first_disbursement_date"
                  class="w-full rounded-md border-gray-300" /></div>
              <div><label class="block text-xs font-medium text-gray-600 mb-1">Full Disbursement Date</label><input
                  type="date" name="full_disbursement_date" x-model="editDeal.full_disbursement_date"
                  class="w-full rounded-md border-gray-300" /></div>
              <div><label class="block text-xs font-medium text-gray-600 mb-1">SPA Completion Date</label><input
                  type="date" name="spa_completion_date" x-model="editDeal.spa_completion_date"
                  class="w-full rounded-md border-gray-300" /></div>
              <div><label class="block text-xs font-medium text-gray-600 mb-1">Client Notification Date</label><input
                  type="date" name="client_notification_date" x-model="editDeal.client_notification_date"
                  class="w-full rounded-md border-gray-300" /></div>
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