<?php

namespace App\Enums;

enum BankEnum: string
{
  case MAYBANK = 'Maybank';
  case CIMB = 'CIMB';
  case PUBLIC_BANK = 'Public Bank';
  case RHB = 'RHB';
  case OTHER = 'Other';

  public static function values(): array
  {
    return array_column(self::cases(), 'value');
  }
}
