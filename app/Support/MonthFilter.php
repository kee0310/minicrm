<?php

namespace App\Support;

use Illuminate\Support\Carbon;

final class MonthFilter
{
    public static function resolve(?string $monthParam): Carbon
    {
        $value = (string) ($monthParam ?? '');

        return preg_match('/^\d{4}-\d{2}$/', $value)
            ? Carbon::createFromFormat('Y-m', $value)->startOfMonth()
            : Carbon::now()->startOfMonth();
    }

    public static function options(Carbon $selectedMonth, int $monthsBack = 11): array
    {
        $start = $selectedMonth->copy()->subMonthsNoOverflow($monthsBack)->startOfMonth();
        $options = [];

        for ($month = $start->copy(); $month->lte($selectedMonth); $month->addMonthNoOverflow()) {
            $options[] = [
                'value' => $month->format('Y-m'),
                'label' => $month->format('M Y'),
            ];
        }

        return $options;
    }
}
