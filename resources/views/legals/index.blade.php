<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Legal</h2>
  </x-slot>

  <div style="background: linear-gradient(45deg, black, transparent); padding-top: 5px; min-height: 100vh;">
    <div class="mx-auto sm:px-6 lg:px-8" x-data="loanPageState({
      legalForm: null,
      searchTerm: '',
      statusFilter: ''
    })" x-init="tableSort.legal = { columnIndex: 0, direction: 'asc', type: 'string' }; applyTableSort('legal')">
      <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <input type="text" x-model.debounce.300ms="searchTerm" placeholder="Search deal, project or client..."
          class="w-full sm:max-w-sm rounded-md border-gray-300" />
        <select x-model="statusFilter" class="w-full sm:w-64 rounded-md border-gray-300">
          <option value="">All Status</option>
          @foreach($statusOptions as $status)
            <option value="{{ $status }}">{{ $status }}</option>
          @endforeach
        </select>
      </div>

      <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
        <table class="min-w-full text-sm bg-indigo-50" x-ref="legal">
          <thead class="bg-indigo-500 text-white">
            <tr>
              <th class="px-4 py-3 text-left font-semibold">
                <button type="button" class="inline-flex items-center gap-1"
                  @click="toggleTableSort('legal', 0, 'string')">
                  Project <span x-text="tableSortIndicator('legal', 0)"></span>
                </button>
              </th>
              <th class="px-4 py-3 text-left font-semibold">
                <button type="button" class="inline-flex items-center gap-1"
                  @click="toggleTableSort('legal', 1, 'string')">
                  Client <span x-text="tableSortIndicator('legal', 1)"></span>
                </button>
              </th>
              <th class="px-4 py-3 text-left font-semibold">
                <button type="button" class="inline-flex items-center gap-1"
                  @click="toggleTableSort('legal', 3, 'string')">
                  Lawyer Firm <span x-text="tableSortIndicator('legal', 3)"></span>
                </button>
              </th>
              <th class="px-4 py-3 text-left font-semibold">
                <button type="button" class="inline-flex items-center gap-1"
                  @click="toggleTableSort('legal', 4, 'date')">
                  SPA Date <span x-text="tableSortIndicator('legal', 4)"></span>
                </button>
              </th>
              <th class="px-4 py-3 text-left font-semibold">
                <button type="button" class="inline-flex items-center gap-1"
                  @click="toggleTableSort('legal', 5, 'date')">
                  Loan Agreement Date <span x-text="tableSortIndicator('legal', 5)"></span>
                </button>
              </th>
              <th class="px-4 py-3 text-left font-semibold">
                <button type="button" class="inline-flex items-center gap-1"
                  @click="toggleTableSort('legal', 6, 'date')">
                  Completion Date <span x-text="tableSortIndicator('legal', 6)"></span>
                </button>
              </th>
              <th class="px-4 py-3 text-center font-semibold">
                <button type="button" class="inline-flex items-center gap-1"
                  @click="toggleTableSort('legal', 7, 'string')">
                  Stamp Duty <span x-text="tableSortIndicator('legal', 7)"></span>
                </button>
              </th>
              <th class="px-4 py-3 text-left font-semibold">
                <button type="button" class="inline-flex items-center gap-1"
                  @click="toggleTableSort('legal', 2, 'string')">
                  Status <span x-text="tableSortIndicator('legal', 2)"></span>
                </button>
              </th>
              @if($canManageLoanRecords)
                <th class="px-4 py-3 text-right font-semibold">Action</th>
              @endif
            </tr>
          </thead>
          @php
            $sortedDeals = $deals->sort(function ($a, $b) {
              $aHasLegal = !is_null($a->legalCase);
              $bHasLegal = !is_null($b->legalCase);
              if ($aHasLegal !== $bHasLegal) {
                return $aHasLegal <=> $bHasLegal;
              }

              $aUpdated = optional($aHasLegal ? $a->legalCase?->updated_at : $a->updated_at)->timestamp ?? 0;
              $bUpdated = optional($bHasLegal ? $b->legalCase?->updated_at : $b->updated_at)->timestamp ?? 0;

              return $bUpdated <=> $aUpdated;
            })->values();
          @endphp
          <tbody class="divide-y divide-gray-200">
            @forelse($sortedDeals as $deal)
              @php
                $legal = $deal->legalCase;
                $hasLegal = !is_null($legal);
                $legalPayload = [
                  'deal_id' => $deal->id,
                  'deal_code' => $deal->deal_id,
                  'project_name' => $deal->project_name,
                  'client_name' => $deal->client?->name,
                  'status' => $legal?->status ?? 'Drafting',
                  'lawyer_firm' => $legal?->lawyer_firm,
                  'spa_date' => optional($legal?->spa_date)->format('Y-m-d'),
                  'loan_agreement_date' => optional($legal?->loan_agreement_date)->format('Y-m-d'),
                  'completion_date' => optional($legal?->completion_date)->format('Y-m-d'),
                  'stamp_duty' => (bool) ($legal?->stamp_duty ?? false),
                  'has_record' => $hasLegal,
                ];

                $statusClass = match ($legal?->status) {
                  'Drafting' => 'bg-gray-200 text-gray-700',
                  'Pending Bank' => 'bg-blue-100 text-blue-700',
                  'Pending Customer Signature' => 'bg-amber-100 text-amber-700',
                  'Completed' => 'bg-green-100 text-green-700',
                  default => '',
                };
              @endphp
              <tr data-sortable-row="1"
                x-show="((('{{ strtolower((string) ($deal->deal_id ?? '')) }}' + ' {{ strtolower((string) ($deal->project_name ?? '')) }}' + ' {{ strtolower((string) ($deal->client?->name ?? '')) }}').includes((searchTerm || '').toLowerCase()))) && ((!statusFilter) || ('{{ $legal?->status ?? 'Drafting' }}' === statusFilter))">
                <td class="px-4 py-3"
                  data-sort-value="{{ strtolower((string) ($deal->deal_id . ' ' . $deal->project_name)) }}">
                  <button type="button" class="text-left text-indigo-600 hover:underline"
                    @click="openLoanDetail({{ $deal->id }}, 'loan.legal.detail')">
                    {{ $deal->deal_id }}
                  </button>:<br>
                  {{ $deal->project_name }}
                </td>
                <td class="px-4 py-3" data-sort-value="{{ strtolower((string) ($deal->client?->name ?? '')) }}">
                  {{ $deal->client?->name ?? '-' }}
                </td>
                <td class="px-4 py-3" data-sort-value="{{ strtolower((string) ($legal?->lawyer_firm ?? '')) }}">
                  {{ $legal?->lawyer_firm ?? '-' }}
                </td>
                <td class="px-4 py-3" data-sort-value="{{ optional($legal?->spa_date)->format('Y-m-d') ?? '' }}">
                  {{ optional($legal?->spa_date)->format('Y-m-d') ?? '-' }}
                </td>
                <td class="px-4 py-3"
                  data-sort-value="{{ optional($legal?->loan_agreement_date)->format('Y-m-d') ?? '' }}">
                  {{ optional($legal?->loan_agreement_date)->format('Y-m-d') ?? '-' }}
                </td>
                <td class="px-4 py-3" data-sort-value="{{ optional($legal?->completion_date)->format('Y-m-d') ?? '' }}">
                  {{ optional($legal?->completion_date)->format('Y-m-d') ?? '-' }}
                </td>
                <td class="px-4 py-3 text-center" data-sort-value="{{ $legal?->stamp_duty ? 'yes' : 'no' }}">
                  <input type="checkbox" disabled {{ $legal?->stamp_duty ? 'checked' : '' }}
                    class="rounded border-gray-300 text-indigo-600" />
                </td>
                <td class="px-4 py-3" data-sort-value="{{ strtolower((string) ($legal?->status)) }}">
                  <span
                    class="inline-flex text-xs items-center px-2.5 py-1 rounded-full font-semibold {{ $statusClass }}">
                    {{ $legal?->status }}
                  </span>
                </td>
                @if($canManageLoanRecords)
                  <td class="px-4 py-3 text-right">
                    @if(($legal?->status ?? '') === 'Completed')
                      <span class="text-xs italic text-gray-600">closed</span>
                    @else
                      <button type="button" data-legal='@json($legalPayload)'
                        @click="legalForm = JSON.parse($el.dataset.legal); openModal('loan.legal.edit')"
                        class="px-3 py-2 text-white rounded-md {{ $hasLegal ? 'bg-indigo-600' : 'bg-green-600' }}">{{ $hasLegal ? 'Edit' : 'Add' }}</button>
                    @endif
                  </td>
                @endif
              </tr>
            @empty
              <tr>
                <td colspan="{{ $canManageLoanRecords ? '9' : '8' }}" class="px-4 py-6 text-center text-gray-600">No loan
                  approved deals found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>

        @include('loans.partials.loan-detail-modal', ['modalKey' => 'loan.legal.detail'])

        @if($canManageLoanRecords)
          <div x-show="isModalOpen('loan.legal.edit')" x-cloak x-transition:enter="transition ease-in-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in-out duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            @click.self="closeModal('loan.legal.edit')">
            <div x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0 scale-95"
              x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in-out duration-150"
              x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
              class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl">
              <div class="mb-4 flex items-center justify-between">
                <h4 class="text-lg font-semibold text-gray-900"
                  x-text="legalForm?.has_record ? 'Edit Legal Case' : 'Add Legal Case'">Edit Legal Case</h4>
                <button type="button" class="text-gray-500 hover:text-gray-700"
                  @click="closeModal('loan.legal.edit')">X</button>
              </div>

              <form method="POST" :action="'{{ route('legals.index') }}/' + (legalForm?.deal_id ?? '')">
                @method('PUT')
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Lawyer Firm</label>
                    <input type="text" name="lawyer_firm" x-model="legalForm.lawyer_firm"
                      class="w-full rounded-md border-gray-300" required />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">SPA Date</label>
                    <input type="date" name="spa_date" x-model="legalForm.spa_date"
                      class="w-full rounded-md border-gray-300" required />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Loan Agreement Date</label>
                    <input type="date" name="loan_agreement_date" x-model="legalForm.loan_agreement_date"
                      class="w-full rounded-md border-gray-300" required />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Completion Date</label>
                    <input type="date" name="completion_date" x-model="legalForm.completion_date"
                      class="w-full rounded-md border-gray-300" required />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                    <select name="status" x-model="legalForm.status" class="w-full rounded-md border-gray-300" required>
                      @foreach($statusOptions as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                      <input type="hidden" name="stamp_duty" :value="legalForm?.stamp_duty ? 1 : 0" />
                      <input type="checkbox" x-model="legalForm.stamp_duty"
                        class="rounded border-gray-300 text-indigo-600" />
                      Stamp Duty
                    </label>
                  </div>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                  <button type="button" @click="closeModal('loan.legal.edit')"
                    class="px-4 py-2 bg-gray-200 rounded-md">Cancel</button>
                  <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">Save</button>
                </div>
              </form>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
</x-app-layout>
