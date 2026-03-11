<?php

namespace App\Services;

use App\Enums\LoanApprovalStatusEnum;
use App\Enums\PipelineEnum;
use App\Enums\RoleEnum;
use App\Models\Deal;
use App\Models\LoanBankSubmission;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class LoanNotificationService
{
    /**
     * @return array<string, int>
     */
    public function forUser(?User $user): array
    {
        $default = [
            'borrower_profile' => 0,
            'pre_qualification' => 0,
            'bank_submission' => 0,
            'approval_analysis' => 0,
            'disbursement' => 0,
            'legal_new' => 0,
        ];

        if (! $user) {
            return $default;
        }

        $roleFingerprint = md5($user->getRoleNames()->sort()->implode('|'));
        $cacheKey = sprintf('loan-notifications:user:%d:roles:%s', $user->id, $roleFingerprint);

        return Cache::remember($cacheKey, now()->addSeconds(30), function () use ($user, $default): array {
            $badges = $default;

            if ($user->hasRole(RoleEnum::LOAN_OFFICER->value)) {
                $loanOfficerDeals = Deal::query()->where('loan_officer_id', $user->id);

                $badges['borrower_profile'] = Deal::query()
                    ->whereNull('loan_officer_id')
                    ->count();

                $badges['pre_qualification'] = (clone $loanOfficerDeals)
                    ->whereIn('pipeline', PipelineEnum::values())
                    ->where(function ($query) {
                        $query->doesntHave('preQualification')
                            ->orWhereHas('preQualification', function ($preQuery) {
                                $preQuery->whereNull('pre_qualification_date')
                                    ->where(function ($bankQuery) {
                                        $bankQuery->whereNull('recommended_banks')
                                            ->orWhereJsonLength('recommended_banks', 0);
                                    });
                            });
                    })
                    ->count();

                $badges['bank_submission'] = (clone $loanOfficerDeals)
                    ->whereIn('pipeline', [
                        PipelineEnum::BOOKING->value,
                        PipelineEnum::SPA_SIGNED->value,
                        PipelineEnum::LOAN_SUBMITTED->value,
                        PipelineEnum::LOAN_APPROVED->value,
                        PipelineEnum::LEGAL_PROCESSING->value,
                        PipelineEnum::COMPLETED->value,
                        PipelineEnum::COMMISSION_PAID->value,
                    ])
                    ->doesntHave('bankSubmissions')
                    ->count();

                $loanOfficerApprovedLoans = LoanBankSubmission::query()
                    ->where('approval_status', LoanApprovalStatusEnum::APPROVED->value)
                    ->whereHas('deal', function ($query) use ($user) {
                        $query->where('loan_officer_id', $user->id);
                    });

                $badges['approval_analysis'] = (clone $loanOfficerApprovedLoans)
                    ->whereNull('applied_amount')
                    ->whereNull('approved_amount')
                    ->whereNull('interest_rate')
                    ->whereNull('lock_in_period')
                    ->whereNull('mrta_mlta')
                    ->whereNull('special_conditions')
                    ->count();

                $badges['disbursement'] = (clone $loanOfficerApprovedLoans)
                    ->whereNull('first_disbursement_date')
                    ->whereNull('full_disbursement_date')
                    ->whereNull('spa_completion_date')
                    ->whereNull('client_notification_date')
                    ->count();
            }

            if ($user->hasRole(RoleEnum::LEGAL_OFFICER->value)) {
                $badges['legal_new'] = Deal::query()
                    ->whereIn('pipeline', [
                        PipelineEnum::LOAN_APPROVED->value,
                        PipelineEnum::LEGAL_PROCESSING->value,
                        PipelineEnum::COMPLETED->value,
                    ])
                    ->whereDoesntHave('legalCase')
                    ->count();
            }

            return $badges;
        });
    }
}
