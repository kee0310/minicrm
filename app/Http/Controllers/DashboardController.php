<?php

namespace App\Http\Controllers;

use App\Enums\LeadStatusEnum;
use App\Enums\PipelineEnum;
use App\Enums\RoleEnum;
use App\Models\Commission;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\LegalCase;
use App\Models\LoanBankSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $now = Carbon::now();
        $startMonth = $now->copy()->startOfMonth();
        $endMonth = $now->copy()->endOfMonth();
        $next30 = $now->copy()->addDays(30)->endOfDay();

        $monthlyLeads = Lead::query()
            ->whereBetween('created_at', [$startMonth, $endMonth]);

        $monthlyBookings = Deal::query()
            ->whereBetween('created_at', [$startMonth, $endMonth])
            ->whereIn('pipeline', [
                PipelineEnum::BOOKING->value,
                PipelineEnum::SPA_SIGNED->value,
                PipelineEnum::LOAN_SUBMITTED->value,
                PipelineEnum::LOAN_APPROVED->value,
                PipelineEnum::LEGAL_PROCESSING->value,
                PipelineEnum::COMPLETED->value,
                PipelineEnum::COMMISSION_PAID->value,
            ]);

        $submittedToBank = LoanBankSubmission::query()
            ->whereIn('approval_status', ['Submitted', 'In Review', 'Approved', 'Rejected']);
        $approvedLoans = LoanBankSubmission::query()->where('approval_status', 'Approved');

        $totalLeadsMonth = (clone $monthlyLeads)->count();
        $totalBookingsMonth = (clone $monthlyBookings)->count();
        $conversionRate = $totalLeadsMonth > 0
            ? round(($totalBookingsMonth / $totalLeadsMonth) * 100, 2)
            : 0;

        $submittedCount = (clone $submittedToBank)->count();
        $approvedCount = (clone $approvedLoans)->count();
        $loanApprovalRate = $submittedCount > 0
            ? round(($approvedCount / $submittedCount) * 100, 2)
            : 0;

        $totalSpaValue = Deal::query()
            ->whereIn('pipeline', [
                PipelineEnum::SPA_SIGNED->value,
                PipelineEnum::LOAN_SUBMITTED->value,
                PipelineEnum::LOAN_APPROVED->value,
                PipelineEnum::LEGAL_PROCESSING->value,
                PipelineEnum::COMPLETED->value,
                PipelineEnum::COMMISSION_PAID->value,
            ])
            ->sum('selling_price');

        $totalDisbursedAmount = LoanBankSubmission::query()
            ->whereNotNull('full_disbursement_date')
            ->sum('approved_amount');

        $commissionTotal = Deal::query()->sum('commission_amount');
        $commissionPaid = Commission::query()->sum('paid');
        $commissionPayable = max((float) $commissionTotal - (float) $commissionPaid, 0);

        $pipelineStages = [
            'Lead' => Lead::query()->whereIn('status', [
                LeadStatusEnum::NEW->value,
                LeadStatusEnum::CONTACTED->value,
                LeadStatusEnum::SCHEDULED->value,
            ])->count(),
            'Viewing' => Deal::query()->where('pipeline', PipelineEnum::VIEWING->value)->count(),
            'Booking' => Deal::query()->where('pipeline', PipelineEnum::BOOKING->value)->count(),
            'Loan Submitted' => Deal::query()->where('pipeline', PipelineEnum::LOAN_SUBMITTED->value)->count(),
            'Loan Approved' => Deal::query()->where('pipeline', PipelineEnum::LOAN_APPROVED->value)->count(),
            'SPA Signed' => Deal::query()->where('pipeline', PipelineEnum::SPA_SIGNED->value)->count(),
            'Disbursed' => LoanBankSubmission::query()->whereNotNull('full_disbursement_date')->count(),
        ];

        $expectedDisbursement30 = LoanBankSubmission::query()
            ->whereBetween('full_disbursement_date', [$now->toDateString(), $next30->toDateString()])
            ->sum('approved_amount');

        $expectedCommission30 = Deal::query()
            ->whereHas('legalCase', function ($q) use ($now, $next30) {
                $q->whereBetween('completion_date', [$now->toDateString(), $next30->toDateString()]);
            })
            ->sum('commission_amount');

        $outstandingCommission = Deal::query()
            ->leftJoin('commissions', 'commissions.deal_id', '=', 'deals.id')
            ->selectRaw('COALESCE(SUM(deals.commission_amount - COALESCE(commissions.paid, 0)), 0) as outstanding')
            ->value('outstanding');

        $unpaidCases = Commission::query()
            ->where('payment_status', 'Unpaid')
            ->count();

        $myLeads = Lead::query()->where('salesperson_id', $user?->id)->count();
        $myBookings = Deal::query()
            ->where('salesperson_id', $user?->id)
            ->whereIn('pipeline', [
                PipelineEnum::BOOKING->value,
                PipelineEnum::SPA_SIGNED->value,
                PipelineEnum::LOAN_SUBMITTED->value,
                PipelineEnum::LOAN_APPROVED->value,
                PipelineEnum::LEGAL_PROCESSING->value,
                PipelineEnum::COMPLETED->value,
                PipelineEnum::COMMISSION_PAID->value,
            ])
            ->count();
        $myConversionRate = $myLeads > 0 ? round(($myBookings / $myLeads) * 100, 2) : 0;
        $myActiveLoanCases = LoanBankSubmission::query()
            ->whereHas('deal', fn($q) => $q->where('salesperson_id', $user?->id))
            ->whereIn('approval_status', ['Prepared', 'Submitted', 'In Review'])
            ->count();
        $myActiveLegalCases = LegalCase::query()
            ->whereHas('deal', fn($q) => $q->where('salesperson_id', $user?->id))
            ->where('status', '!=', 'Completed')
            ->count();
        $myCommissionPending = Deal::query()
            ->where('salesperson_id', $user?->id)
            ->leftJoin('commissions', 'commissions.deal_id', '=', 'deals.id')
            ->selectRaw('COALESCE(SUM(deals.commission_amount - COALESCE(commissions.paid, 0)), 0) as pending')
            ->value('pending');
        $myCommissionPaid = Commission::query()
            ->whereHas('deal', fn($q) => $q->where('salesperson_id', $user?->id))
            ->sum('paid');

        $leaderboard = Deal::query()
            ->join('users', 'users.id', '=', 'deals.salesperson_id')
            ->selectRaw('users.name as salesperson_name, COUNT(deals.id) as deals_count, COALESCE(SUM(deals.selling_price), 0) as total_value')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_value')
            ->limit(5)
            ->get();

        $loanTotal = LoanBankSubmission::query()->count();
        $pendingDocumentCases = LoanBankSubmission::query()
            ->where(function ($q) {
                $q->whereNull('file_completeness_percentage')
                    ->orWhere('file_completeness_percentage', '<', 80);
            })
            ->count();
        $loanSubmittedToBank = LoanBankSubmission::query()
            ->whereIn('approval_status', ['Submitted', 'In Review', 'Approved', 'Rejected'])
            ->count();
        $loanApproved = LoanBankSubmission::query()->where('approval_status', 'Approved')->count();
        $loanRejected = LoanBankSubmission::query()->where('approval_status', 'Rejected')->count();
        $loanDecisionCount = $loanApproved + $loanRejected;
        $loanApprovalRateDecision = $loanDecisionCount > 0
            ? round(($loanApproved / $loanDecisionCount) * 100, 2)
            : 0;

        $avgApprovalDays = LoanBankSubmission::query()
            ->where('approval_status', 'Approved')
            ->whereNotNull('submission_date')
            ->whereNotNull('expected_approval_date')
            ->selectRaw('AVG(DATEDIFF(expected_approval_date, submission_date)) as avg_days')
            ->value('avg_days');

        $highDsrCases = Deal::query()
            ->join('clients', 'clients.id', '=', 'deals.client_id')
            ->join('loan_pre_qualifications', 'loan_pre_qualifications.deal_id', '=', 'deals.id')
            ->where('clients.monthly_income', '>', 0)
            ->whereRaw('(loan_pre_qualifications.monthly_commitments / clients.monthly_income) >= 0.7')
            ->count();

        $legalDrafting = LegalCase::query()->where('status', 'Drafting')->count();
        $legalAwaitingClientSignature = LegalCase::query()->where('status', 'Pending Customer Signature')->count();
        $legalAwaitingBank = LegalCase::query()->where('status', 'Pending Bank')->count();
        $legalAwaitingDisbursement = LegalCase::query()
            ->where('status', 'Completed')
            ->whereHas('deal.bankSubmissions', function ($q) {
                $q->whereNull('full_disbursement_date');
            })
            ->count();
        $legalOverdue = LegalCase::query()
            ->where('status', '!=', 'Completed')
            ->where('updated_at', '<', $now->copy()->subDays(14))
            ->count();

        $commissionEligible = Commission::query()->count();
        $commissionPendingApproval = Commission::query()
            ->where('payment_status', 'Unpaid')
            ->where('paid', 0)
            ->count();
        $commissionApproved = Commission::query()
            ->where('payment_status', 'Unpaid')
            ->where('paid', '>', 0)
            ->count();
        $commissionPaidCount = Commission::query()->where('payment_status', 'Paid')->count();
        $clawbackCases = Commission::query()->where('paid', '<', 0)->count();

        $commissionBySalesperson = Commission::query()
            ->join('deals', 'deals.id', '=', 'commissions.deal_id')
            ->join('users', 'users.id', '=', 'deals.salesperson_id')
            ->selectRaw('users.name as salesperson_name, COALESCE(SUM(deals.commission_amount),0) as total_commission, COALESCE(SUM(commissions.paid),0) as paid_commission')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_commission')
            ->get();

        $commissionByMonth = Commission::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COALESCE(SUM(paid), 0) as paid_commission")
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->orderBy('month')
            ->get();

        return view('dashboard', [
            'executive' => [
                'total_leads_month' => $totalLeadsMonth,
                'total_bookings' => $totalBookingsMonth,
                'conversion_rate' => $conversionRate,
                'loan_approval_rate' => $loanApprovalRate,
                'total_spa_value' => $totalSpaValue,
                'total_disbursed_amount' => $totalDisbursedAmount,
                'commission_payable' => $commissionPayable,
                'commission_paid' => $commissionPaid,
            ],
            'pipelineStages' => $pipelineStages,
            'forecast' => [
                'expected_disbursement_30' => $expectedDisbursement30,
                'expected_commission_30' => $expectedCommission30,
                'outstanding_commission' => $outstandingCommission,
                'unpaid_cases' => $unpaidCases,
            ],
            'sales' => [
                'my_leads' => $myLeads,
                'my_bookings' => $myBookings,
                'my_conversion_rate' => $myConversionRate,
                'my_active_loan_cases' => $myActiveLoanCases,
                'my_active_legal_cases' => $myActiveLegalCases,
                'my_commission_pending' => $myCommissionPending,
                'my_commission_paid' => $myCommissionPaid,
            ],
            'leaderboard' => $leaderboard,
            'loan' => [
                'total_cases' => $loanTotal,
                'pending_document_cases' => $pendingDocumentCases,
                'submitted_to_bank' => $loanSubmittedToBank,
                'approved' => $loanApproved,
                'rejected' => $loanRejected,
                'approval_rate' => $loanApprovalRateDecision,
                'average_approval_days' => $avgApprovalDays ? round((float) $avgApprovalDays, 1) : null,
                'high_dsr_cases' => $highDsrCases,
            ],
            'legal' => [
                'drafting' => $legalDrafting,
                'awaiting_client_signature' => $legalAwaitingClientSignature,
                'awaiting_bank' => $legalAwaitingBank,
                'awaiting_disbursement' => $legalAwaitingDisbursement,
                'overdue_cases' => $legalOverdue,
            ],
            'finance' => [
                'eligible' => $commissionEligible,
                'pending_approval' => $commissionPendingApproval,
                'approved' => $commissionApproved,
                'paid' => $commissionPaidCount,
                'clawback' => $clawbackCases,
                'by_salesperson' => $commissionBySalesperson,
                'by_month' => $commissionByMonth,
            ],
            'canViewExecutive' => $user?->hasRole(RoleEnum::ADMIN->value),
            'canViewSales' => $user?->hasAnyRole([RoleEnum::ADMIN->value, RoleEnum::LEADER->value, RoleEnum::SALESPERSON->value]),
            'canViewLoan' => $user?->hasAnyRole([RoleEnum::ADMIN->value, RoleEnum::LOAN_OFFICER->value, RoleEnum::LEADER->value]),
            'canViewLegal' => $user?->hasAnyRole([RoleEnum::ADMIN->value, RoleEnum::LEADER->value, RoleEnum::SALESPERSON->value]),
            'canViewFinance' => $user?->hasAnyRole([RoleEnum::ADMIN->value, RoleEnum::LEADER->value]),
        ]);
    }
}

