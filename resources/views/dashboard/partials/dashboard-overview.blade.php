 <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
     <x-card title="Lead Status (All)" class="crm-anim-fade-up" style="--crm-anim-delay: 125ms;">
         <div class="grid grid-cols-2 gap-4 md:grid-cols-2 crm-anim-stagger">
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Total Leads</p>
                 <p class="mt-2 text-2xl font-semibold">
                     {{ number_format($lead['total_leads']) }}
                 </p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Losted Leads</p>
                 <p class="mt-2 text-2xl font-semibold text-rose-700">
                     {{ number_format($lead['total_lost_leads']) }}</p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Contacted Leads</p>
                 <p class="mt-2 text-2xl font-semibold">
                     {{ number_format($lead['contacted_leads']) }}
                 </p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Scheduled Leads</p>
                 <p class="mt-2 text-2xl font-semibold">
                     {{ number_format($lead['scheduled_leads']) }}
                 </p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Leads Converted</p>
                 <p class="mt-2 text-2xl font-semibold">
                     {{ number_format($lead['leads_converted']) }}</p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Leads Converted Rate</p>
                 <p class="mt-2 text-2xl font-semibold">
                     {{ number_format($lead['leads_converted_rate'], 2) }}%
                 </p>
             </div>
         </div>
     </x-card>

     <x-card title="Deal Status (All)" class="crm-anim-fade-up" style="--crm-anim-delay: 130ms;">
         <div class="grid grid-cols-2 gap-4 md:grid-cols-2 crm-anim-stagger">
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Total Deals</p>
                 <p class="mt-2 text-2xl font-semibold">
                     {{ number_format($deal['total_deals']) }}</p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Deal Close Rate</p>
                 <p class="mt-2 text-2xl font-semibold">
                     {{ number_format($deal['close_rate'], 2) }}%</p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Completed Deals</p>
                 <p class="mt-2 text-2xl font-semibold text-emerald-700">
                     {{ number_format($deal['completed_deals']) }}
                 </p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Active Deals</p>
                 <p class="mt-2 text-2xl font-semibold">
                     {{ number_format($deal['incomplete_deals']) }}
                 </p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Avg Complete Day</p>
                 <p class="mt-2 text-2xl font-semibold">
                     {{ is_null($deal['avg_completion_days']) ? '-' : number_format($deal['avg_completion_days'], 1) }}
                 </p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Avg Commission / Deal</p>
                 <p class="mt-2 text-2xl font-semibold">RM
                     {{ number_format($deal['avg_commission_per_deal'], 2) }}
                 </p>
             </div>
         </div>
     </x-card>

     <x-card title="Loan Status (All)" class="crm-anim-fade-up col-span-2" style="--crm-anim-delay: 140ms;">
         <div class="grid grid-cols-2 gap-4 md:grid-cols-4 crm-anim-stagger">
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Total Loan Cases</p>
                 <p class="mt-2 text-2xl font-semibold">
                     {{ number_format($loan['total_cases']) }}</p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Pending Document Cases</p>
                 <p class="mt-2 text-2xl font-semibold">
                     {{ number_format($loan['pending_document_cases']) }}</p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Submitted to Bank</p>
                 <p class="mt-2 text-2xl font-semibold">
                     {{ number_format($loan['submitted_to_bank']) }}</p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Approved</p>
                 <p class="mt-2 text-2xl font-semibold text-emerald-700">
                     {{ number_format($loan['approved']) }}</p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Rejected</p>
                 <p class="mt-2 text-2xl font-semibold text-rose-700">
                     {{ number_format($loan['rejected']) }}</p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Approval Rate</p>
                 <p class="mt-2 text-2xl font-semibold">
                     {{ number_format($loan['approval_rate'], 2) }}%</p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Average Approval Days</p>
                 <p class="mt-2 text-2xl font-semibold">
                     {{ is_null($loan['average_approval_days']) ? '-' : $loan['average_approval_days'] }}
                 </p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">High DSR Cases</p>
                 <p class="mt-2 text-2xl font-semibold text-amber-700">
                     {{ number_format($loan['high_dsr_cases']) }}</p>
             </div>
         </div>
     </x-card>

     <x-card title="Legal Status (All)" class="crm-anim-fade-up" style="--crm-anim-delay: 180ms;">
         <div class="grid grid-cols-2 gap-4  md:grid-cols-2 crm-anim-stagger">
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">SPA Drafting</p>
                 <p class="mt-2 text-2xl font-semibold">{{ number_format($legal['drafting']) }}
                 </p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Awaiting Client Signature</p>
                 <p class="mt-2 text-2xl font-semibold">
                     {{ number_format($legal['awaiting_client_signature']) }}</p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Awaiting Bank</p>
                 <p class="mt-2 text-2xl font-semibold">
                     {{ number_format($legal['awaiting_bank']) }}</p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Overdue Cases (&gt;14 days)</p>
                 <p class="mt-2 text-2xl font-semibold text-rose-700">
                     {{ number_format($legal['overdue_cases']) }}</p>
             </div>
         </div>
     </x-card>

     <x-card title="Commission Status (All)" class="crm-anim-fade-up" style="--crm-anim-delay: 220ms;">
         <div class="mb-6 grid grid-cols-2 gap-4  md:grid-cols-2 crm-anim-stagger">
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Commission Eligible</p>
                 <p class="mt-2 text-2xl font-semibold">
                     {{ number_format($finance['eligible']) }}
                 </p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Pending Approval</p>
                 <p class="mt-2 text-2xl font-semibold text-amber-700">
                     {{ number_format($finance['pending_approval']) }}</p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Paid</p>
                 <p class="mt-2 text-2xl font-semibold text-emerald-700">
                     {{ number_format($finance['paid']) }}
                 </p>
             </div>
             <div class="crm-kpi crm-kpi-2">
                 <p class="crm-kpi-label-2">Clawback Cases</p>
                 <p class="mt-2 text-2xl font-semibold text-rose-700">
                     {{ number_format($finance['clawback']) }}
                 </p>
             </div>
         </div>
     </x-card>
 </div>
