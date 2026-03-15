<?php

namespace App\Services;

use App\Enums\LeadStatusEnum;
use App\Enums\LegalStatusEnum;
use App\Enums\LoanApprovalStatusEnum;
use App\Enums\PipelineEnum;
use App\Enums\RoleEnum;
use App\Models\Commission;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\LegalCase;
use App\Models\LoanBankSubmission;
use App\Models\User;
use App\Query\Dashboard\LeaderPerformanceQuery;
use App\Query\Dashboard\SalespersonPerformanceQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const PIPELINE_DATE_COLUMN_MAP = [
        PipelineEnum::LEAD->value => 'lead_date',
        PipelineEnum::VIEWING->value => 'viewing_date',
        PipelineEnum::BOOKING->value => 'booking_date',
        PipelineEnum::SPA_SIGNED->value => 'spa_signed_date',
        PipelineEnum::LOAN_SUBMITTED->value => 'loan_submitted_date',
        PipelineEnum::LOAN_APPROVED->value => 'loan_approved_date',
        PipelineEnum::LEGAL_PROCESSING->value => 'legal_processing_date',
        PipelineEnum::COMPLETED->value => 'completed_date',
        PipelineEnum::COMMISSION_PAID->value => 'commission_paid_date',
    ];

    public function salespeopleData(Request $request, User $user, Carbon $selectedMonth): array
    {
        $tab = (string) $request->query('tab', 'salesperson');
        $resolvedTab = in_array($tab, ['salesperson', 'leader'], true) ? $tab : 'salesperson';

        if ($resolvedTab === 'leader') {
            return LeaderPerformanceQuery::build($request, $user, $selectedMonth);
        }

        return SalespersonPerformanceQuery::build($request, $user, $selectedMonth);
    }

    public function buildDashboardViewData(
        Request $request,
        User $user,
        string $dashboardMode,
        Carbon $selectedMonth
    ): array {
        $isAdmin = $user->hasRole(RoleEnum::ADMIN->value);
        $isLeader = $user->hasRole(RoleEnum::LEADER->value);
        $isSalesperson = $user->hasRole(RoleEnum::SALESPERSON->value);

        $now = Carbon::now();
        $startMonth = $selectedMonth->copy()->startOfMonth();
        $endMonth = $selectedMonth->copy()->endOfMonth();
        $beforeMonthEnd = $startMonth->copy()->subDay()->endOfDay();
        $previousStart = $selectedMonth->copy()->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $selectedMonth->copy()->subMonthNoOverflow()->endOfMonth();
        $next30 = $now->copy()->addDays(30)->endOfDay();
        $trend = function (float|int $current, float|int $previous): float {
            if ((float) $previous === 0.0) {
                return (float) $current > 0 ? 100.0 : 0.0;
            }

            return round((($current - $previous) / $previous) * 100, 2);
        };
        $dashboardRoute = $dashboardMode === 'deals' ? 'dashboard.deals' : 'dashboard.sales';
        $monthRoute = fn (Carbon $month) => route($dashboardRoute, ['month' => $month->format('Y-m')]);
        $loanStages = PipelineEnum::loanStages();

        $applyDealScope = function (Builder $query) use ($isAdmin, $isLeader, $isSalesperson, $user): Builder {
            if ($isAdmin) {
                return $query;
            }

            if ($isLeader) {
                return $query->where('deals.leader_id', $user->id);
            }

            if ($isSalesperson) {
                return $query->where('deals.salesperson_id', $user->id);
            }

            return $query->whereRaw('1 = 0');
        };

        $applyLeadScope = function (Builder $query) use ($isAdmin, $isLeader, $isSalesperson, $user): Builder {
            if ($isAdmin) {
                return $query;
            }

            if ($isLeader) {
                return $query->where('leads.leader_id', $user->id);
            }

            if ($isSalesperson) {
                return $query->where('leads.salesperson_id', $user->id);
            }

            return $query->whereRaw('1 = 0');
        };

        $dealQuery = fn () => $applyDealScope(Deal::query());
        $leadQuery = fn () => $applyLeadScope(Lead::query());
        $loanQuery = fn () => LoanBankSubmission::query()->whereHas('deal', fn (Builder $q) => $applyDealScope($q));
        $legalCaseQuery = fn () => LegalCase::query()->whereHas('deal', fn (Builder $q) => $applyDealScope($q));
        $commissionQuery = fn () => Commission::query()->whereHas('deal', fn (Builder $q) => $applyDealScope($q));

        $monthlyLeads = $leadQuery()
            ->whereBetween('created_at', [$startMonth, $endMonth]);

        $monthlyBookings = $dealQuery()
            ->whereBetween('created_at', [$startMonth, $endMonth])
            ->whereIn('pipeline', $loanStages);

        $submittedToBank = $loanQuery()
            ->whereBetween('created_at', [$startMonth, $endMonth])
            ->whereIn('approval_status', LoanApprovalStatusEnum::submittedToBank());
        $approvedLoans = $loanQuery()
            ->whereBetween('created_at', [$startMonth, $endMonth])
            ->where('approval_status', LoanApprovalStatusEnum::APPROVED->value);

        $totalLeadsMonth = (clone $monthlyLeads)->count();
        $totalLeadsPreviousMonth = $this->countBetween($leadQuery(), 'created_at', $previousStart, $previousEnd);
        $totalBookingsMonth = (clone $monthlyBookings)->count();
        $totalBookingsPreviousMonth = (clone $dealQuery())
            ->whereIn('pipeline', $loanStages)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();
        $submittedCount = (clone $submittedToBank)->count();
        $approvedCount = (clone $approvedLoans)->count();
        $loanApprovalRate = $submittedCount > 0
            ? round(($approvedCount / $submittedCount) * 100, 2)
            : 0;
        $submittedPrevious = $loanQuery()
            ->whereIn('approval_status', LoanApprovalStatusEnum::submittedToBank())
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();
        $approvedPrevious = $loanQuery()
            ->where('approval_status', LoanApprovalStatusEnum::APPROVED->value)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();
        $loanApprovalRatePrevious = $submittedPrevious > 0
            ? round(($approvedPrevious / $submittedPrevious) * 100, 2)
            : 0;

        $totalDealsMonth = $this->countBetween($dealQuery(), 'created_at', $startMonth, $endMonth);
        $totalDealsPreviousMonth = $this->countBetween($dealQuery(), 'created_at', $previousStart, $previousEnd);
        $totalLoanCasesMonth = $this->countBetween($loanQuery(), 'created_at', $startMonth, $endMonth);
        $totalLoanCasesPreviousMonth = $this->countBetween($loanQuery(), 'created_at', $previousStart, $previousEnd);

        $commissionTotal = $dealQuery()
            ->join('deal_pipelines', 'deal_pipelines.deal_id', '=', 'deals.id')
            ->whereNotNull('deal_pipelines.completed_date')
            ->whereBetween('deal_pipelines.completed_date', [$startMonth->toDateTimeString(), $endMonth->toDateTimeString()])
            ->sum('deals.commission_amount');
        $commissionTotalPrevious = $dealQuery()
            ->join('deal_pipelines', 'deal_pipelines.deal_id', '=', 'deals.id')
            ->whereNotNull('deal_pipelines.completed_date')
            ->whereBetween('deal_pipelines.completed_date', [$previousStart->toDateTimeString(), $previousEnd->toDateTimeString()])
            ->sum('deals.commission_amount');
        $commissionPaid = $this->sumBetween($commissionQuery(), 'created_at', $startMonth, $endMonth, 'paid');
        $commissionPayable = max((float) $commissionTotal - (float) $commissionPaid, 0);
        $commissionPaidPrevious = $this->sumBetween($commissionQuery(), 'created_at', $previousStart, $previousEnd, 'paid');
        $commissionPayablePrevious = max((float) $commissionTotalPrevious - (float) $commissionPaidPrevious, 0);

        $pipelineStageTotals = $dealQuery()
            ->whereBetween('deals.created_at', [$startMonth, $endMonth])
            ->selectRaw('pipeline, COUNT(*) as total')
            ->groupBy('pipeline')
            ->pluck('total', 'pipeline');

        $previousPipelineStageTotals = $dealQuery()
            ->whereBetween('deals.created_at', [$previousStart, $previousEnd])
            ->selectRaw('pipeline, COUNT(*) as total')
            ->groupBy('pipeline')
            ->pluck('total', 'pipeline');

        $totalCompletedDealsMonth = (int) (
            ($pipelineStageTotals[PipelineEnum::COMPLETED->value] ?? 0)
            + ($pipelineStageTotals[PipelineEnum::COMMISSION_PAID->value] ?? 0)
        );
        $totalCompletedDealsPreviousMonth = (int) (
            ($previousPipelineStageTotals[PipelineEnum::COMPLETED->value] ?? 0)
            + ($previousPipelineStageTotals[PipelineEnum::COMMISSION_PAID->value] ?? 0)
        );

        $expectedDisbursement30 = $loanQuery()
            ->whereBetween('full_disbursement_date', [$startMonth->toDateString(), $endMonth->toDateString()])
            ->sum('approved_amount');

        $forecastDealsThisMonth = $dealQuery()
            ->whereHas('legalCase', function ($q) use ($startMonth, $endMonth) {
                $q->whereBetween('completion_date', [$startMonth->toDateString(), $endMonth->toDateString()]);
            });

        $expectedCommission30 = (clone $forecastDealsThisMonth)->sum('commission_amount');

        $outstandingCommission = (clone $forecastDealsThisMonth)
            ->leftJoin('commissions', 'commissions.deal_id', '=', 'deals.id')
            ->selectRaw('COALESCE(SUM(GREATEST(deals.commission_amount - COALESCE(commissions.paid, 0), 0)), 0) as outstanding')
            ->value('outstanding');

        $unpaidCases = (clone $forecastDealsThisMonth)
            ->leftJoin('commissions', 'commissions.deal_id', '=', 'deals.id')
            ->whereRaw('GREATEST(deals.commission_amount - COALESCE(commissions.paid, 0), 0) > 0')
            ->count('deals.id');

        $myLeads = $this->countBetween($leadQuery(), 'created_at', $startMonth, $endMonth);
        $myBookings = (clone $dealQuery())
            ->whereIn('pipeline', $loanStages)
            ->whereBetween('created_at', [$startMonth, $endMonth])
            ->count();
        $myConversionRate = $myLeads > 0 ? round(($myBookings / $myLeads) * 100, 2) : 0;
        $myActiveLoanCases = (clone $loanQuery())
            ->whereBetween('created_at', [$startMonth, $endMonth])
            ->whereIn('approval_status', LoanApprovalStatusEnum::activeCases())
            ->count();
        $myActiveLegalCases = (clone $legalCaseQuery())
            ->whereBetween('created_at', [$startMonth, $endMonth])
            ->where('status', '!=', LegalStatusEnum::COMPLETED->value)
            ->count();
        $myCommissionPending = $dealQuery()
            ->whereBetween('deals.created_at', [$startMonth, $endMonth])
            ->leftJoin('commissions', 'commissions.deal_id', '=', 'deals.id')
            ->selectRaw('COALESCE(SUM(deals.commission_amount - COALESCE(commissions.paid, 0)), 0) as pending')
            ->value('pending');
        $myCommissionPaid = $this->sumBetween($commissionQuery(), 'created_at', $startMonth, $endMonth, 'paid');

        // Overview Dashboard uses cumulative data up to selected month-end,
        // and also exposes this-month increments for "history + new" display.
        $leadAll = $leadQuery()
            ->where('created_at', '<=', $endMonth);
        $leadHistory = $leadQuery()
            ->where('created_at', '<=', $beforeMonthEnd);
        $leadNew = $leadQuery()
            ->whereBetween('created_at', [$startMonth, $endMonth]);
        $dealAll = $dealQuery()
            ->where('deals.created_at', '<=', $endMonth);
        $dealNew = $dealQuery()
            ->whereBetween('deals.created_at', [$startMonth, $endMonth]);
        $loanAll = $loanQuery()
            ->where('created_at', '<=', $endMonth);
        $loanNew = $loanQuery()
            ->whereBetween('created_at', [$startMonth, $endMonth]);
        $legalAll = $legalCaseQuery()
            ->where('created_at', '<=', $endMonth);
        $legalNew = $legalCaseQuery()
            ->whereBetween('created_at', [$startMonth, $endMonth]);
        $commissionAll = $commissionQuery()
            ->where('created_at', '<=', $endMonth);
        $commissionNew = $commissionQuery()
            ->whereBetween('created_at', [$startMonth, $endMonth]);

        $totalLeadsBeforeMonth = (clone $leadAll)->count();
        $totalLeadsHistory = (clone $leadHistory)->count();
        $totalLeadsNew = (clone $leadNew)->count();
        $totalLostLeadsBeforeMonth = (clone $leadAll)
            ->where('status', LeadStatusEnum::LOST->value)
            ->count();
        $totalLostLeadsHistory = (clone $leadHistory)
            ->where('status', LeadStatusEnum::LOST->value)
            ->count();
        $totalLostLeadsNew = (clone $leadNew)
            ->where('status', LeadStatusEnum::LOST->value)
            ->count();
        $totalContactedLeadsBeforeMonth = (clone $leadAll)
            ->where('status', LeadStatusEnum::CONTACTED->value)
            ->count();
        $totalContactedLeadsHistory = (clone $leadHistory)
            ->where('status', LeadStatusEnum::CONTACTED->value)
            ->count();
        $totalContactedLeadsNew = (clone $leadNew)
            ->where('status', LeadStatusEnum::CONTACTED->value)
            ->count();
        $totalScheduledLeadsBeforeMonth = (clone $leadAll)
            ->where('status', LeadStatusEnum::SCHEDULED->value)
            ->count();
        $totalScheduledLeadsHistory = (clone $leadHistory)
            ->where('status', LeadStatusEnum::SCHEDULED->value)
            ->count();
        $totalScheduledLeadsNew = (clone $leadNew)
            ->where('status', LeadStatusEnum::SCHEDULED->value)
            ->count();
        $totalBookingsBeforeMonth = (clone $leadAll)
            ->where('status', LeadStatusEnum::DEAL->value)
            ->count();
        $totalBookingsHistory = (clone $leadHistory)
            ->where('status', LeadStatusEnum::DEAL->value)
            ->count();
        $totalBookingsNew = (clone $leadNew)
            ->where('status', LeadStatusEnum::DEAL->value)
            ->count();
        $leadToBookingRateBeforeMonth = $totalLeadsBeforeMonth > 0
            ? round(($totalBookingsBeforeMonth / $totalLeadsBeforeMonth) * 100, 2)
            : 0;

        $leaderboard = $dealQuery()
            ->join('users', 'users.id', '=', 'deals.salesperson_id')
            ->whereBetween('deals.created_at', [$startMonth, $endMonth])
            ->whereIn('deals.salesperson_id', User::role(RoleEnum::SALESPERSON->value)->pluck('id'))
            ->selectRaw('users.name as salesperson_name, COUNT(deals.id) as deals_count, COALESCE(SUM(deals.selling_price), 0) as total_value')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_value')
            ->limit(5)
            ->get();

        $totalDealsBeforeMonth = (clone $dealAll)->count();
        $totalDealsNew = (clone $dealNew)->count();
        $completedDealsBeforeMonth = (clone $dealAll)
            ->whereIn('deals.pipeline', [PipelineEnum::COMPLETED->value, PipelineEnum::COMMISSION_PAID->value])
            ->distinct('deals.id')
            ->count('deals.id');
        $completedDealsNew = (clone $dealNew)
            ->whereIn('deals.pipeline', [PipelineEnum::COMPLETED->value, PipelineEnum::COMMISSION_PAID->value])
            ->distinct('deals.id')
            ->count('deals.id');
        $incompleteDealsBeforeMonth = max($totalDealsBeforeMonth - $completedDealsBeforeMonth, 0);
        $incompleteDealsNew = max($totalDealsNew - $completedDealsNew, 0);
        $dealCloseRateBeforeMonth = $totalDealsBeforeMonth > 0
            ? round(($completedDealsBeforeMonth / $totalDealsBeforeMonth) * 100, 2)
            : 0;
        $avgCompletionDays = (clone $dealAll)
            ->join('deal_pipelines', 'deal_pipelines.deal_id', '=', 'deals.id')
            ->whereNotNull('deal_pipelines.completed_date')
            ->whereDate('deal_pipelines.completed_date', '<=', $endMonth->toDateString())
            ->selectRaw('AVG(DATEDIFF(deal_pipelines.completed_date, deals.created_at)) as avg_days')
            ->value('avg_days');
        $totalCommissionBeforeMonth = (clone $dealAll)
            ->sum('commission_amount');
        $avgCommissionPerDealBeforeMonth = $totalDealsBeforeMonth > 0
            ? ((float) $totalCommissionBeforeMonth / $totalDealsBeforeMonth)
            : 0;

        $loanTotal = (clone $loanAll)
            ->count();
        $loanTotalNew = (clone $loanNew)->count();
        $pendingDocumentCases = (clone $loanAll)
            ->whereIn('approval_status', LoanApprovalStatusEnum::activeCases())
            ->where(function ($q) {
                $q->whereNull('file_completeness_percentage')
                    ->orWhere('file_completeness_percentage', '<', 80);
            })
            ->count();
        $pendingDocumentCasesNew = (clone $loanNew)
            ->whereIn('approval_status', LoanApprovalStatusEnum::activeCases())
            ->where(function ($q) {
                $q->whereNull('file_completeness_percentage')
                    ->orWhere('file_completeness_percentage', '<', 80);
            })
            ->count();
        $loanSubmittedToBank = (clone $loanAll)
            ->whereIn('approval_status', LoanApprovalStatusEnum::submittedToBank())
            ->count();
        $loanSubmittedToBankNew = (clone $loanNew)
            ->whereIn('approval_status', LoanApprovalStatusEnum::submittedToBank())
            ->count();
        $loanApproved = (clone $loanAll)
            ->where('approval_status', LoanApprovalStatusEnum::APPROVED->value)
            ->count();
        $loanApprovedNew = (clone $loanNew)
            ->where('approval_status', LoanApprovalStatusEnum::APPROVED->value)
            ->count();
        $loanRejected = (clone $loanAll)
            ->where('approval_status', LoanApprovalStatusEnum::REJECTED->value)
            ->count();
        $loanRejectedNew = (clone $loanNew)
            ->where('approval_status', LoanApprovalStatusEnum::REJECTED->value)
            ->count();
        $loanOverviewApprovalRate = $loanSubmittedToBank > 0
            ? round(($loanApproved / $loanSubmittedToBank) * 100, 2)
            : 0;

        $avgApprovalDays = (clone $dealAll)
            ->join('deal_pipelines', 'deal_pipelines.deal_id', '=', 'deals.id')
            ->whereNotNull('deal_pipelines.loan_submitted_date')
            ->whereNotNull('deal_pipelines.loan_approved_date')
            ->whereDate('deal_pipelines.loan_approved_date', '<=', $endMonth->toDateString())
            ->selectRaw('AVG(DATEDIFF(deal_pipelines.loan_approved_date, deal_pipelines.loan_submitted_date)) as avg_days')
            ->value('avg_days');

        $highDsrCases = (clone $dealAll)
            ->join('leads', 'leads.id', '=', 'deals.lead_id')
            ->join('loan_pre_qualifications', 'loan_pre_qualifications.deal_id', '=', 'deals.id')
            ->where('leads.monthly_income', '>', 0)
            ->whereRaw('(loan_pre_qualifications.monthly_commitments / leads.monthly_income) >= 0.7')
            ->count();
        $highDsrCasesNew = (clone $dealNew)
            ->join('leads', 'leads.id', '=', 'deals.lead_id')
            ->join('loan_pre_qualifications', 'loan_pre_qualifications.deal_id', '=', 'deals.id')
            ->where('leads.monthly_income', '>', 0)
            ->whereRaw('(loan_pre_qualifications.monthly_commitments / leads.monthly_income) >= 0.7')
            ->count();

        $legalDrafting = (clone $legalAll)
            ->where('status', LegalStatusEnum::DRAFTING->value)
            ->count();
        $legalDraftingNew = (clone $legalNew)
            ->where('status', LegalStatusEnum::DRAFTING->value)
            ->count();
        $legalAwaitingClientSignature = (clone $legalAll)
            ->where('status', LegalStatusEnum::PENDING_CUSTOMER_SIGNATURE->value)
            ->count();
        $legalAwaitingClientSignatureNew = (clone $legalNew)
            ->where('status', LegalStatusEnum::PENDING_CUSTOMER_SIGNATURE->value)
            ->count();
        $legalAwaitingBank = (clone $legalAll)
            ->where('status', LegalStatusEnum::PENDING_BANK->value)
            ->count();
        $legalAwaitingBankNew = (clone $legalNew)
            ->where('status', LegalStatusEnum::PENDING_BANK->value)
            ->count();
        $legalTotal = (clone $legalAll)->count();
        $legalCompleted = (clone $legalAll)
            ->where('status', LegalStatusEnum::COMPLETED->value)
            ->count();
        $legalOverdue = (clone $legalAll)
            ->where('status', '!=', LegalStatusEnum::COMPLETED->value)
            ->where('updated_at', '<', $endMonth->copy()->subDays(14))
            ->count();
        $legalOverdueNew = (clone $legalNew)
            ->where('status', '!=', LegalStatusEnum::COMPLETED->value)
            ->where('updated_at', '<', $endMonth->copy()->subDays(14))
            ->count();

        $commissionEligible = (clone $commissionAll)
            ->count();
        $commissionEligibleNew = (clone $commissionNew)->count();
        $commissionPendingApproval = (clone $commissionAll)
            ->where(function (Builder $query) {
                $query->whereNull('payment_status')
                    ->orWhere('payment_status', 'Unpaid');
            })
            ->where('paid', 0)
            ->count();
        $commissionPendingApprovalNew = (clone $commissionNew)
            ->where(function (Builder $query) {
                $query->whereNull('payment_status')
                    ->orWhere('payment_status', 'Unpaid');
            })
            ->where('paid', 0)
            ->count();
        $commissionApproved = (clone $commissionAll)
            ->where(function (Builder $query) {
                $query->whereNull('payment_status')
                    ->orWhere('payment_status', 'Unpaid');
            })
            ->where('paid', '>', 0)
            ->count();
        $commissionApprovedNew = (clone $commissionNew)
            ->where(function (Builder $query) {
                $query->whereNull('payment_status')
                    ->orWhere('payment_status', 'Unpaid');
            })
            ->where('paid', '>', 0)
            ->count();
        $commissionPaidCount = (clone $commissionAll)
            ->where('payment_status', 'Paid')
            ->count();
        $commissionPaidCountNew = (clone $commissionNew)
            ->where('payment_status', 'Paid')
            ->count();
        $clawbackCases = (clone $commissionAll)
            ->where('paid', '<', 0)
            ->count();
        $clawbackCasesNew = (clone $commissionNew)
            ->where('paid', '<', 0)
            ->count();

        $commissionBySalesperson = $commissionQuery()
            ->join('deals', 'deals.id', '=', 'commissions.deal_id')
            ->join('users', 'users.id', '=', 'deals.salesperson_id')
            ->selectRaw('users.name as salesperson_name, COALESCE(SUM(deals.commission_amount),0) as total_commission, COALESCE(SUM(commissions.paid),0) as paid_commission')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_commission')
            ->get();

        $commissionByMonth = $commissionQuery()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COALESCE(SUM(paid), 0) as paid_commission")
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->orderBy('month')
            ->get();

        $canViewMonthlyPerformance = $isAdmin || $isLeader;

        return [
            'dashboardTitle' => $dashboardMode === 'deals' ? 'Admin Dashboard' : 'Sales Dashboard',
            'dashboardSubtitle' => $dashboardMode === 'deals'
                ? 'Admin overview across all deals and operations'
                : 'Sales and leader performance overview',
            'executive' => [
                'new_leads_month' => $totalLeadsMonth,
                'new_bookings' => $totalBookingsMonth,
                'completed_deals_month' => $totalCompletedDealsMonth,
                'approved_loans_month' => $approvedCount,
                'loan_approval_rate' => $loanApprovalRate,
                'new_deals_month' => $totalDealsMonth,
                'new_loan_cases_month' => $totalLoanCasesMonth,
                'total_commission_month' => $commissionTotal,
                'commission_payable' => $commissionPayable,
                'commission_paid' => $commissionPaid,
            ],
            'executiveTrends' => [
                'new_leads_month' => $trend($totalLeadsMonth, $totalLeadsPreviousMonth),
                'new_bookings' => $trend($totalBookingsMonth, $totalBookingsPreviousMonth),
                'completed_deals_month' => $trend($totalCompletedDealsMonth, $totalCompletedDealsPreviousMonth),
                'approved_loans_month' => $trend($approvedCount, $approvedPrevious),
                'loan_approval_rate' => $trend($loanApprovalRate, $loanApprovalRatePrevious),
                'new_deals_month' => $trend($totalDealsMonth, $totalDealsPreviousMonth),
                'new_loan_cases_month' => $trend($totalLoanCasesMonth, $totalLoanCasesPreviousMonth),
                'total_commission_month' => $trend((float) $commissionTotal, (float) $commissionTotalPrevious),
                'commission_payable' => $trend((float) $commissionPayable, (float) $commissionPayablePrevious),
                'commission_paid' => $trend((float) $commissionPaid, (float) $commissionPaidPrevious),
            ],
            'monthNav' => [
                'label' => $startMonth->format('F Y'),
                'current' => $startMonth->format('Y-m'),
                'prev_url' => $monthRoute($startMonth->copy()->subMonthNoOverflow()),
                'next_url' => $monthRoute($startMonth->copy()->addMonthNoOverflow()),
            ],
            'leadsBySource' => [],
            'pipelineStages' => [],
            'performanceCharts' => null,
            'dashboardChartsEndpoint' => route('dashboard.charts', [
                'month' => $startMonth->format('Y-m'),
                'mode' => $dashboardMode,
            ]),
            'dashboardPipelineDetailsEndpoint' => route('dashboard.pipeline-details', [
                'month' => $startMonth->format('Y-m'),
                'mode' => $dashboardMode,
            ]),
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
            'lead' => [
                'total_leads' => $totalLeadsBeforeMonth,
                'total_leads_history' => $totalLeadsHistory,
                'total_leads_new' => $totalLeadsNew,
                'total_lost_leads' => $totalLostLeadsBeforeMonth,
                'total_lost_leads_history' => $totalLostLeadsHistory,
                'total_lost_leads_new' => $totalLostLeadsNew,
                'contacted_leads' => $totalContactedLeadsBeforeMonth,
                'contacted_leads_history' => $totalContactedLeadsHistory,
                'contacted_leads_new' => $totalContactedLeadsNew,
                'scheduled_leads' => $totalScheduledLeadsBeforeMonth,
                'scheduled_leads_history' => $totalScheduledLeadsHistory,
                'scheduled_leads_new' => $totalScheduledLeadsNew,
                'leads_converted' => $totalBookingsBeforeMonth,
                'leads_converted_history' => $totalBookingsHistory,
                'leads_converted_new' => $totalBookingsNew,
                'leads_converted_rate' => $leadToBookingRateBeforeMonth,
            ],
            'deal' => [
                'total_deals' => $totalDealsBeforeMonth,
                'total_deals_new' => $totalDealsNew,
                'completed_deals' => $completedDealsBeforeMonth,
                'completed_deals_new' => $completedDealsNew,
                'incomplete_deals' => $incompleteDealsBeforeMonth,
                'incomplete_deals_new' => $incompleteDealsNew,
                'close_rate' => $dealCloseRateBeforeMonth,
                'avg_completion_days' => $avgCompletionDays ? round((float) $avgCompletionDays, 1) : null,
                'avg_commission_per_deal' => $avgCommissionPerDealBeforeMonth,
            ],
            'leaderboard' => $leaderboard,
            'loan' => [
                'total_cases' => $loanTotal,
                'total_cases_new' => $loanTotalNew,
                'pending_document_cases' => $pendingDocumentCases,
                'pending_document_cases_new' => $pendingDocumentCasesNew,
                'submitted_to_bank' => $loanSubmittedToBank,
                'submitted_to_bank_new' => $loanSubmittedToBankNew,
                'approved' => $loanApproved,
                'approved_new' => $loanApprovedNew,
                'rejected' => $loanRejected,
                'rejected_new' => $loanRejectedNew,
                'approval_rate' => $loanOverviewApprovalRate,
                'average_approval_days' => $avgApprovalDays ? round((float) $avgApprovalDays, 1) : null,
                'high_dsr_cases' => $highDsrCases,
                'high_dsr_cases_new' => $highDsrCasesNew,
            ],
            'legal' => [
                'drafting' => $legalDrafting,
                'drafting_new' => $legalDraftingNew,
                'awaiting_client_signature' => $legalAwaitingClientSignature,
                'awaiting_client_signature_new' => $legalAwaitingClientSignatureNew,
                'awaiting_bank' => $legalAwaitingBank,
                'awaiting_bank_new' => $legalAwaitingBankNew,
                'total_cases' => $legalTotal,
                'completed_cases' => $legalCompleted,
                'overdue_cases' => $legalOverdue,
                'overdue_cases_new' => $legalOverdueNew,
            ],
            'finance' => [
                'total_commission' => $totalCommissionBeforeMonth,
                'eligible' => $commissionEligible,
                'eligible_new' => $commissionEligibleNew,
                'pending_approval' => $commissionPendingApproval,
                'pending_approval_new' => $commissionPendingApprovalNew,
                'approved' => $commissionApproved,
                'approved_new' => $commissionApprovedNew,
                'paid' => $commissionPaidCount,
                'paid_new' => $commissionPaidCountNew,
                'clawback' => $clawbackCases,
                'clawback_new' => $clawbackCasesNew,
                'by_salesperson' => $commissionBySalesperson,
                'by_month' => $commissionByMonth,
            ],
            'canViewExecutive' => $dashboardMode === 'deals',
            'canViewSales' => $dashboardMode === 'sales' && $user->hasAnyRole([RoleEnum::SALESPERSON->value, RoleEnum::LEADER->value]),
            'canViewLoan' => $dashboardMode === 'deals' || $user->hasRole(RoleEnum::LEADER->value),
            'canViewLegal' => $dashboardMode === 'deals' || $user->hasAnyRole([RoleEnum::LEADER->value, RoleEnum::SALESPERSON->value]),
            'canViewFinance' => $dashboardMode === 'deals',
            'canViewMonthlyPerformance' => $canViewMonthlyPerformance,
        ];
    }

    public function buildGroupedPerformance(
        string $ownerColumn,
        Carbon $thisMonthStart,
        Carbon $thisMonthEnd
    ): array {
        $requiredRole = $ownerColumn === 'leader_id'
            ? RoleEnum::LEADER->value
            : RoleEnum::SALESPERSON->value;

        $eligibleUserIds = User::role($requiredRole)->pluck('id');
        if ($eligibleUserIds->isEmpty()) {
            return [
                'labels' => [],
                'deals' => [],
                'commission' => [],
            ];
        }

        $baseQuery = Deal::query()
            ->whereIn('pipeline', [
                PipelineEnum::COMPLETED->value,
                PipelineEnum::COMMISSION_PAID->value,
            ])
            ->whereIn($ownerColumn, $eligibleUserIds)
            ->whereNotNull($ownerColumn);

        $rows = (clone $baseQuery)
            ->whereBetween('created_at', [$thisMonthStart->toDateTimeString(), $thisMonthEnd->toDateTimeString()])
            ->selectRaw("{$ownerColumn} as user_id, COUNT(*) as deals_count, COALESCE(SUM(commission_amount), 0) as total_commission")
            ->groupBy($ownerColumn)
            ->get();

        if ($rows->isEmpty()) {
            return [
                'labels' => [],
                'deals' => [],
                'commission' => [],
            ];
        }

        $rankedRows = $rows
            ->sort(function ($left, $right) {
                $dealsComparison = ((int) $right->deals_count) <=> ((int) $left->deals_count);
                if ($dealsComparison !== 0) {
                    return $dealsComparison;
                }

                $commissionComparison = ((float) $right->total_commission) <=> ((float) $left->total_commission);
                if ($commissionComparison !== 0) {
                    return $commissionComparison;
                }

                return ((int) $left->user_id) <=> ((int) $right->user_id);
            })
            ->take(5)
            ->values();
        $rankedUserIds = $rankedRows->pluck('user_id');

        $userNames = User::query()
            ->whereIn('id', $rankedUserIds)
            ->pluck('name', 'id');

        return [
            'labels' => $rankedRows->map(fn ($row) => (string) ($userNames[$row->user_id] ?? "User #{$row->user_id}"))->all(),
            'deals' => $rankedRows->map(fn ($row) => (float) ($row->deals_count ?? 0))->all(),
            'commission' => $rankedRows->map(fn ($row) => (float) ($row->total_commission ?? 0))->all(),
        ];
    }

    public function authorizeDashboardMode(User $user, string $mode): void
    {
        if ($mode === 'deals') {
            abort_unless($user->hasRole(RoleEnum::ADMIN->value), 403);

            return;
        }

        abort_unless(
            $user->hasAnyRole([
                RoleEnum::ADMIN->value,
                RoleEnum::SALESPERSON->value,
                RoleEnum::LEADER->value,
            ]),
            403
        );
    }

    private function countBetween(Builder $query, string $column, mixed $start, mixed $end): int
    {
        return (clone $query)->whereBetween($column, [$start, $end])->count();
    }

    private function sumBetween(Builder $query, string $betweenColumn, mixed $start, mixed $end, string $sumColumn): float
    {
        return (float) (clone $query)->whereBetween($betweenColumn, [$start, $end])->sum($sumColumn);
    }

    public function buildChartPayload(User $user, string $mode, Carbon $selectedMonth): array
    {
        $startMonth = $selectedMonth->copy()->startOfMonth();
        $endMonth = $selectedMonth->copy()->endOfMonth();

        $pipelineStageTotals = $this->resolveDealQuery($user, $mode)
            ->whereBetween('deals.created_at', [$startMonth, $endMonth])
            ->selectRaw('pipeline, COUNT(*) as total')
            ->groupBy('pipeline')
            ->pluck('total', 'pipeline');

        $pipelineStages = collect(PipelineEnum::cases())
            ->mapWithKeys(fn (PipelineEnum $stage) => [
                $stage->value => (int) ($pipelineStageTotals[$stage->value] ?? 0),
            ])
            ->all();

        $monthlyLeadSourcesRaw = $this->resolveLeadQuery($user, $mode)
            ->whereBetween('created_at', [$startMonth, $endMonth])
            ->selectRaw('COALESCE(NULLIF(source, ""), "Unknown") as source_name, COUNT(*) as total')
            ->groupBy('source_name')
            ->orderByDesc('total')
            ->get();
        $leadSourceTotal = (int) $monthlyLeadSourcesRaw->sum('total');

        $sourceOrder = [
            'Facebook',
            'Friend Referral',
            'Exhibition/Fair',
            'Company Assigned',
            'Old Client Referral',
            'Unknown',
        ];
        $sourceTotals = $monthlyLeadSourcesRaw
            ->mapWithKeys(fn ($row) => [(string) $row->source_name => (int) $row->total]);
        $dynamicSources = $sourceTotals
            ->keys()
            ->filter(fn ($source) => ! in_array($source, $sourceOrder, true))
            ->values()
            ->all();
        $orderedSources = array_values(array_unique(array_merge($sourceOrder, $dynamicSources)));

        $leadSourcesPalette = ['#2563eb', '#14b8a6', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4', '#84cc16', '#ec4899'];
        $leadsBySource = collect($orderedSources)->map(function ($source, $index) use ($sourceTotals, $leadSourceTotal, $leadSourcesPalette) {
            $count = (int) ($sourceTotals[$source] ?? 0);
            $percent = $leadSourceTotal > 0 ? round(($count / $leadSourceTotal) * 100, 2) : 0;

            return [
                'source' => $source,
                'count' => $count,
                'percent' => $percent,
                'color' => $leadSourcesPalette[$index % count($leadSourcesPalette)],
            ];
        })->filter(fn ($row) => $row['count'] > 0)->values()->all();

        $canViewMonthlyPerformance = $user->hasRole(RoleEnum::ADMIN->value) || $user->hasRole(RoleEnum::LEADER->value);
        $performanceCharts = $canViewMonthlyPerformance ? [
            'period' => $startMonth->format('F Y'),
            'salesperson_performance' => $this->buildGroupedPerformance('salesperson_id', $startMonth, $endMonth),
            'leader_performance' => $this->buildGroupedPerformance('leader_id', $startMonth, $endMonth),
        ] : null;
        $commissionTrend = $this->buildCommissionTrend($user, $mode, $startMonth, $endMonth);

        return [
            'leadsBySource' => $leadsBySource,
            'pipelineStages' => $pipelineStages,
            'salesPerformance' => $performanceCharts,
            'commissionTrend' => $commissionTrend,
        ];
    }

    public function buildPipelineDetailsPayload(
        User $user,
        string $mode,
        Carbon $selectedMonth,
        PipelineEnum $stageEnum
    ): array {
        $startMonth = $selectedMonth->copy()->startOfMonth();
        $endMonth = $selectedMonth->copy()->endOfMonth();

        $stageDateColumn = self::PIPELINE_DATE_COLUMN_MAP[$stageEnum->value] ?? null;
        abort_if(! $stageDateColumn, 422, 'Unsupported pipeline stage.');

        $rows = $this->resolveDealQuery($user, $mode)
            ->leftJoin('users as salesperson', 'salesperson.id', '=', 'deals.salesperson_id')
            ->leftJoin('users as leader', 'leader.id', '=', 'deals.leader_id')
            ->leftJoin('deal_pipelines as pipeline_dates', 'pipeline_dates.deal_id', '=', 'deals.id')
            ->whereBetween('deals.created_at', [$startMonth->toDateTimeString(), $endMonth->toDateTimeString()])
            ->where('deals.pipeline', $stageEnum->value)
            ->orderBy('deals.created_at')
            ->orderBy('deals.id')
            ->limit(200)
            ->get([
                'deals.deal_id',
                'deals.project_name',
                'salesperson.name as salesperson_name',
                'leader.name as leader_name',
                'deals.created_at',
                "pipeline_dates.{$stageDateColumn} as stage_date",
            ]);

        $stageDateLabel = sprintf('%s Date', $stageEnum->value);

        return [
            'stage' => $stageEnum->value,
            'stageDateLabel' => $stageDateLabel,
            'rows' => $rows->map(function ($row) {
                return [
                    'deal_id' => (string) ($row->deal_id ?? '-'),
                    'project_name' => (string) ($row->project_name ?? '-'),
                    'salesperson' => (string) ($row->salesperson_name ?? '-'),
                    'leader' => (string) ($row->leader_name ?? '-'),
                    'created_date' => $row->created_at ? Carbon::parse($row->created_at)->format('Y-m-d') : '-',
                    'stage_date' => $row->stage_date ? Carbon::parse($row->stage_date)->format('Y-m-d') : '-',
                ];
            })->values()->all(),
        ];
    }

    protected function resolveDealQuery(User $user, string $mode): Builder
    {
        if ($mode === 'deals' && $user->hasRole(RoleEnum::ADMIN->value)) {
            return Deal::query();
        }

        return Deal::visibleTo($user);
    }

    protected function resolveLeadQuery(User $user, string $mode): Builder
    {
        if ($mode === 'deals' && $user->hasRole(RoleEnum::ADMIN->value)) {
            return Lead::query();
        }

        if ($user->hasRole(RoleEnum::LEADER->value)) {
            return Lead::query()->where('leader_id', $user->id);
        }

        if ($user->hasRole(RoleEnum::SALESPERSON->value)) {
            return Lead::query()->where('salesperson_id', $user->id);
        }

        return Lead::query()->whereRaw('1 = 0');
    }

    protected function buildCommissionTrend(
        User $user,
        string $mode,
        Carbon $monthStart,
        Carbon $monthEnd
    ): array {
        $trendStart = $monthStart->copy()->subMonthsNoOverflow(4)->startOfMonth();
        $trendEnd = $monthEnd->copy()->endOfMonth();

        $totalsByMonth = $this->resolveDealQuery($user, $mode)
            ->join('deal_pipelines', 'deal_pipelines.deal_id', '=', 'deals.id')
            ->whereNotNull('deal_pipelines.completed_date')
            ->whereBetween('deal_pipelines.completed_date', [
                $trendStart->toDateTimeString(),
                $trendEnd->toDateTimeString(),
            ])
            ->selectRaw("DATE_FORMAT(deal_pipelines.completed_date, '%Y-%m') as period, COALESCE(SUM(deals.commission_amount), 0) as total_commission")
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('total_commission', 'period');

        $labels = [];
        $values = [];

        for ($month = $trendStart->copy(); $month->lte($monthEnd); $month->addMonthNoOverflow()) {
            $period = $month->format('Y-m');
            $labels[] = $month->format('M Y');
            $values[] = (float) ($totalsByMonth[$period] ?? 0);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}
