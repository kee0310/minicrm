<?php

namespace App\Services;

use App\Enums\LegalStatusEnum;
use App\Enums\PipelineEnum;
use App\Enums\RoleEnum;
use App\Models\Deal;
use App\Models\LegalCase;
use App\Models\User;

class LegalService
{
    public function __construct(private OfficerAssignmentService $officerAssignment) {}

    public function updateLegalCase(Deal $deal, array $data, ?User $user): LegalCase
    {
        $legalCase = $deal->legalCase()->updateOrCreate(
            ['deal_id' => $deal->id],
            [
                'status' => $data['status'] ?? ($deal->legalCase?->status ?? LegalStatusEnum::DRAFTING->value),
                'lawyer_firm' => $data['lawyer_firm'] ?? null,
                'spa_date' => $data['spa_date'] ?? null,
                'loan_agreement_date' => $data['loan_agreement_date'] ?? null,
                'completion_date' => $data['completion_date'] ?? null,
                'stamp_duty' => (bool) ($data['stamp_duty'] ?? false),
            ]
        );

        $this->officerAssignment->assignOfficerIfNeeded(
            $deal,
            $user,
            isset($data['assign_to']) ? (int) $data['assign_to'] : null,
            RoleEnum::LEGAL_OFFICER->value,
            'legal_officer_id'
        );

        if ($legalCase->status === LegalStatusEnum::COMPLETED->value) {
            $deal->syncPipelineStage(PipelineEnum::COMPLETED);
        } else {
            $deal->syncPipelineStage(PipelineEnum::LEGAL_PROCESSING);
        }

        return $legalCase;
    }
}
