<?php

namespace App\Enums;

enum LoanApprovalStatusEnum: string
{
    case PREPARED = 'Prepared';
    case SUBMITTED = 'Submitted';
    case IN_REVIEW = 'In Review';
    case APPROVED = 'Approved';
    case REJECTED = 'Rejected';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function submittedToBank(): array
    {
        return [
            self::SUBMITTED->value,
            self::IN_REVIEW->value,
            self::APPROVED->value,
            self::REJECTED->value,
        ];
    }

    public static function activeCases(): array
    {
        return [
            self::PREPARED->value,
            self::SUBMITTED->value,
            self::IN_REVIEW->value,
        ];
    }
}
