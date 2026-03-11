<?php

namespace App\Enums;

enum LegalStatusEnum: string
{
    case DRAFTING = 'Drafting';
    case PENDING_BANK = 'Pending Bank';
    case PENDING_CUSTOMER_SIGNATURE = 'Pending Customer Signature';
    case COMPLETED = 'Completed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
