<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Loans / Pre-Qualification</h2>
  </x-slot>

  @include('loans._tabs')

  <div class=" mx-auto sm:px-6 lg:px-8"
    x-data="{ showEditModal: false, editDeal: null, showDealModal: false, selectedDeal: null, searchTerm: '', riskFilter: '' }">
    <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
      <input type="text" x-model.debounce.300ms="searchTerm" placeholder="Search deal, project or client..."
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
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Project</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Client</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Risk</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Recommended Bank 1</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Recommended Bank 2</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Recommended Bank 3</th>
            <th class="px-4 py-3 text-left font-semibold text-gray-700">Pre-Qualification Date</th>
            <th class="px-4 py-3 text-right font-semibold text-gray-700">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          @forelse($deals as $deal)
            @php
              $pre = $deal->preQualification;
              $riskGrade = $deal->client?->financialCondition?->riskGrade();
              $riskClass = $riskGrade === 'C' ? 'bg-red-100 text-red-700' : ($riskGrade === 'B' ? 'bg-amber-100 text-amber-700' : ($riskGrade === 'A' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'));
              $storedRecommendations = is_array($pre?->recommended_banks) ? $pre->recommended_banks : [];
              $hasStructuredRecommendations = !empty($storedRecommendations)
                && is_array($storedRecommendations[0] ?? null)
                && array_key_exists('bank', $storedRecommendations[0]);

              if ($hasStructuredRecommendations) {
                $recommendations = collect([0, 1, 2])->map(fn($index) => [
                  'bank' => $storedRecommendations[$index]['bank'] ?? null,
                  'approval_probability' => $storedRecommendations[$index]['approval_probability'] ?? null,
                  'loan_margin' => $storedRecommendations[$index]['loan_margin'] ?? null,
                ])->all();
              } else {
                $recommendations = collect([0, 1, 2])->map(fn($index) => [
                  'bank' => $storedRecommendations[$index] ?? null,
                  'approval_probability' => null,
                  'loan_margin' => null,
                ])->all();
              }
              $prePayload = [
                'deal_id' => $deal->id,
                'has_record' => !is_null($pre),
                'deal_code' => $deal->deal_id,
                'project_name' => $deal->project_name,
                'client_name' => $deal->client?->name,
                'pre_qualification_date' => optional($pre?->pre_qualification_date)->format('Y-m-d') ?? now()->format('Y-m-d'),
                'recommended_bank_1' => $recommendations[0]['bank'] ?? null,
                'recommended_bank_2' => $recommendations[1]['bank'] ?? null,
                'recommended_bank_3' => $recommendations[2]['bank'] ?? null,
                'approval_probability_1' => $recommendations[0]['approval_probability'] ?? null,
                'approval_probability_2' => $recommendations[1]['approval_probability'] ?? null,
                'approval_probability_3' => $recommendations[2]['approval_probability'] ?? null,
                'loan_margin_1' => $recommendations[0]['loan_margin'] ?? null,
                'loan_margin_2' => $recommendations[1]['loan_margin'] ?? null,
                'loan_margin_3' => $recommendations[2]['loan_margin'] ?? null,
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
            @endphp
            <tr
              x-show="((('{{ strtolower((string) ($deal->deal_id ?? '')) }}' + ' {{ strtolower((string) ($deal->project_name ?? '')) }}' + ' {{ strtolower((string) ($deal->client?->name ?? '')) }}').includes((searchTerm || '').toLowerCase()))) && ((!riskFilter) || ('{{ $riskGrade ?? '-' }}' === riskFilter))">
              <td class="px-4 py-3">
                <button type="button" class="text-left text-indigo-600 hover:underline" data-deal='@json($dealPayload)'
                  @click="selectedDeal = JSON.parse($el.dataset.deal); showDealModal = true">
                  {{ $deal->deal_id }}
                </button>:<br>
                {{ $deal->project_name }}
              </td>
              <td class="px-4 py-3">{{ $deal->client?->name }}</td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex items-center px-2.5 py-1 rounded-full font-semibold {{ $riskClass }}">{{ $riskGrade ?? '-' }}</span>
              </td>
              @for($i = 0; $i < 3; $i++)
                <td class="px-4 py-3">
                  @php $rec = $recommendations[$i] ?? null; @endphp

                  @if($rec)
                    <div class="grid">
                      @if(!empty($rec['bank']))
                        <b>{{ $rec['bank'] }}</b>
                      @else
                        -
                      @endif
                      @if(isset($rec['loan_margin']))
                        <em class="text-xs">
                          Loan Margin: {{ $rec['loan_margin'] }}%
                        </em>
                      @endif
                      @if(isset($rec['approval_probability']))
                        <em class="text-xs">
                          Approval Probability: {{ $rec['approval_probability'] }}%
                        </em>
                      @endif
                    </div>
                  @endif
                </td>
              @endfor
              <td class="px-4 py-3">{{ optional($pre?->pre_qualification_date)->format('Y-m-d') ?? '-' }}</td>
              <td class="px-4 py-3 text-right">
                <button type="button" data-pre='@json($prePayload)'
                  @click="editDeal = JSON.parse($el.dataset.pre); showEditModal = true"
                  class="px-3 py-2 text-white rounded-md {{ $pre ? 'bg-indigo-600' : 'bg-green-600' }}">{{ $pre ? 'Edit' : 'Add' }}</button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="px-4 py-6 text-center text-gray-600">No new deals found.</td>
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
          class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl max-h-[90vh] overflow-y-auto">
          <div class="mb-4 flex items-center justify-between">
            <h4 class="text-lg font-semibold text-gray-900"
              x-text="editDeal?.has_record ? 'Edit Pre-Qualification' : 'Add Pre-Qualification'">Edit
              Pre-Qualification</h4>
            <button type="button" class="text-gray-500 hover:text-gray-700" @click="showEditModal = false">X</button>
          </div>

          <form method="POST" :action="'{{ url('/loans/pre-qualification') }}/' + (editDeal?.deal_id ?? '')">
            @method('PUT')
            @csrf
            <div class="space-y-4">
              <div class="rounded-md border border-gray-200 p-3">
                <h5 class="mb-3 text-sm font-semibold text-gray-800">Recommendation 1</h5>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Recommended Bank</label>
                    <select name="recommended_bank_1" x-model="editDeal.recommended_bank_1"
                      class="w-full rounded-md border-gray-300">
                      <option value="">-</option>
                      @foreach($bankOptions as $bank)
                        <option value="{{ $bank }}">{{ $bank }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Approval Probability (%)</label>
                    <input type="number" name="approval_probability_1" min="0" max="100"
                      x-model="editDeal.approval_probability_1" class="w-full rounded-md border-gray-300" />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Loan Margin (%)</label>
                    <select name="loan_margin_1" x-model="editDeal.loan_margin_1"
                      class="w-full rounded-md border-gray-300">
                      <option value="">-</option>
                      <option value="70">70%</option>
                      <option value="80">80%</option>
                      <option value="90">90%</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="rounded-md border border-gray-200 p-3">
                <h5 class="mb-3 text-sm font-semibold text-gray-800">Recommendation 2</h5>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Recommended Bank</label>
                    <select name="recommended_bank_2" x-model="editDeal.recommended_bank_2"
                      class="w-full rounded-md border-gray-300">
                      <option value="">-</option>
                      @foreach($bankOptions as $bank)
                        <option value="{{ $bank }}">{{ $bank }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Approval Probability (%)</label>
                    <input type="number" name="approval_probability_2" min="0" max="100"
                      x-model="editDeal.approval_probability_2" class="w-full rounded-md border-gray-300" />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Loan Margin (%)</label>
                    <select name="loan_margin_2" x-model="editDeal.loan_margin_2"
                      class="w-full rounded-md border-gray-300">
                      <option value="">-</option>
                      <option value="70">70%</option>
                      <option value="80">80%</option>
                      <option value="90">90%</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="rounded-md border border-gray-200 p-3">
                <h5 class="mb-3 text-sm font-semibold text-gray-800">Recommendation 3</h5>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Recommended Bank</label>
                    <select name="recommended_bank_3" x-model="editDeal.recommended_bank_3"
                      class="w-full rounded-md border-gray-300">
                      <option value="">-</option>
                      @foreach($bankOptions as $bank)
                        <option value="{{ $bank }}">{{ $bank }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Approval Probability (%)</label>
                    <input type="number" name="approval_probability_3" min="0" max="100"
                      x-model="editDeal.approval_probability_3" class="w-full rounded-md border-gray-300" />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Loan Margin (%)</label>
                    <select name="loan_margin_3" x-model="editDeal.loan_margin_3"
                      class="w-full rounded-md border-gray-300">
                      <option value="">-</option>
                      <option value="70">70%</option>
                      <option value="80">80%</option>
                      <option value="90">90%</option>
                    </select>
                  </div>
                </div>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Pre-Qualification Date</label>
                <input type="date" name="pre_qualification_date" x-model="editDeal.pre_qualification_date"
                  class="w-full rounded-md border-gray-300" />
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