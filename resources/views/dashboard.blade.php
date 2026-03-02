<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-900 leading-tight">
      CRM Dashboard
    </h2>
  </x-slot>

  <div class="py-8 bg-slate-100 min-h-screen">
    <div class="mx-auto sm:px-6 lg:px-8 space-y-6">

      @if($canViewExecutive)
        <section class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
          <h3 class="text-lg font-semibold text-slate-900 mb-4">Executive Snapshot</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Total Leads (This Month)</p><p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($executive['total_leads_month']) }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Total Bookings</p><p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($executive['total_bookings']) }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Conversion Rate</p><p class="mt-2 text-2xl font-bold text-indigo-700">{{ number_format($executive['conversion_rate'], 2) }}%</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Loan Approval Rate</p><p class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($executive['loan_approval_rate'], 2) }}%</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Total SPA Value</p><p class="mt-2 text-2xl font-bold text-slate-900">RM {{ number_format($executive['total_spa_value'], 2) }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Total Disbursed Amount</p><p class="mt-2 text-2xl font-bold text-slate-900">RM {{ number_format($executive['total_disbursed_amount'], 2) }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Commission Payable</p><p class="mt-2 text-2xl font-bold text-amber-700">RM {{ number_format($executive['commission_payable'], 2) }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Commission Paid</p><p class="mt-2 text-2xl font-bold text-emerald-700">RM {{ number_format($executive['commission_paid'], 2) }}</p></div>
          </div>
        </section>

        <section class="grid grid-cols-1 xl:grid-cols-3 gap-6">
          <div class="xl:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Pipeline Overview</h3>
            @php $maxStage = max($pipelineStages ?: [1]); @endphp
            <div class="space-y-3">
              @foreach($pipelineStages as $stage => $count)
                @php
                  $percent = $maxStage > 0 ? round(($count / $maxStage) * 100, 2) : 0;
                @endphp
                <div>
                  <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-slate-700">{{ $stage }}</span>
                    <span class="font-semibold text-slate-900">{{ number_format($count) }}</span>
                  </div>
                  <div class="mt-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-2 bg-indigo-600 rounded-full" style="width: {{ $percent }}%;"></div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>

          <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Revenue Forecast (Next 30 Days)</h3>
            <div class="space-y-4">
              <div><p class="text-xs uppercase text-slate-500">Expected Disbursement</p><p class="text-xl font-bold text-slate-900">RM {{ number_format($forecast['expected_disbursement_30'], 2) }}</p></div>
              <div><p class="text-xs uppercase text-slate-500">Expected Commission</p><p class="text-xl font-bold text-slate-900">RM {{ number_format($forecast['expected_commission_30'], 2) }}</p></div>
              <div><p class="text-xs uppercase text-slate-500">Outstanding Commission</p><p class="text-xl font-bold text-amber-700">RM {{ number_format($forecast['outstanding_commission'], 2) }}</p></div>
              <div><p class="text-xs uppercase text-slate-500">Unpaid Cases</p><p class="text-xl font-bold text-rose-700">{{ number_format($forecast['unpaid_cases']) }}</p></div>
            </div>
          </div>
        </section>
      @endif

      @if($canViewSales)
        <section class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
          <h3 class="text-lg font-semibold text-slate-900 mb-4">Sales Dashboard</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">My Leads</p><p class="mt-2 text-xl font-bold">{{ number_format($sales['my_leads']) }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">My Bookings</p><p class="mt-2 text-xl font-bold">{{ number_format($sales['my_bookings']) }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">My Conversion Rate</p><p class="mt-2 text-xl font-bold text-indigo-700">{{ number_format($sales['my_conversion_rate'], 2) }}%</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">My Active Loan Cases</p><p class="mt-2 text-xl font-bold">{{ number_format($sales['my_active_loan_cases']) }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">My Active Legal Cases</p><p class="mt-2 text-xl font-bold">{{ number_format($sales['my_active_legal_cases']) }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">My Commission Pending</p><p class="mt-2 text-xl font-bold text-amber-700">RM {{ number_format($sales['my_commission_pending'], 2) }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">My Commission Paid</p><p class="mt-2 text-xl font-bold text-emerald-700">RM {{ number_format($sales['my_commission_paid'], 2) }}</p></div>
          </div>
          <div class="mt-6">
            <h4 class="text-sm font-semibold text-slate-800 mb-2">Top 5 Sales Leaderboard</h4>
            <div class="overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead class="bg-slate-100">
                  <tr>
                    <th class="px-3 py-2 text-left font-semibold">Salesperson</th>
                    <th class="px-3 py-2 text-left font-semibold">Deals</th>
                    <th class="px-3 py-2 text-left font-semibold">Total Value</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                  @forelse($leaderboard as $item)
                    <tr>
                      <td class="px-3 py-2">{{ $item->salesperson_name }}</td>
                      <td class="px-3 py-2">{{ number_format($item->deals_count) }}</td>
                      <td class="px-3 py-2">RM {{ number_format($item->total_value, 2) }}</td>
                    </tr>
                  @empty
                    <tr><td colspan="3" class="px-3 py-4 text-center text-slate-500">No sales data.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </section>
      @endif

      @if($canViewLoan)
        <section class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
          <h3 class="text-lg font-semibold text-slate-900 mb-4">Loan Dashboard</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Total Loan Cases</p><p class="mt-2 text-xl font-bold">{{ number_format($loan['total_cases']) }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Pending Document Cases</p><p class="mt-2 text-xl font-bold">{{ number_format($loan['pending_document_cases']) }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Submitted to Bank</p><p class="mt-2 text-xl font-bold">{{ number_format($loan['submitted_to_bank']) }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Approved</p><p class="mt-2 text-xl font-bold text-emerald-700">{{ number_format($loan['approved']) }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Rejected</p><p class="mt-2 text-xl font-bold text-rose-700">{{ number_format($loan['rejected']) }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Approval Rate</p><p class="mt-2 text-xl font-bold text-indigo-700">{{ number_format($loan['approval_rate'], 2) }}%</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Average Approval Days</p><p class="mt-2 text-xl font-bold">{{ is_null($loan['average_approval_days']) ? '-' : $loan['average_approval_days'] }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">High DSR Cases</p><p class="mt-2 text-xl font-bold text-amber-700">{{ number_format($loan['high_dsr_cases']) }}</p></div>
          </div>
        </section>
      @endif

      @if($canViewLegal)
        <section class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
          <h3 class="text-lg font-semibold text-slate-900 mb-4">Legal Dashboard</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">SPA Drafting</p><p class="mt-2 text-xl font-bold">{{ number_format($legal['drafting']) }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Awaiting Client Signature</p><p class="mt-2 text-xl font-bold">{{ number_format($legal['awaiting_client_signature']) }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Awaiting Bank</p><p class="mt-2 text-xl font-bold">{{ number_format($legal['awaiting_bank']) }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Awaiting Disbursement</p><p class="mt-2 text-xl font-bold">{{ number_format($legal['awaiting_disbursement']) }}</p></div>
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-4"><p class="text-xs uppercase text-rose-600">Overdue Cases (&gt;14 days)</p><p class="mt-2 text-xl font-bold text-rose-700">{{ number_format($legal['overdue_cases']) }}</p></div>
          </div>
        </section>
      @endif

      @if($canViewFinance)
        <section class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
          <h3 class="text-lg font-semibold text-slate-900 mb-4">Commission / Finance Dashboard</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Commission Eligible</p><p class="mt-2 text-xl font-bold">{{ number_format($finance['eligible']) }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Pending Approval</p><p class="mt-2 text-xl font-bold text-amber-700">{{ number_format($finance['pending_approval']) }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Approved</p><p class="mt-2 text-xl font-bold text-indigo-700">{{ number_format($finance['approved']) }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Paid</p><p class="mt-2 text-xl font-bold text-emerald-700">{{ number_format($finance['paid']) }}</p></div>
            <div class="rounded-lg border border-slate-200 p-4"><p class="text-xs uppercase text-slate-500">Clawback Cases</p><p class="mt-2 text-xl font-bold text-rose-700">{{ number_format($finance['clawback']) }}</p></div>
          </div>

          <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div>
              <h4 class="text-sm font-semibold text-slate-800 mb-2">Commission by Salesperson</h4>
              <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                  <thead class="bg-slate-100">
                    <tr>
                      <th class="px-3 py-2 text-left font-semibold">Salesperson</th>
                      <th class="px-3 py-2 text-left font-semibold">Total</th>
                      <th class="px-3 py-2 text-left font-semibold">Paid</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-200">
                    @forelse($finance['by_salesperson'] as $item)
                      <tr>
                        <td class="px-3 py-2">{{ $item->salesperson_name }}</td>
                        <td class="px-3 py-2">RM {{ number_format($item->total_commission, 2) }}</td>
                        <td class="px-3 py-2">RM {{ number_format($item->paid_commission, 2) }}</td>
                      </tr>
                    @empty
                      <tr><td colspan="3" class="px-3 py-4 text-center text-slate-500">No commission data.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
            <div>
              <h4 class="text-sm font-semibold text-slate-800 mb-2">Commission by Month</h4>
              <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                  <thead class="bg-slate-100">
                    <tr>
                      <th class="px-3 py-2 text-left font-semibold">Month</th>
                      <th class="px-3 py-2 text-left font-semibold">Paid Amount</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-200">
                    @forelse($finance['by_month'] as $item)
                      <tr>
                        <td class="px-3 py-2">{{ $item->month }}</td>
                        <td class="px-3 py-2">RM {{ number_format($item->paid_commission, 2) }}</td>
                      </tr>
                    @empty
                      <tr><td colspan="2" class="px-3 py-4 text-center text-slate-500">No monthly data.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>
      @endif
    </div>
  </div>
</x-app-layout>

