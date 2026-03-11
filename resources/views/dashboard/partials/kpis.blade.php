@php
    $kpis = [
        [
            'label' => 'New Leads',
            'value' => number_format($executive['new_leads_month']),
            'trend' => $executiveTrends['new_leads_month'] ?? 0,
        ],
        [
            'label' => 'New Deals',
            'value' => number_format($executive['new_deals_month']),
            'trend' => $executiveTrends['new_deals_month'] ?? 0,
        ],
        [
            'label' => 'New Bookings',
            'value' => number_format($executive['new_bookings']),
            'trend' => $executiveTrends['new_bookings'] ?? 0,
        ],
        [
            'label' => 'Completed Deals',
            'value' => number_format($executive['completed_deals_month']),
            'trend' => $executiveTrends['completed_deals_month'] ?? 0,
        ],
        [
            'label' => 'Submited Loan',
            'value' => number_format($executive['new_loan_cases_month']),
            'trend' => $executiveTrends['new_loan_cases_month'] ?? 0,
        ],
        [
            'label' => 'Approved Loan',
            'value' => number_format($executive['approved_loans_month'] ?? 0),
            'trend' => $executiveTrends['approved_loans_month'] ?? 0,
        ],
        [
            'label' => 'Loan Approval Rate',
            'value' => number_format($executive['loan_approval_rate'], 2) . '%',
            'trend' => $executiveTrends['loan_approval_rate'] ?? 0,
        ],
        [
            'label' => 'Commission Earned',
            'value' => 'RM ' . number_format($executive['total_commission_month'], 2),
            'trend' => $executiveTrends['total_commission_month'] ?? 0,
        ],
    ];
@endphp
@foreach ($kpis as $item)
    @php
        $trendValue = (float) ($item['trend'] ?? 0);
        $trendClass =
            $trendValue > 0 ? 'crm-kpi-trend-up' : ($trendValue < 0 ? 'crm-kpi-trend-down' : 'crm-kpi-trend-flat');
        $trendPrefix = $trendValue > 0 ? '+' : '';
    @endphp

    <article class="crm-kpi crm-anim-fade-up">
        <p class="crm-kpi-label">
            {{ $item['label'] }}</p>
        <p class="mt-2 text-[1.5rem] font-semibold leading-none text-slate-900 crm-countup">
            {{ $item['value'] }}</p>
        <p class="mt-2 text-xs {{ $trendClass }}">
            {{ $trendPrefix }}{{ number_format($trendValue, 2) }}%
    </article>
@endforeach
