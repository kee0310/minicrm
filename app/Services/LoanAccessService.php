<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class LoanAccessService
{
    public function canManageLoanRecords(?User $user): bool
    {
        return $user
            && ($user->hasRole(RoleEnum::ADMIN->value) || $user->hasRole(RoleEnum::LOAN_OFFICER->value));
    }

    public function canManageLegalRecords(?User $user): bool
    {
        return $user
            && (
                $user->hasRole(RoleEnum::ADMIN->value)
                || $user->hasRole(RoleEnum::LOAN_OFFICER->value)
                || $user->hasRole(RoleEnum::LEGAL_OFFICER->value)
            );
    }

    public function ensureCanManageLoanRecords(?User $user): void
    {
        abort_unless($this->canManageLoanRecords($user), 403);
    }

    public function ensureCanManageLegalRecords(?User $user): void
    {
        abort_unless($this->canManageLegalRecords($user), 403);
    }

    public function scopeDealsForLoanAccess(Builder $query, ?User $user): Builder
    {
        abort_if(! $user, 403);

        if ($user->hasRole(RoleEnum::SALESPERSON->value) || $user->hasRole(RoleEnum::LEADER->value)) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('salesperson_id', $user->id)
                    ->orWhere('leader_id', $user->id);
            });
        }

        return $query;
    }

    public function scopeDealsForRestrictedLoanAccess(Builder $query, ?User $user): Builder
    {
        abort_if(! $user, 403);

        abort_unless(
            $user->hasAnyRole([
                RoleEnum::ADMIN->value,
                RoleEnum::SALESPERSON->value,
                RoleEnum::LEADER->value,
                RoleEnum::LOAN_OFFICER->value,
            ]),
            403
        );

        if ($user->hasRole(RoleEnum::ADMIN->value)) {
            return $query;
        }

        $query->where(function (Builder $q) use ($user) {
            if ($user->hasRole(RoleEnum::SALESPERSON->value)) {
                $q->where('salesperson_id', $user->id);
            }

            if ($user->hasRole(RoleEnum::LEADER->value)) {
                $q->orWhere('salesperson_id', $user->id)
                    ->orWhere('leader_id', $user->id);
            }

            if ($user->hasRole(RoleEnum::LOAN_OFFICER->value)) {
                $q->orWhere('deals.loan_officer_id', $user->id);
            }
        });

        return $query;
    }

    public function scopeLoanSubmissionsForLoanAccess(Builder $query, ?User $user): Builder
    {
        abort_if(! $user, 403);

        if ($user->hasRole(RoleEnum::SALESPERSON->value) || $user->hasRole(RoleEnum::LEADER->value)) {
            $query->whereHas('deal', function (Builder $dealQuery) use ($user) {
                $dealQuery->where('salesperson_id', $user->id)
                    ->orWhere('leader_id', $user->id);
            });
        }

        return $query;
    }

    public function scopeLoanSubmissionsForRestrictedLoanAccess(Builder $query, ?User $user): Builder
    {
        abort_if(! $user, 403);

        abort_unless(
            $user->hasAnyRole([
                RoleEnum::ADMIN->value,
                RoleEnum::SALESPERSON->value,
                RoleEnum::LEADER->value,
                RoleEnum::LOAN_OFFICER->value,
            ]),
            403
        );

        if ($user->hasRole(RoleEnum::ADMIN->value)) {
            return $query;
        }

        $query->where(function (Builder $submissionQuery) use ($user) {
            if ($user->hasRole(RoleEnum::SALESPERSON->value)) {
                $submissionQuery->whereHas('deal', function (Builder $dealQuery) use ($user) {
                    $dealQuery->where('salesperson_id', $user->id);
                });
            }

            if ($user->hasRole(RoleEnum::LOAN_OFFICER->value)) {
                $submissionQuery->orWhereHas('deal', function (Builder $dealQuery) use ($user) {
                    $dealQuery->where('loan_officer_id', $user->id);
                });
            }

            if ($user->hasRole(RoleEnum::LEADER->value)) {
                $submissionQuery->orWhereHas('deal', function (Builder $dealQuery) use ($user) {
                    $dealQuery->where('salesperson_id', $user->id)
                        ->orWhere('leader_id', $user->id);
                });
            }
        });

        return $query;
    }

    public function ensureCanViewDeal(Deal $deal, ?User $user): void
    {
        $isAllowed = $this->scopeDealsForLoanAccess(Deal::query(), $user)
            ->whereKey($deal->id)
            ->exists();

        abort_unless($isAllowed, 403);
    }

    public function ensureCanViewRestrictedDeal(Deal $deal, ?User $user): void
    {
        $isAllowed = $this->scopeDealsForRestrictedLoanAccess(Deal::query(), $user)
            ->whereKey($deal->id)
            ->exists();

        abort_unless($isAllowed, 403);
    }
}
