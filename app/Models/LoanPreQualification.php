<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanPreQualification extends Model
{
    use HasFactory;

    protected $fillable = [
        'deal_id',
        'existing_loans',
        'monthly_commitments',
        'credit_card_limits',
        'credit_card_utilization',
        'ccris',
        'ctos',
        'risk_grade',
        'pre_qualification_date',
        'recommended_banks',
    ];

    protected $casts = [
        'existing_loans' => 'decimal:2',
        'monthly_commitments' => 'decimal:2',
        'credit_card_limits' => 'decimal:2',
        'credit_card_utilization' => 'decimal:2',
        'pre_qualification_date' => 'date',
        'recommended_banks' => 'array',
    ];

    public function deal()
    {
        return $this->belongsTo(Deal::class, 'deal_id');
    }

    public function riskScore(): ?int
    {
        $client = $this->deal?->client;
        $commitments = (float) ($this->monthly_commitments ?? 0);
        $income = (float) ($client?->monthly_income ?? 0);
        $ccris = strtolower((string) $this->ccris);
        $ctos = strtolower((string) $this->ctos);

        if (!$this->hasAnyRiskInput($client, $ccris, $ctos)) {
            return null;
        }

        $incomeStabilityRisk = $this->incomeStabilityRisk($client, $income);
        $dsrRisk = $this->debtServiceRatioRisk($income, $commitments);
        $ccrisRisk = $this->ccrisRisk($ccris, $ctos);
        $documentIntegrityRisk = $this->documentIntegrityRisk($client, $ccris, $ctos);
        $ccUtilRisk = $this->creditCardRisk();

        $score = ($incomeStabilityRisk * 0.20)
            + ($dsrRisk * 0.30)
            + ($ccrisRisk * 0.20)
            + ($ccUtilRisk * 0.15)
            + ($documentIntegrityRisk * 0.15);

        return (int) max(0, min(100, round($score)));
    }

    public function riskGrade(): ?string
    {
        $score = $this->riskScore();

        if (is_null($score)) {
            return null;
        }

        if ($score >= 70) {
            return 'C';
        }

        if ($score >= 40) {
            return 'B';
        }

        return 'A';
    }

    protected function hasAnyRiskInput(?Client $client, string $ccris, string $ctos): bool
    {
        return !(
            is_null($this->existing_loans) &&
            is_null($this->monthly_commitments) &&
            is_null($this->credit_card_limits) &&
            is_null($this->credit_card_utilization) &&
            trim($ccris) === '' &&
            trim($ctos) === '' &&
            is_null($client?->monthly_income) &&
            empty($client?->occupation) &&
            empty($client?->company) &&
            empty($client?->ic_passport)
        );
    }

    protected function incomeStabilityRisk(?Client $client, float $income): int
    {
        if ($income <= 0) {
            return 90;
        }

        $risk = 20;

        if ($income < 3000) {
            $risk += 25;
        } elseif ($income < 6000) {
            $risk += 15;
        } elseif ($income < 10000) {
            $risk += 8;
        }

        if (empty($client?->occupation)) {
            $risk += 15;
        }

        if (empty($client?->company)) {
            $risk += 10;
        }

        return (int) max(0, min(100, $risk));
    }

    protected function debtServiceRatioRisk(float $income, float $commitments): int
    {
        if ($income <= 0) {
            return 95;
        }

        $dsr = $commitments / $income;

        if ($dsr <= 0.40) {
            return 10;
        }
        if ($dsr <= 0.60) {
            return 35;
        }
        if ($dsr <= 0.75) {
            return 70;
        }
        return 95;
    }

    protected function ccrisRisk(string $ccris, string $ctos): int
    {
        $text = trim($ccris . ' ' . $ctos);
        if ($text === '') {
            return 40;
        }

        $severe = ['bankrupt', 'legal', 'default', 'written off', 'judgment', 'blacklist'];
        foreach ($severe as $keyword) {
            if (str_contains($text, $keyword)) {
                return 95;
            }
        }

        $moderate = ['late', 'arrears', 'overdue', 'delinquent', 'restructure', 'reschedule'];
        foreach ($moderate as $keyword) {
            if (str_contains($text, $keyword)) {
                return 65;
            }
        }

        $good = ['clean', 'good', 'current', 'no issue', 'no issues'];
        foreach ($good as $keyword) {
            if (str_contains($text, $keyword)) {
                return 10;
            }
        }

        return 35;
    }

    protected function documentIntegrityRisk(?Client $client, string $ccris, string $ctos): int
    {
        $fields = [
            $client?->ic_passport,
            $client?->occupation,
            $client?->company,
            $client?->monthly_income,
            $this->existing_loans,
            $this->monthly_commitments,
            $this->credit_card_limits,
            $this->credit_card_utilization,
            trim($ccris) === '' ? null : $ccris,
            trim($ctos) === '' ? null : $ctos,
        ];

        $total = count($fields);
        $filled = collect($fields)->filter(function ($value) {
            if (is_string($value)) {
                return trim($value) !== '';
            }

            return !is_null($value);
        })->count();

        $completeness = $total > 0 ? ($filled / $total) * 100 : 0;

        return (int) max(0, min(100, round(100 - $completeness)));
    }

    protected function creditCardRisk(): int
    {
        $util = (float) ($this->credit_card_utilization ?? 0);

        if ($util <= 0) {
            return 20;
        }

        if ($util <= 30) {
            return 10;
        }

        if ($util <= 60) {
            return 40;
        }

        if ($util <= 80) {
            return 70;
        }

        return 90;
    }
}

