<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Commission</h2>
  </x-slot>

  <div style="background: linear-gradient(45deg, black, transparent); padding-top: 5px; min-height: 100vh;">
    <div class="mx-auto sm:px-6 lg:px-8" x-data="loanPageState({
      commissionForm: null,
      searchTerm: '',
      statusFilter: ''
    })">
      <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <input type="text" x-model.debounce.300ms="searchTerm" placeholder="Search deal, project or salesperson..."
          class="w-full sm:max-w-sm rounded-md border-gray-300" />
        <select x-model="statusFilter" class="w-full sm:w-44 rounded-md border-gray-300">
          <option value="">All Status</option>
          @foreach($statusOptions as $status)
            <option value="{{ $status }}">{{ $status }}</option>
          @endforeach
        </select>
      </div>

      <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
        <table class="min-w-full text-sm bg-emerald-50">
          <thead class="bg-emerald-500 text-white">
            <tr>
              <th class="px-4 py-3 text-left font-semibold">Salesperson Project</th>
              <th class="px-4 py-3 text-left font-semibold">Salesperson</th>
              <th class="px-4 py-3 text-left font-semibold">Total</th>
              <th class="px-4 py-3 text-left font-semibold">Paid</th>
              <th class="px-4 py-3 text-left font-semibold">Remaining</th>
              <th class="px-4 py-3 text-left font-semibold">Payment Status</th>
              <th class="px-4 py-3 text-right font-semibold">Action</th>
            </tr>
          </thead>
          @php
            $sortedCommissions = $commissions->sortByDesc('updated_at')->values();
          @endphp
          <tbody class="divide-y divide-gray-200">
            @forelse($sortedCommissions as $commission)
              @php
                $deal = $commission->deal;
                $total = (float) ($deal?->commission_amount ?? 0);
                $paid = (float) ($commission?->paid ?? 0);
                $remaining = max($total - $paid, 0);
                $paymentStatus = $commission?->payment_status ?? 'Unpaid';
                $statusClass = $paymentStatus === 'Paid'
                  ? 'bg-green-100 text-green-700'
                  : 'bg-amber-100 text-amber-700';
                $commissionPayload = [
                  'commission_id' => $commission->id,
                  'deal_id' => $deal->id,
                  'deal_code' => $deal->deal_id,
                  'project_name' => $deal->project_name,
                  'salesperson_name' => $deal->salesperson?->name,
                  'total' => $total,
                  'paid' => $paid,
                  'payment_status' => $paymentStatus,
                ];
              @endphp
              <tr
                x-show="((('{{ strtolower((string) ($deal?->deal_id ?? '')) }}' + ' {{ strtolower((string) ($deal?->project_name ?? '')) }}' + ' {{ strtolower((string) ($deal?->salesperson?->name ?? '')) }}').includes((searchTerm || '').toLowerCase()))) && ((!statusFilter) || ('{{ $paymentStatus }}' === statusFilter))">
                <td class="px-4 py-3">
                  <button type="button" class="text-left text-indigo-600 hover:underline"
                    @click="openLoanDetail({{ $deal?->id ?? 'null' }}, 'commission.detail')">
                    {{ $deal?->deal_id ?? '-' }}
                  </button>:<br>
                  {{ $deal?->project_name ?? '-' }}
                </td>
                <td class="px-4 py-3">{{ $deal?->salesperson?->name ?? '-' }}</td>
                <td class="px-4 py-3">{{ number_format($total, 2) }}</td>
                <td class="px-4 py-3">{{ number_format($paid, 2) }}</td>
                <td class="px-4 py-3">{{ number_format($remaining, 2) }}</td>
                <td class="px-4 py-3">
                  <span class="inline-flex text-xs items-center px-2.5 py-1 rounded-full font-semibold {{ $statusClass }}">
                    {{ $paymentStatus }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right">
                  <button type="button" data-commission='@json($commissionPayload)'
                    @click="commissionForm = JSON.parse($el.dataset.commission); openModal('commission.edit')"
                    class="px-3 py-2 text-white rounded-md bg-indigo-600">Edit</button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="px-4 py-6 text-center text-gray-600">No commission records found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>

        @include('loans.partials.loan-detail-modal', ['modalKey' => 'commission.detail'])

        <div x-show="isModalOpen('commission.edit')" x-cloak x-transition:enter="transition ease-in-out duration-200"
          x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
          x-transition:leave="transition ease-in-out duration-150" x-transition:leave-start="opacity-100"
          x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
          @click.self="closeModal('commission.edit')">
          <div x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in-out duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-xl rounded-lg bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
              <h4 class="text-lg font-semibold text-gray-900">Edit Commission</h4>
              <button type="button" class="text-gray-500 hover:text-gray-700"
                @click="closeModal('commission.edit')">X</button>
            </div>

            <form method="POST" :action="'{{ route('commissions.index') }}/' + (commissionForm?.commission_id ?? '')">
              @method('PUT')
              @csrf
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Project</label>
                  <input type="text" :value="(commissionForm?.deal_code ?? '-') + ' - ' + (commissionForm?.project_name ?? '-')"
                    class="w-full rounded-md border-gray-300 bg-gray-100" readonly />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Salesperson</label>
                  <input type="text" :value="commissionForm?.salesperson_name ?? '-'"
                    class="w-full rounded-md border-gray-300 bg-gray-100" readonly />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Total</label>
                  <input type="text" :value="Number(commissionForm?.total ?? 0).toFixed(2)"
                    class="w-full rounded-md border-gray-300 bg-gray-100" readonly />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Paid</label>
                  <input type="number" step="0.01" min="0" name="paid" x-model="commissionForm.paid"
                    class="w-full rounded-md border-gray-300" required />
                </div>
                <div class="md:col-span-2">
                  <label class="block text-xs font-medium text-gray-600 mb-1">Payment Status</label>
                  <select name="payment_status" x-model="commissionForm.payment_status"
                    class="w-full rounded-md border-gray-300" required>
                    @foreach($statusOptions as $status)
                      <option value="{{ $status }}">{{ $status }}</option>
                    @endforeach
                  </select>
                </div>
              </div>

              <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="closeModal('commission.edit')"
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
