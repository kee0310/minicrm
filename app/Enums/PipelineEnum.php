<?php

namespace App\Enums;

enum PipelineEnum: string
{
  case NEW = 'New';
  case VIEWING = 'Viewing';
  case BOOKING = 'Booking';
  case SPA_SIGNED = 'SPA Signed';
  case LOAN_SUBMITTED = 'Loan Submitted';
  case LOAN_APPROVED = 'Loan Approved';
  case LEGAL_PROCESSING = 'Legal Processing';
  case COMPLETED = 'Completed';
  case COMMISSION_PAID = 'Commission Paid';

  /**
   * Badge color classes
   */
  public function color(): string
  {
    return match ($this) {
      self::NEW => 'bg-gray-100 text-gray-800',
      self::VIEWING => 'bg-blue-100 text-blue-800',
      self::BOOKING => 'bg-yellow-100 text-yellow-800',
      self::SPA_SIGNED => 'bg-purple-100 text-purple-800',
      self::LOAN_SUBMITTED => 'bg-orange-100 text-orange-800',
      self::LOAN_APPROVED => 'bg-green-100 text-green-800',
      self::LEGAL_PROCESSING => 'bg-indigo-100 text-indigo-800',
      self::COMPLETED => 'bg-emerald-100 text-emerald-800',
      self::COMMISSION_PAID => 'bg-teal-100 text-teal-800',
    };
  }

  /**
   * Badge style (optional future use)
   */
  public function badge(): string
  {
    return "px-2 py-1 text-xs font-semibold rounded-full " . $this->color();
  }

  /**
   * Get all enum values
   */
  public static function values(): array
  {
    return array_column(self::cases(), 'value');
  }

  /**
   * Stages that can be manually selected during deal creation.
   */
  public static function creatableCases(): array
  {
    return [
      self::NEW,
      self::VIEWING,
      self::BOOKING,
      self::SPA_SIGNED,
    ];
  }

  public static function creatableValues(): array
  {
    return array_column(self::creatableCases(), 'value');
  }

  /**
   * Stages that are system-driven and should no longer be manually edited.
   */
  public static function lockedCases(): array
  {
    return [
      self::LOAN_SUBMITTED,
      self::LOAN_APPROVED,
      self::LEGAL_PROCESSING,
      self::COMPLETED,
      self::COMMISSION_PAID,
    ];
  }

  public function isLockedForManualEdit(): bool
  {
    return in_array($this, self::lockedCases(), true);
  }
}
