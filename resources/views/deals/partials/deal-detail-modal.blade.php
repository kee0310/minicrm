<div x-show="isModalOpen('{{ $modalKey }}')" x-cloak x-transition:enter="transition ease-in-out duration-200"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in-out duration-150" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-3"
    @click.self="closeModal('{{ $modalKey }}')">
    <div x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in-out duration-150"
        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="w-full max-w-[680px] h-[90vh] overflow-y-auto border border-gray-300 bg-white p-5 shadow-2xl sm:p-7">

        <div x-show="!loanDetailLoading" class="space-y-4 text-xs text-gray-800">
            <div class="mb-4 border-b border-gray-200 pb-3">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h4 class="text-xl font-bold text-gray-900">Deal Detail Report</h4>
                        <p class="">
                            <span>Deal Code:</span> <span x-text="selectedDeal?.deal_code ?? '-'"></span>
                        </p>
                    </div>
                    <div class="grid gap-2 justify-items-end">
                        <span
                            class="inline-flex items-center rounded-full mx-1 px-2 py-0.5 font-semibold max-w-min whitespace-nowrap"
                            :class="{
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
                            }"
                            x-text="selectedDeal?.deal_status ?? '-'"></span>
                        <p class="text-gray-500">
                            <em>Created at:</em> <em x-text="selectedDeal?.created_at ?? '-'"></em>
                        </p>
                    </div>
                </div>
            </div>

            <section class="rounded border border-gray-200 p-3">
                <h5 class="mb-2 text-sm font-semibold text-gray-900">Deal Summary</h5>
                <div class="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                    <div class="space-y-1">
                        <p><span class="font-semibold">Project:</span> <span
                                x-text="selectedDeal?.project_name ?? '-'"></span></p>
                        <p><span class="font-semibold">Developer:</span> <span
                                x-text="selectedDeal?.developer ?? '-'"></span></p>
                        <p><span class="font-semibold">Unit Number:</span> <span
                                x-text="selectedDeal?.unit_number ?? '-'"></span></p>
                        <p><span class="font-semibold">Selling Price:</span> <span
                                x-text="selectedDeal?.selling_price ? new Intl.NumberFormat('ms-MY', { style: 'currency', currency: 'MYR' }).format(selectedDeal.selling_price) : '-'"></span>
                        </p>
                    </div>
                    <div class="space-y-1">
                        <p><span class="font-semibold">Salesperson:</span>
                            <span x-text="selectedDeal?.salesperson_name ?? '-'"></span>
                        </p>
                        <p><span class="font-semibold">Leader:</span>
                            <span x-text="selectedDeal?.leader_name ?? '-'"></span>
                        </p>
                        <p><span class="font-semibold">Loan Officer:</span> <span
                                x-text="selectedDeal?.loan_officer_name ?? '-'"></span></p>
                        <p><span class="font-semibold">Legal Officer:</span> <span
                                x-text="selectedDeal?.legal_officer_name ?? '-'"></span></p>
                    </div>
                </div>
            </section>

            <section class="rounded border border-gray-200 p-3">
                <div class="flex items-start justify-between gap-3">
                    <h5 class="mb-2 text-sm font-semibold text-gray-900">Client Detail</h5>
                    <p>Risk Grade - <b x-text="selectedDeal?.borrower_profile?.risk_grade ?? '-'"></b></p>
                </div>
                <div class="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                    <div class="space-y-1">
                        <p><span class="font-semibold">Name:</span> <span
                                x-text="selectedDeal?.client?.name ?? '-'"></span>
                        </p>
                        <p><span class="font-semibold">Age:</span> <span
                                x-text="selectedDeal?.client?.age ?? '-'"></span></p>
                        <p><span class="font-semibold">Occupation:</span> <span
                                x-text="selectedDeal?.client?.occupation ?? '-'"></span></p>
                        <p><span class="font-semibold">Company:</span> <span
                                x-text="selectedDeal?.client?.company ?? '-'"></span></p>
                        <p><span class="font-semibold">Working Years:</span> <span
                                x-text="selectedDeal?.client?.working_years ?? '-'"></span></p>
                        <p><span class="font-semibold">Monthly Income:</span> <span
                                x-text="selectedDeal?.client?.monthly_income ? new Intl.NumberFormat('ms-MY', { style: 'currency', currency: 'MYR' }).format(selectedDeal.client.monthly_income ) : '-'"></span>
                        </p>
                        <p><span class="font-semibold">Fixed Income:</span> <span
                                x-text="selectedDeal?.client?.fixed_income ? new Intl.NumberFormat('ms-MY', { style: 'currency', currency: 'MYR' }).format(selectedDeal.client.fixed_income ) : '-'"></span>
                        </p>
                    </div>
                    <div class="space-y-1">
                        <p><span class="font-semibold">IC / Passport:</span> <span
                                x-text="selectedDeal?.client?.ic_passport ?? '-'"></span></p>
                        <p><span class="font-semibold">Existing Loans:</span> <span
                                x-text="selectedDeal?.borrower_profile?.existing_loans ? new Intl.NumberFormat('ms-MY', { style: 'currency', currency: 'MYR' }).format(selectedDeal.borrower_profile.existing_loans ) : '-'"></span>
                        </p>
                        <p><span class="font-semibold">Monthly Commitments:</span> <span
                                x-text="selectedDeal?.borrower_profile?.monthly_commitments ? new Intl.NumberFormat('ms-MY', { style: 'currency', currency: 'MYR' }).format(selectedDeal.borrower_profile.monthly_commitments ) : '-'"></span>
                        </p>
                        <p><span class="font-semibold">Credit Card Limits:</span> <span
                                x-text="selectedDeal?.borrower_profile?.credit_card_limits ? new Intl.NumberFormat('ms-MY', { style: 'currency', currency: 'MYR' }).format(selectedDeal.borrower_profile.credit_card_limits ) : '-'"></span>
                        </p>
                        <p><span class="font-semibold">Card Utilization:</span>
                            <span
                                x-text="selectedDeal?.borrower_profile?.credit_card_utilization != null ? selectedDeal.borrower_profile.credit_card_utilization + '%' : '-'">
                            </span>
                        </p>
                        <p><span class="font-semibold">CCRIS:</span> <span
                                x-text="selectedDeal?.borrower_profile?.ccris ?? '-'"></span></p>
                        <p><span class="font-semibold">CTOS:</span> <span
                                x-text="selectedDeal?.borrower_profile?.ctos ?? '-'"></span>
                        </p>
                    </div>
                </div>
            </section>

            <section class="rounded border border-gray-200 p-3">
                <div class="flex items-start justify-between gap-3">
                    <h5 class="mb-2 text-sm font-semibold text-gray-900">Pre-Qualification</h5>
                    <em class="text-gray-600">Qualificated at:
                        <span x-text="selectedDeal?.pre_qualification?.date ?? '-'"></span></em>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200 text-center">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="border border-gray-200 px-2 py-1 w-[50%]">Bank</th>
                                <th class="border border-gray-200 px-2 py-1 w-[25%]">Approval Probability</th>
                                <th class="border border-gray-200 px-2 py-1 w-[25%]">Loan Margin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template
                                x-if="!(selectedDeal?.pre_qualification?.recommendations ?? []).some(item => item?.bank || item?.approval_probability !== null || item?.loan_margin !== null)">
                                <tr>
                                    <td colspan="3"
                                        class="border border-gray-200 px-2 py-1 text-gray-500 text-center italic">No
                                        data</td>
                                </tr>
                            </template>
                            <template
                                x-for="(item, index) in (selectedDeal?.pre_qualification?.recommendations ?? []).filter(item => item?.bank || item?.approval_probability !== null || item?.loan_margin !== null)"
                                :key="index">
                                <tr>
                                    <td class="border border-gray-200 px-2 py-1" x-text="item?.bank ?? '-'"></td>
                                    <td class="border border-gray-200 px-2 py-1"
                                        x-text="item?.approval_probability != null ? item.approval_probability + '%' : '-'">
                                    </td>
                                    <td class="border border-gray-200 px-2 py-1"
                                        x-text="item?.loan_margin != null ? item.loan_margin + '%' : '-'">
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded border border-gray-200 p-3">
                <div class="flex items-start justify-between gap-3">
                    <h5 class="mb-2 text-sm font-semibold text-gray-900">Bank Submission Tracking</h5>
                    <em class="text-gray-600">Approved at:
                        <span x-text="selectedDeal?.pipeline_dates?.loan_approved_date ?? '-'"></span></em>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200 text-center">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="border border-gray-200 px-2 py-1">Loan ID</th>
                                <th class="border border-gray-200 px-2 py-1">Bank</th>
                                <th class="border border-gray-200 px-2 py-1">Banker Contact</th>
                                <th class="border border-gray-200 px-2 py-1">Doc Score</th>
                                <th class="border border-gray-200 px-2 py-1">Status</th>
                                <th class="border border-gray-200 px-2 py-1">Expected Approval</th>
                                <th class="border border-gray-200 px-2 py-1">File Integrity</th>
                                <th class="border border-gray-200 px-2 py-1">Submission Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="!selectedDeal?.bank_submissions?.length">
                                <tr>
                                    <td colspan="8"
                                        class="border border-gray-200 px-2 py-1 text-gray-500 text-center italic">No
                                        data</td>
                                </tr>
                            </template>
                            <template x-for="(item, index) in (selectedDeal?.bank_submissions ?? [])"
                                :key="index">
                                <tr>
                                    <td class="border border-gray-200 px-2 py-1" x-text="item?.loan_id ?? '-'"></td>
                                    <td class="border border-gray-200 px-2 py-1" x-text="item?.bank_name ?? '-'"></td>
                                    <td class="border border-gray-200 px-2 py-1" x-text="item?.banker_contact ?? '-'">
                                    </td>
                                    <td class="border border-gray-200 px-2 py-1"
                                        x-text="item?.document_completeness_score ?? '-'"></td>
                                    <td class="border border-gray-200 px-2 py-1"
                                        x-text="item?.approval_status ?? '-'"></td>
                                    <td class="border border-gray-200 px-2 py-1"
                                        x-text="item?.expected_approval_date ?? '-'"></td>
                                    <td class="border border-gray-200 px-2 py-1"
                                        x-text="item?.file_completeness_percentage ?? '-'"></td>
                                    <td class="border border-gray-200 px-2 py-1"
                                        x-text="item?.submission_date ?? '-'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded border border-gray-200 p-3">
                <h5 class="mb-2 text-sm font-semibold text-gray-900">Approval Analysis</h5>
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200 text-center">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="border border-gray-200 px-2 py-1">Loan ID</th>
                                <th class="border border-gray-200 px-2 py-1">Approved Bank</th>
                                <th class="border border-gray-200 px-2 py-1">Applied Amount</th>
                                <th class="border border-gray-200 px-2 py-1">Approved Amount</th>
                                <th class="border border-gray-200 px-2 py-1">Interest Rate</th>
                                <th class="border border-gray-200 px-2 py-1">Lock-in Period</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="!selectedDeal?.approval_analysis?.length">
                                <tr>
                                    <td colspan="6"
                                        class="border border-gray-200 px-2 py-1 text-gray-500 text-center italic">No
                                        data</td>
                                </tr>
                            </template>
                            <template x-for="(item, index) in (selectedDeal?.approval_analysis ?? [])"
                                :key="index">
                                <tr>
                                    <td class="border border-gray-200 px-2 py-1" x-text="item?.loan_id ?? '-'"></td>
                                    <td class="border border-gray-200 px-2 py-1" x-text="item?.approved_bank ?? '-'">
                                    </td>
                                    <td class="border border-gray-200 px-2 py-1"
                                        x-text="item?.applied_amount ? new Intl.NumberFormat('ms-MY', { style: 'currency', currency: 'MYR' }).format(item.applied_amount ) : '-'">
                                    </td>
                                    <td class="border border-gray-200 px-2 py-1"
                                        x-text="item?.approved_amount ? new Intl.NumberFormat('ms-MY', { style: 'currency', currency: 'MYR' }).format(item.approved_amount ) : '-'">
                                    </td>
                                    <td class="border border-gray-200 px-2 py-1" x-text="item?.interest_rate ?? '-'">
                                    </td>
                                    <td class="border border-gray-200 px-2 py-1" x-text="item?.lock_in_period ?? '-'">
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded border border-gray-200 p-3">
                <h5 class="mb-2 text-sm font-semibold text-gray-900">Disbursement</h5>
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200 text-center">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="border border-gray-200 px-2 py-1">Loan ID</th>
                                <th class="border border-gray-200 px-2 py-1">First Disbursement</th>
                                <th class="border border-gray-200 px-2 py-1">Full Disbursement</th>
                                <th class="border border-gray-200 px-2 py-1">SPA Completion</th>
                                <th class="border border-gray-200 px-2 py-1">Client Notification</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="!selectedDeal?.disbursements?.length">
                                <tr>
                                    <td colspan="5"
                                        class="border border-gray-200 px-2 py-1 text-gray-500 text-center italic">No
                                        data</td>
                                </tr>
                            </template>
                            <template x-for="(item, index) in (selectedDeal?.disbursements ?? [])"
                                :key="index">
                                <tr>
                                    <td class="border border-gray-200 px-2 py-1" x-text="item?.loan_id ?? '-'"></td>
                                    <td class="border border-gray-200 px-2 py-1"
                                        x-text="item?.first_disbursement_date ?? '-'"></td>
                                    <td class="border border-gray-200 px-2 py-1"
                                        x-text="item?.full_disbursement_date ?? '-'">
                                    </td>
                                    <td class="border border-gray-200 px-2 py-1"
                                        x-text="item?.spa_completion_date ?? '-'">
                                    </td>
                                    <td class="border border-gray-200 px-2 py-1"
                                        x-text="item?.client_notification_date ?? '-'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded border border-gray-200 p-3">
                <h5 class="mb-2 text-sm font-semibold text-gray-900">Legal</h5>
                <div class="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                    <p><span class="font-semibold">Status:</span> <span
                            x-text="selectedDeal?.legal?.status ?? '-'"></span>
                    </p>
                    <p><span class="font-semibold">Lawyer Firm:</span> <span
                            x-text="selectedDeal?.legal?.lawyer_firm ?? '-'"></span></p>
                    <p><span class="font-semibold">SPA Date:</span> <span
                            x-text="selectedDeal?.legal?.spa_date ?? '-'"></span>
                    </p>
                    <p><span class="font-semibold">Loan Agreement Date:</span> <span
                            x-text="selectedDeal?.legal?.loan_agreement_date ?? '-'"></span></p>
                    <p><span class="font-semibold">Completion Date:</span> <span
                            x-text="selectedDeal?.legal?.completion_date ?? '-'"></span></p>
                    <p><span class="font-semibold">Stamp Duty:</span> <span
                            x-text="selectedDeal?.legal?.stamp_duty == null ? '-' : (selectedDeal.legal.stamp_duty ? 'Yes' : 'No')"></span>
                    </p>
                </div>
            </section>

            <section class="rounded border border-gray-200 p-3">
                <h5 class="mb-2 text-sm font-semibold text-gray-900">Pipeline Dates</h5>
                <div class="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                    <template x-if="selectedDeal?.pipeline_dates?.lead_date">
                        <p x-text="`${selectedDeal.pipeline_dates.lead_date} - New Lead`"></p>
                    </template>
                    <template x-if="selectedDeal?.pipeline_dates?.viewing_date">
                        <p x-text="`${selectedDeal.pipeline_dates.viewing_date} - Viewing`"></p>
                    </template>
                    <template x-if="selectedDeal?.pipeline_dates?.booking_date">
                        <p x-text="`${selectedDeal.pipeline_dates.booking_date} - Booking`"></p>
                    </template>
                    <template x-if="selectedDeal?.pipeline_dates?.spa_signed_date">
                        <p x-text="`${selectedDeal.pipeline_dates.spa_signed_date} - SPA Signed`"></p>
                    </template>
                    <template x-if="selectedDeal?.pipeline_dates?.loan_submitted_date">
                        <p x-text="`${selectedDeal.pipeline_dates.loan_submitted_date} - Loan Submitted`"></p>
                    </template>
                    <template x-if="selectedDeal?.pipeline_dates?.loan_approved_date">
                        <p x-text="`${selectedDeal.pipeline_dates.loan_approved_date} - Loan Approved`"></p>
                    </template>
                    <template x-if="selectedDeal?.pipeline_dates?.legal_processing_date">
                        <p x-text="`${selectedDeal.pipeline_dates.legal_processing_date} - Legal Processing`"></p>
                    </template>
                    <template x-if="selectedDeal?.pipeline_dates?.completed_date">
                        <p x-text="`${selectedDeal.pipeline_dates.completed_date} - Completed`"></p>
                    </template>
                    <template x-if="selectedDeal?.pipeline_dates?.commission_paid_date">
                        <p x-text="`${selectedDeal.pipeline_dates.commission_paid_date} - Commission Paid`"></p>
                    </template>
                </div>
            </section>

            <div class="flex justify-end">
                <button type="button"
                    class="rounded border border-gray-300 px-3 py-1 text-xs text-gray-700 hover:bg-gray-100"
                    @click="closeModal('{{ $modalKey }}')">Close</button>
            </div>
        </div>
    </div>
</div>
