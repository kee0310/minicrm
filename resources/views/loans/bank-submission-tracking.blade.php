<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Loans / Bank Submission Tracking</h2>
  </x-slot>

  @include('loans._tabs')

  <div class="mx-auto sm:px-6 lg:px-8"
    x-data="{ showCreateModal: false, createDealId: null, showEditModal: false, editSubmission: null, showDealModal: false, selectedDeal: null, searchTerm: '', statusFilter: '' }">
    <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
      <input type="text" x-model.debounce.300ms="searchTerm" placeholder="Search deal, project, client or bank..."
        class="w-full sm:max-w-sm rounded-md border-gray-300" />
      <select x-model="statusFilter" class="w-full sm:w-52 rounded-md border-gray-300">
        <option value="">All Status</option>
        <option value="No Submission">No Submission</option>
        @foreach($statusOptions as $status)
          <option value="{{ $status }}">{{ $status }}</option>
        @endforeach
      </select>
    </div>

    <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Project</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Client</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Bank</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Banker Contact</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Submission Date</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Doc Score</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Approval Status</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Expected Approval</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">File %</th>
            <th class="px-4 py-3 text-right font-semibold text-gray-700">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          @forelse($deals as $deal)
            @php
              $riskGrade = $deal->client?->financialCondition?->riskGrade();
              $riskClass = $riskGrade === 'C' ? 'bg-red-100 text-red-700' : ($riskGrade === 'B' ? 'bg-amber-100 text-amber-700' : ($riskGrade === 'A' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'));
              $pre = $deal->preQualification;
              $storedRecommendations = is_array($pre?->recommended_banks) ? $pre->recommended_banks : [];
              $hasStructuredRecommendations = !empty($storedRecommendations)
                && is_array($storedRecommendations[0] ?? null)
                && array_key_exists('bank', $storedRecommendations[0]);
              if ($hasStructuredRecommendations) {
                $recommendationBanks = collect([0, 1, 2])->map(fn($index) => $storedRecommendations[$index]['bank'] ?? null)->filter()->values()->all();
              } else {
                $recommendationBanks = collect($storedRecommendations)->filter()->values()->all();
              }
              $dealPayload = [
                'deal_code' => $deal->deal_id,
                'deal_status' => $deal->pipeline?->value,
                'project_name' => $deal->project_name,
                'developer' => $deal->developer,
                'unit_number' => $deal->unit_number,
                'selling_price' => $deal->selling_price,
                'created_at' => optional($deal->created_at)->format('Y-m-d'),
              ];
            @endphp
            @if($deal->bankSubmissions->isEmpty())
              <tr
                x-show="((('{{ strtolower((string) ($deal->deal_id ?? '')) }}' + ' {{ strtolower((string) ($deal->project_name ?? '')) }}' + ' {{ strtolower((string) ($deal->client?->name ?? '')) }}').includes((searchTerm || '').toLowerCase()))) && ((!statusFilter) || statusFilter === 'No Submission')">
                <td class="px-4 py-3">
                  <button type="button" class="text-left text-indigo-600 hover:underline" data-deal='@json($dealPayload)'
                    @click="selectedDeal = JSON.parse($el.dataset.deal); showDealModal = true">
                    {{ $deal->deal_id }}
                  </button>:<br>
                  {{ $deal->project_name }}
                </td>
                <td class="px-4 py-3">{{ $deal->client?->name ?? '-' }}</td>
                <td class="px-4 py-3">-</td>
                <td class="px-4 py-3">-</td>
                <td class="px-4 py-3">-</td>
                <td class="px-4 py-3">-</td>
                <td class="px-4 py-3">-</td>
                <td class="px-4 py-3">-</td>
                <td class="px-4 py-3">-</td>
                <td class="px-4 py-3 text-right">
                  <button type="button" @click="createDealId = {{ $deal->id }}; showCreateModal = true"
                    class="px-3 py-2 bg-green-600 text-white rounded-md">Add</button>
                </td>
              </tr>
            @else
              @foreach($deal->bankSubmissions as $submission)
                @php
                  $submissionPayload = [
                    'loan_id' => $submission->loan_id,
                    'bank_name' => $submission->bank_name,
                    'banker_contact' => $submission->banker_contact,
                    'submission_date' => optional($submission->submission_date)->format('Y-m-d'),
                    'document_completeness_score' => $submission->document_completeness_score,
                    'approval_status' => $submission->approval_status,
                    'expected_approval_date' => optional($submission->expected_approval_date)->format('Y-m-d'),
                    'file_completeness_percentage' => $submission->file_completeness_percentage,
                  ];
                @endphp
                <tr
                  x-show="((('{{ strtolower((string) ($deal->deal_id ?? '')) }}' + ' {{ strtolower((string) ($deal->project_name ?? '')) }}' + ' {{ strtolower((string) ($deal->client?->name ?? '')) }}' + ' {{ strtolower((string) ($submission->bank_name ?? '')) }}').includes((searchTerm || '').toLowerCase()))) && ((!statusFilter) || ('{{ $submission->approval_status }}' === statusFilter))">
                  <td class="px-4 py-3">
                    <button type="button" class="text-left text-indigo-600 hover:underline" data-deal='@json($dealPayload)'
                      @click="selectedDeal = JSON.parse($el.dataset.deal); showDealModal = true">
                      {{ $deal->deal_id }}
                    </button>:<br>
                    {{ $deal->project_name }}
                  </td>
                  <td class="px-4 py-3">{{ $deal->client?->name ?? '-' }}</td>
                  <td class="px-4 py-3">{{ $submission->bank_name }}</td>
                  <td class="px-4 py-3">{{ $submission->banker_contact ?? '-' }}</td>
                  <td class="px-4 py-3">{{ optional($submission->submission_date)->format('Y-m-d') ?? '-' }}</td>
                  <td class="px-4 py-3">{{ $submission->document_completeness_score ?? '-' }}</td>
                  <td class="px-4 py-3">{{ $submission->approval_status }}</td>
                  <td class="px-4 py-3">{{ optional($submission->expected_approval_date)->format('Y-m-d') ?? '-' }}</td>
                  <td
                    class="px-4 py-3 {{ is_null($submission->file_completeness_percentage) ? 'text-gray-500' : (($submission->file_completeness_percentage < 80) ? 'text-red-600' : 'text-green-600') }}">
                    {{ is_null($submission->file_completeness_percentage) ? '-' : $submission->file_completeness_percentage . '%' }}
                  </td>
                  <td class="px-4 py-3 text-right">
                    <button type="button" data-submission='@json($submissionPayload)'
                      @click="editSubmission = JSON.parse($el.dataset.submission); showEditModal = true"
                      class="px-3 py-2 bg-indigo-600 text-white rounded-md">Edit</button>
                  </td>
                </tr>
              @endforeach
            @endif
          @empty
            <tr>
              <td colspan="12" class="px-4 py-6 text-center text-gray-600">No deals in Booking/SPA Signed/Loan stages.
              </td>
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

      <div x-show="showCreateModal" x-cloak x-transition:enter="transition ease-in-out duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in-out duration-150" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
        @click.self="showCreateModal = false">
        <div x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0 scale-95"
          x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in-out duration-150"
          x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
          class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl">
          <div class="mb-4 flex items-center justify-between">
            <h4 class="text-lg font-semibold text-gray-900">Add Bank Submission</h4>
            <button type="button" class="text-gray-500 hover:text-gray-700" @click="showCreateModal = false">X</button>
          </div>
          <form method="POST" :action="'{{ url('/loans/bank-submission-tracking') }}/' + (createDealId ?? '')">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div><label class="block text-xs font-medium text-gray-600 mb-1">Bank Name</label><select name="bank_name"
                  class="w-full rounded-md border-gray-300" required>
                  <option value="">Select</option>@foreach($bankOptions as $bank)<option value="{{ $bank }}">
                    {{ $bank }}
                  </option>@endforeach
                </select></div>
              <div><label class="block text-xs font-medium text-gray-600 mb-1">Banker Contact</label><input type="text"
                  name="banker_contact" class="w-full rounded-md border-gray-300" /></div>
              <div><label class="block text-xs font-medium text-gray-600 mb-1">Submission Date</label><input type="date"
                  name="submission_date" class="w-full rounded-md border-gray-300" /></div>
              <div><label class="block text-xs font-medium text-gray-600 mb-1">Doc Score (1-5)</label><input
                  type="number" name="document_completeness_score" min="1" max="5"
                  class="w-full rounded-md border-gray-300" /></div>
              <div><label class="block text-xs font-medium text-gray-600 mb-1">Approval Status</label><select
                  name="approval_status" class="w-full rounded-md border-gray-300"
                  required>@foreach($statusOptions as $status)<option value="{{ $status }}">{{ $status }}</option>
                  @endforeach</select></div>
              <div><label class="block text-xs font-medium text-gray-600 mb-1">Expected Approval Date</label><input
                  type="date" name="expected_approval_date" class="w-full rounded-md border-gray-300" /></div>
              <div><label class="block text-xs font-medium text-gray-600 mb-1">File Completeness (%)</label><input
                  type="number" name="file_completeness_percentage" min="0" max="100"
                  class="w-full rounded-md border-gray-300" /></div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
              <button type="button" @click="showCreateModal = false"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md">Cancel</button>
              <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md">Add</button>
            </div>
          </form>
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
            <h4 class="text-lg font-semibold text-gray-900">Edit Bank Submission</h4>
            <button type="button" class="text-gray-500 hover:text-gray-700" @click="showEditModal = false">X</button>
          </div>
          <form method="POST"
            :action="'{{ url('/loans/bank-submission-tracking/submissions') }}/' + (editSubmission?.loan_id ?? '')">
            @method('PUT')
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div><label class="block text-xs font-medium text-gray-600 mb-1">Bank Name</label><select name="bank_name"
                  x-model="editSubmission.bank_name" class="w-full rounded-md border-gray-300"
                  required>@foreach($bankOptions as $bank)<option value="{{ $bank }}">{{ $bank }}</option>
                  @endforeach</select></div>
              <div><label class="block text-xs font-medium text-gray-600 mb-1">Banker Contact</label><input type="text"
                  name="banker_contact" x-model="editSubmission.banker_contact"
                  class="w-full rounded-md border-gray-300" /></div>
              <div><label class="block text-xs font-medium text-gray-600 mb-1">Submission Date</label><input type="date"
                  name="submission_date" x-model="editSubmission.submission_date"
                  class="w-full rounded-md border-gray-300" /></div>
              <div><label class="block text-xs font-medium text-gray-600 mb-1">Doc Score (1-5)</label><input
                  type="number" name="document_completeness_score" min="1" max="5"
                  x-model="editSubmission.document_completeness_score" class="w-full rounded-md border-gray-300" />
              </div>
              <div><label class="block text-xs font-medium text-gray-600 mb-1">Approval Status</label><select
                  name="approval_status" x-model="editSubmission.approval_status"
                  class="w-full rounded-md border-gray-300" required>@foreach($statusOptions as $status)<option
                  value="{{ $status }}">{{ $status }}</option>@endforeach</select></div>
              <div><label class="block text-xs font-medium text-gray-600 mb-1">Expected Approval Date</label><input
                  type="date" name="expected_approval_date" x-model="editSubmission.expected_approval_date"
                  class="w-full rounded-md border-gray-300" /></div>
              <div><label class="block text-xs font-medium text-gray-600 mb-1">File Completeness (%)</label><input
                  type="number" name="file_completeness_percentage" min="0" max="100"
                  x-model="editSubmission.file_completeness_percentage" class="w-full rounded-md border-gray-300" />
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