<div x-show="isModalOpen('{{ $modalKey }}')" x-cloak x-transition:enter="transition ease-in-out duration-200"
  x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
  x-transition:leave="transition ease-in-out duration-150" x-transition:leave-start="opacity-100"
  x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-3"
  @click.self="closeModal('{{ $modalKey }}')">
  <div x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in-out duration-150"
    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
    class="w-full max-w-[680px] h-[90vh] overflow-y-auto border border-gray-300 bg-white p-5 shadow-2xl sm:p-7">
    <div class="mb-4 border-b border-gray-200 pb-3">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h4 class="text-xl font-bold text-gray-900">Loan Detail Report</h4>
          <p class="text-xs text-gray-500">Generated from current CRM loan records</p>
        </div>
        <button type="button" class="rounded border border-gray-300 px-3 py-1 text-xs text-gray-700 hover:bg-gray-100"
          @click="closeModal('{{ $modalKey }}')">Close</button>
      </div>
    </div>

    <template x-if="loanDetailLoading">
      <div class="rounded border border-gray-200 bg-gray-50 p-3 text-sm text-gray-600">
        Loading loan report...
      </div>
    </template>

    <div x-show="!loanDetailLoading" class="space-y-4 text-xs text-gray-800">
      <section class="rounded border border-gray-200 p-3">
        <h5 class="mb-2 text-sm font-semibold text-gray-900">Deal Summary</h5>
        <div class="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
          <p><span class="font-semibold">Deal Code:</span> <span x-text="selectedDeal?.deal_code ?? '-'"></span></p>
          <p>
            <span class="font-semibold">Deal Status:</span>
            <span class="inline-flex items-center rounded-full mx-1 px-2 py-0.5 font-semibold" :class="{
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
          </p>
          <p><span class="font-semibold">Project:</span> <span x-text="selectedDeal?.project_name ?? '-'"></span></p>
          <p><span class="font-semibold">Developer:</span> <span x-text="selectedDeal?.developer ?? '-'"></span></p>
          <p><span class="font-semibold">Unit Number:</span> <span x-text="selectedDeal?.unit_number ?? '-'"></span></p>
          <p><span class="font-semibold">Selling Price:</span> <span x-text="selectedDeal?.selling_price ?? '-'"></span>
          </p>
          <p><span class="font-semibold">Created Date:</span> <span x-text="selectedDeal?.created_at ?? '-'"></span></p>
        </div>
      </section>

      <section class="rounded border border-gray-200 p-3">
        <h5 class="mb-2 text-sm font-semibold text-gray-900">Client Detail</h5>
        <div class="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
          <p><span class="font-semibold">Client ID:</span> <span x-text="selectedDeal?.client?.client_id ?? '-'"></span>
          </p>
          <p><span class="font-semibold">Name:</span> <span x-text="selectedDeal?.client?.name ?? '-'"></span></p>
          <p><span class="font-semibold">Email:</span> <span x-text="selectedDeal?.client?.email ?? '-'"></span></p>
          <p><span class="font-semibold">Phone:</span> <span x-text="selectedDeal?.client?.phone ?? '-'"></span></p>
          <p><span class="font-semibold">Age:</span> <span x-text="selectedDeal?.client?.age ?? '-'"></span></p>
          <p><span class="font-semibold">IC / Passport:</span> <span
              x-text="selectedDeal?.client?.ic_passport ?? '-'"></span></p>
          <p><span class="font-semibold">Occupation:</span> <span
              x-text="selectedDeal?.client?.occupation ?? '-'"></span></p>
          <p><span class="font-semibold">Company:</span> <span x-text="selectedDeal?.client?.company ?? '-'"></span></p>
          <p><span class="font-semibold">Monthly Income:</span> <span
              x-text="selectedDeal?.client?.monthly_income ?? '-'"></span></p>
          <p><span class="font-semibold">Data Completeness:</span> <span
              x-text="selectedDeal?.client?.completeness_rate ?? '-'"></span></p>
        </div>
      </section>

      <section class="rounded border border-gray-200 p-3">
        <h5 class="mb-2 text-sm font-semibold text-gray-900">Borrower Profile</h5>
        <div class="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
          <p><span class="font-semibold">Risk:</span> <span
              x-text="selectedDeal?.borrower_profile?.risk_grade ?? '-'"></span></p>
          <p><span class="font-semibold">Existing Loans:</span> <span
              x-text="selectedDeal?.borrower_profile?.existing_loans ?? '-'"></span></p>
          <p><span class="font-semibold">Monthly Commitments:</span> <span
              x-text="selectedDeal?.borrower_profile?.monthly_commitments ?? '-'"></span></p>
          <p><span class="font-semibold">Credit Card Limits:</span> <span
              x-text="selectedDeal?.borrower_profile?.credit_card_limits ?? '-'"></span></p>
          <p><span class="font-semibold">Card Utilization:</span>
            <span
              x-text=" selectedDeal?.borrower_profile?.credit_card_utilization != null ? selectedDeal.borrower_profile.credit_card_utilization + '%' : '-'">
            </span>
          </p>
          <p><span class="font-semibold">CCRIS:</span> <span
              x-text="selectedDeal?.borrower_profile?.ccris ?? '-'"></span></p>
          <p><span class="font-semibold">CTOS:</span> <span x-text="selectedDeal?.borrower_profile?.ctos ?? '-'"></span>
          </p>
        </div>
      </section>

      <section class="rounded border border-gray-200 p-3">
        <h5 class="mb-2 text-sm font-semibold text-gray-900">Pre-Qualification</h5>
        <p class="mb-2"><span class="font-semibold">Date:</span> <span
            x-text="selectedDeal?.pre_qualification?.date ?? '-'"></span></p>
        <div class="overflow-x-auto">
          <table class="min-w-full border border-gray-200 text-left">
            <thead class="bg-gray-50">
              <tr>
                <th class="border border-gray-200 px-2 py-1">Bank</th>
                <th class="border border-gray-200 px-2 py-1">Approval Probability (%)</th>
                <th class="border border-gray-200 px-2 py-1">Loan Margin (%)</th>
              </tr>
            </thead>
            <tbody>
              <template x-if="!selectedDeal?.pre_qualification?.recommendations?.length">
                <tr>
                  <td colspan="3" class="border border-gray-200 px-2 py-1 text-gray-500">-</td>
                </tr>
              </template>
              <template x-for="(item, index) in (selectedDeal?.pre_qualification?.recommendations ?? [])" :key="index">
                <tr>
                  <td class="border border-gray-200 px-2 py-1" x-text="item?.bank ?? '-'"></td>
                  <td class="border border-gray-200 px-2 py-1" x-text="item?.approval_probability ?? '-'"></td>
                  <td class="border border-gray-200 px-2 py-1" x-text="item?.loan_margin ?? '-'"></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </section>

      <section class="rounded border border-gray-200 p-3">
        <h5 class="mb-2 text-sm font-semibold text-gray-900">Bank Submission Tracking</h5>
        <div class="overflow-x-auto">
          <table class="min-w-full border border-gray-200 text-left">
            <thead class="bg-gray-50">
              <tr>
                <th class="border border-gray-200 px-2 py-1">Bank</th>
                <th class="border border-gray-200 px-2 py-1">Status</th>
                <th class="border border-gray-200 px-2 py-1">Submission Date</th>
                <th class="border border-gray-200 px-2 py-1">File %</th>
              </tr>
            </thead>
            <tbody>
              <template x-if="!selectedDeal?.bank_submissions?.length">
                <tr>
                  <td colspan="5" class="border border-gray-200 px-2 py-1 text-gray-500 text-center italic">No data</td>
                </tr>
              </template>
              <template x-for="(item, index) in (selectedDeal?.bank_submissions ?? [])" :key="index">
                <tr>
                  <td class="border border-gray-200 px-2 py-1" x-text="item?.bank_name ?? '-'"></td>
                  <td class="border border-gray-200 px-2 py-1" x-text="item?.approval_status ?? '-'" :class="{
                    'text-gray-600': item?.approval_status === 'Prepared',
                    'text-blue-600': item?.approval_status === 'Submitted',
                    'text-amber-600': item?.approval_status === 'In Review',
                    'text-green-600': item?.approval_status === 'Approved',
                    'text-red-600': item?.approval_status === 'Rejected',
                  }">
                  </td>
                  <td class="border border-gray-200 px-2 py-1" x-text="item?.submission_date ?? '-'"></td>
                  <td class="border border-gray-200 px-2 py-1" x-text="item?.file_completeness_percentage ?? '-'"></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </section>

      <section class="rounded border border-gray-200 p-3">
        <h5 class="mb-2 text-sm font-semibold text-gray-900">Approval Analysis</h5>
        <div class="overflow-x-auto">
          <table class="min-w-full border border-gray-200 text-left">
            <thead class="bg-gray-50">
              <tr>
                <th class="border border-gray-200 px-2 py-1">Approved Bank</th>
                <th class="border border-gray-200 px-2 py-1">Applied Amount</th>
                <th class="border border-gray-200 px-2 py-1">Approved Amount</th>
                <th class="border border-gray-200 px-2 py-1">Interest Rate</th>
              </tr>
            </thead>
            <tbody>
              <template x-if="!selectedDeal?.approval_analysis?.length">
                <tr>
                  <td colspan="5" class="border border-gray-200 px-2 py-1 text-gray-500 text-center italic">No data</td>
                </tr>
              </template>
              <template x-for="(item, index) in (selectedDeal?.approval_analysis ?? [])" :key="index">
                <tr>
                  <td class="border border-gray-200 px-2 py-1" x-text="item?.approved_bank ?? '-'"></td>
                  <td class="border border-gray-200 px-2 py-1" x-text="item?.applied_amount ?? '-'"></td>
                  <td class="border border-gray-200 px-2 py-1" x-text="item?.approved_amount ?? '-'"></td>
                  <td class="border border-gray-200 px-2 py-1" x-text="item?.interest_rate ?? '-'"></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </section>

      <section class="rounded border border-gray-200 p-3">
        <h5 class="mb-2 text-sm font-semibold text-gray-900">Disbursement</h5>
        <div class="overflow-x-auto">
          <table class="min-w-full border border-gray-200 text-left">
            <thead class="bg-gray-50">
              <tr>
                <th class="border border-gray-200 px-2 py-1">First Disbursement</th>
                <th class="border border-gray-200 px-2 py-1">Full Disbursement</th>
                <th class="border border-gray-200 px-2 py-1">SPA Completion</th>
                <th class="border border-gray-200 px-2 py-1">Client Notification</th>
              </tr>
            </thead>
            <tbody>
              <template x-if="!selectedDeal?.disbursements?.length">
                <tr>
                  <td colspan="5" class="border border-gray-200 px-2 py-1 text-gray-500 text-center italic">No data</td>
                </tr>
              </template>
              <template x-for="(item, index) in (selectedDeal?.disbursements ?? [])" :key="index">
                <tr>
                  <td class="border border-gray-200 px-2 py-1" x-text="item?.first_disbursement_date ?? '-'"></td>
                  <td class="border border-gray-200 px-2 py-1" x-text="item?.full_disbursement_date ?? '-'"></td>
                  <td class="border border-gray-200 px-2 py-1" x-text="item?.spa_completion_date ?? '-'"></td>
                  <td class="border border-gray-200 px-2 py-1" x-text="item?.client_notification_date ?? '-'"></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </div>
</div>