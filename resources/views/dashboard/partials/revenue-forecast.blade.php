<div class="grid gap-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500"> Expected
            Disbursement </p>
        <p class="text-3xl font-semibold leading-tight text-slate-900 break-words crm-countup"> RM
            {{ number_format($forecast['expected_disbursement_30'], 2) }} </p>
    </div>
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500"> Expected
            Commission </p>
        <p class="text-3xl font-semibold leading-tight text-slate-900 break-words crm-countup"> RM
            {{ number_format($forecast['expected_commission_30'], 2) }} </p>
    </div>
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500"> Outstanding
            Commission </p>
        <p class="text-3xl font-semibold leading-tight text-amber-700 break-words crm-countup"> RM
            {{ number_format($forecast['outstanding_commission'], 2) }} </p>
    </div>
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500"> Unpaid Cases
        </p>
        <p class="text-3xl font-semibold leading-tight text-rose-700 crm-countup">
            {{ number_format($forecast['unpaid_cases']) }} </p>
    </div>
</div>
