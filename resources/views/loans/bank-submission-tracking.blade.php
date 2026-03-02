<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Loans / Bank Submission Tracking</h2>
  </x-slot>

  <div style="background: linear-gradient(45deg, black, transparent); padding-top: 5px; min-height: 100vh;">
    @include('loans._tabs')

    <div class="mx-auto sm:px-6 lg:px-8" x-data="loanPageState({
      bankForm: null,
      searchTerm: '',
      statusFilter: ''
    })">
      {{-- Client-side search and submission status filtering --}}
      <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <input type="text" x-model.debounce.300ms="searchTerm" placeholder="Search deal, project, client or bank..."
          class="w-full sm:max-w-sm rounded-md border-gray-300" />
        <select x-model="statusFilter" class="w-full sm:w-52 rounded-md border-gray-300">
          <option value="">All Status</option>
          @foreach($statusOptions as $status)
            <option value="{{ $status }}">{{ $status }}</option>
          @endforeach
        </select>
      </div>

      <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
        <table class="min-w-full text-sm bg-purple-50">
          <thead class="bg-purple-500 text-white">
            <tr>
              <th class="px-4 py-3 text-left font-semibold">Project</th>
              <th class="px-4 py-3 text-left font-semibold">Client</th>
              <th class="px-4 py-3 text-left font-semibold">Loan ID</th>
              <th class="px-4 py-3 text-left font-semibold">Bank</th>
              <th class="px-4 py-3 text-left font-semibold">Banker Contact</th>
              <th class="px-4 py-3 text-left font-semibold">Submission Date</th>
              <th class="px-4 py-3 text-left font-semibold">Doc Score</th>
              <th class="px-4 py-3 text-left font-semibold">Approval Status</th>
              <th class="px-4 py-3 text-left font-semibold">Expected Approval</th>
              <th class="px-4 py-3 text-left font-semibold">File %</th>
              @if($canManageLoanRecords)
                <th class="px-4 py-3 text-right font-semibold">Action</th>
              @endif
            </tr>
          </thead>
          @php
            $sortedDeals = $deals->sort(function ($a, $b) {
              $aIsEmpty = $a->bankSubmissions->isEmpty();
              $bIsEmpty = $b->bankSubmissions->isEmpty();
              if ($aIsEmpty !== $bIsEmpty) {
                return $bIsEmpty <=> $aIsEmpty; // empty first
              }

              $aUpdated = $aIsEmpty
                ? (optional($a->updated_at)->timestamp ?? 0)
                : ($a->bankSubmissions->max(fn($item) => optional($item->updated_at)->timestamp ?? 0) ?? 0);
              $bUpdated = $bIsEmpty
                ? (optional($b->updated_at)->timestamp ?? 0)
                : ($b->bankSubmissions->max(fn($item) => optional($item->updated_at)->timestamp ?? 0) ?? 0);
              return $bUpdated <=> $aUpdated; // newest first
            })->values();

            $submissionRows = collect();
            foreach ($sortedDeals as $deal) {
              if ($deal->bankSubmissions->isEmpty()) {
                $submissionRows->push([
                  'deal' => $deal,
                  'submission' => null,
                  'has_newer_submission' => false,
                ]);
                continue;
              }

              foreach ($deal->bankSubmissions->sortByDesc('updated_at') as $submission) {
                $hasNewerSubmission = $deal->bankSubmissions->contains(function ($item) use ($submission) {
                  if ((string) $item->loan_id === (string) $submission->loan_id) {
                    return false;
                  }

                  $itemTs = optional($item->created_at)->timestamp ?? 0;
                  $currentTs = optional($submission->created_at)->timestamp ?? 0;

                  return $itemTs > $currentTs;
                });

                $submissionRows->push([
                  'deal' => $deal,
                  'submission' => $submission,
                  'has_newer_submission' => $hasNewerSubmission,
                ]);
              }
            }
          @endphp
          <tbody class="divide-y divide-gray-200">
            @forelse($submissionRows as $row)
              @php
                $deal = $row['deal'];
                $submission = $row['submission'];
                $hasNewerSubmission = $row['has_newer_submission'];
                $hasSubmission = !is_null($submission);
                $createPayload = [
                  'mode' => 'create',
                  'deal_id' => (string) $deal->id,
                  'loan_id' => '',
                  'bank_name' => '',
                  'banker_contact' => '',
                  'submission_date' => '',
                  'document_completeness_score' => '',
                  'approval_status' => 'Prepared',
                  'expected_approval_date' => '',
                  'file_completeness_percentage' => '',
                ];

                $submissionPayload = $hasSubmission ? [
                  'mode' => 'edit',
                  'deal_id' => $deal->id,
                  'loan_id' => $submission->loan_id,
                  'bank_name' => $submission->bank_name,
                  'banker_contact' => $submission->banker_contact,
                  'submission_date' => optional($submission->submission_date)->format('Y-m-d'),
                  'document_completeness_score' => $submission->document_completeness_score,
                  'approval_status' => $submission->approval_status,
                  'expected_approval_date' => optional($submission->expected_approval_date)->format('Y-m-d'),
                  'file_completeness_percentage' => $submission->file_completeness_percentage,
                ] : null;

                $statusClass = match ($submission?->approval_status) {
                  'Prepared' => 'bg-gray-200 text-gray-700',
                  'Submitted' => 'bg-blue-100 text-blue-700',
                  'In Review' => 'bg-amber-100 text-amber-700',
                  'Approved' => 'bg-green-100 text-green-700',
                  'Rejected' => 'bg-red-100 text-red-700',
                  default => 'bg-gray-200 text-gray-600',
                };
              @endphp
              <tr
                x-show="((('{{ strtolower((string) ($deal->deal_id ?? '')) }}' + ' {{ strtolower((string) ($deal->project_name ?? '')) }}' + ' {{ strtolower((string) ($deal->client?->name ?? '')) }}' + ' {{ strtolower((string) ($submission?->bank_name ?? '')) }}').includes((searchTerm || '').toLowerCase()))) && ((!statusFilter) || ('{{ $submission?->approval_status ?? 'No Submission' }}' === statusFilter))">
                <td class="px-4 py-3">
                  <button type="button" class="text-left text-indigo-600 hover:underline"
                    @click="openLoanDetail({{ $deal->id }}, 'loan.bank.detail'{{ $hasSubmission ? ', ' . \Illuminate\Support\Js::from($submission->loan_id) : '' }})">
                    {{ $deal->deal_id }}
                  </button>:<br>
                  {{ $deal->project_name }}
                </td>
                <td class="px-4 py-3">{{ $deal->client?->name ?? '-' }}</td>
                <td class="px-4 py-3">{{ $submission?->loan_id ?? '-' }}</td>
                <td class="px-4 py-3">{{ $submission?->bank_name ?? '-' }}</td>
                <td class="px-4 py-3">{{ $submission?->banker_contact ?? '-' }}</td>
                <td class="px-4 py-3">{{ optional($submission?->submission_date)->format('Y-m-d') ?? '-' }}</td>
                <td class="px-4 py-3">{{ $submission?->document_completeness_score ?? '-' }}</td>
                <td class="px-4 py-3">
                  @if($hasSubmission)
                    <span class="inline-flex text-xs items-center px-2.5 py-1 rounded-full font-semibold {{ $statusClass }}">
                      {{ $submission->approval_status }}
                    </span>
                  @else
                    -
                  @endif
                </td>
                <td class="px-4 py-3">{{ optional($submission?->expected_approval_date)->format('Y-m-d') ?? '-' }}</td>
                <td
                  class="px-4 py-3 {{ is_null($submission?->file_completeness_percentage) ? 'text-gray-500' : (($submission->file_completeness_percentage < 80) ? 'text-red-600' : 'text-green-600') }}">
                  {{ is_null($submission?->file_completeness_percentage) ? '-' : $submission->file_completeness_percentage . '%' }}
                </td>
                @if($canManageLoanRecords)
                  <td class="px-4 py-3 text-right">
                    @if(!$hasSubmission)
                      <button type="button" data-submission='@json($createPayload)'
                        @click="bankForm = JSON.parse($el.dataset.submission); openModal('loan.bank.form')"
                        class="px-3 py-2 bg-green-600 text-white rounded-md">Add</button>
                    @elseif($submission->approval_status === 'Approved')
                      <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Closed</span>
                    @elseif($submission->approval_status === 'Rejected')
                      @if($hasNewerSubmission)
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Closed</span>
                      @else
                        <button type="button" data-submission='@json($createPayload)'
                          @click="bankForm = JSON.parse($el.dataset.submission); openModal('loan.bank.form')"
                          class="px-3 py-2 bg-violet-600 text-white rounded-md">New</button>
                      @endif
                    @else
                      <button type="button" data-submission='@json($submissionPayload)'
                        @click="bankForm = JSON.parse($el.dataset.submission); openModal('loan.bank.form')"
                        class="px-3 py-2 bg-indigo-600 text-white rounded-md">Edit</button>
                    @endif
                  </td>
                @endif
              </tr>
            @empty
              <tr>
                <td colspan="{{ $canManageLoanRecords ? '11' : '10' }}" class="px-4 py-6 text-center text-gray-600">No
                  deals in Booking/SPA Signed/Loan stages.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>

        {{-- Loan detail modal --}}
        @include('loans.partials.loan-detail-modal', ['modalKey' => 'loan.bank.detail'])

        @if($canManageLoanRecords)
          {{-- Shared create/edit bank submission modal --}}
          <div x-show="isModalOpen('loan.bank.form')" x-cloak x-transition:enter="transition ease-in-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in-out duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            @click.self="closeModal('loan.bank.form')">
            <div x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0 scale-95"
              x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in-out duration-150"
              x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
              class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl">
              <div class="mb-4 flex items-center justify-between">
                <h4 class="text-lg font-semibold text-gray-900"
                  x-text="bankForm?.mode === 'edit' ? 'Edit Bank Submission' : 'Create Case'">Edit Bank Submission</h4>
                <button type="button" class="text-gray-500 hover:text-gray-700"
                  @click="closeModal('loan.bank.form')">X</button>
              </div>
              <form method="POST" :action="bankForm?.mode === 'edit'
                        ? '{{ url('/loans/bank-submission-tracking/submissions') }}/' + (bankForm?.loan_id ?? '')
                        : '{{ route('loans.bank-submission-tracking.store') }}'">
                @csrf
                <input type="hidden" name="_method" :value="bankForm?.mode === 'edit' ? 'PUT' : 'POST'">
                <input type="hidden" name="deal_id" :value="bankForm?.deal_id ?? ''">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div><label class="block text-xs font-medium text-gray-600 mb-1">Bank Name</label><select
                      name="bank_name" x-model="bankForm.bank_name" class="w-full rounded-md border-gray-300" required>
                      <option value="">Select</option>@foreach($bankOptions as $bank)<option value="{{ $bank }}">
                        {{ $bank }}
                      </option>@endforeach
                    </select></div>
                  <div><label class="block text-xs font-medium text-gray-600 mb-1">Banker Contact</label><input
                      type="text" name="banker_contact" x-model="bankForm.banker_contact"
                      class="w-full rounded-md border-gray-300" required /></div>
                  <div><label class="block text-xs font-medium text-gray-600 mb-1">Submission Date</label><input
                      type="date" name="submission_date" x-model="bankForm.submission_date"
                      class="w-full rounded-md border-gray-300" required /></div>
                  <div><label class="block text-xs font-medium text-gray-600 mb-1">Doc Score (1-5)</label><input
                      type="number" name="document_completeness_score" min="1" max="5"
                      x-model="bankForm.document_completeness_score" class="w-full rounded-md border-gray-300" required />
                  </div>
                  <div><label class="block text-xs font-medium text-gray-600 mb-1">Approval Status</label><select
                      name="approval_status" x-model="bankForm.approval_status" class="w-full rounded-md border-gray-300"
                      required>@foreach($statusOptions as $status)<option value="{{ $status }}">{{ $status }}</option>
                      @endforeach</select></div>
                  <div><label class="block text-xs font-medium text-gray-600 mb-1">Expected Approval Date</label><input
                      type="date" name="expected_approval_date" x-model="bankForm.expected_approval_date"
                      class="w-full rounded-md border-gray-300" required /></div>
                  <div><label class="block text-xs font-medium text-gray-600 mb-1">File Completeness (%)</label><input
                      type="number" name="file_completeness_percentage" min="0" max="100"
                      x-model="bankForm.file_completeness_percentage" class="w-full rounded-md border-gray-300"
                      required /></div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                  <button type="button" @click="closeModal('loan.bank.form')"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md">Cancel</button>
                  <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md"
                    x-text="bankForm?.mode === 'edit' ? 'Save' : 'Create'">Save</button>
                </div>
              </form>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
</x-app-layout>
