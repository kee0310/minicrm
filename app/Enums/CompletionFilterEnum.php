<?php

namespace App\Enums;

enum CompletionFilterEnum: string
{
    case NEW = 'new';
    case COMPLETED = 'completed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
