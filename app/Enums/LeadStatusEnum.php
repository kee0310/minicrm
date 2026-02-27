<?php

namespace App\Enums;

enum LeadStatusEnum: string
{
    case NEW = 'New';
    case CONTACTED = 'Contacted';
    case SCHEDULED = 'Scheduled';
    case DEAL = 'Deal';
    case LOST = 'Lost';

    public function color(): string
    {
        return match ($this) {
            self::NEW => 'bg-gray-100 text-gray-800',
            self::CONTACTED => 'bg-blue-100 text-blue-800',
            self::SCHEDULED => 'bg-yellow-100 text-yellow-800',
            self::DEAL => 'bg-green-100 text-green-800',
            self::LOST => 'bg-red-100 text-red-800',
        };
    }

    public function badge(): string
    {
        return "px-2 py-1 text-xs font-semibold rounded-full " . $this->color();
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

