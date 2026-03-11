<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanPreQualification extends Model
{
    use HasFactory;

    // Weighted component model. All component scores are normalized to 0-100.
    private const COMPONENT_WEIGHTS = [
        'affordability' => 0.40,
        'leverage' => 0.25,
        'repayment_behavior' => 0.25,
        'profile_completeness' => 0.05,
        'employment_stability' => 0.05,
    ];

    // Grade mapping remains A/B/C to keep existing UI/database compatibility.
    private const GRADE_THRESHOLDS = [
        'C' => 80,
        'B' => 60,
    ];

    private const DSR_BANDS = [
        ['max' => 0.35, 'risk' => 10],
        ['max' => 0.50, 'risk' => 28],
        ['max' => 0.65, 'risk' => 55],
        ['max' => 0.80, 'risk' => 78],
    ];

    private const DEBT_TO_ANNUAL_INCOME_BANDS = [
        ['max' => 1.0, 'risk' => 8],
        ['max' => 2.0, 'risk' => 20],
        ['max' => 4.0, 'risk' => 35],
        ['max' => 6.0, 'risk' => 50],
    ];

    private const CARD_UTILIZATION_BANDS = [
        ['max' => 0.30, 'risk' => 10],
        ['max' => 0.60, 'risk' => 30],
        ['max' => 0.80, 'risk' => 55],
        ['max' => 1.00, 'risk' => 75],
    ];

    private const MONTHLY_INCOME_BANDS = [
        ['max' => 2500, 'risk' => 75],
        ['max' => 5000, 'risk' => 55],
        ['max' => 8000, 'risk' => 35],
        ['max' => INF, 'risk' => 18],
    ];

    private const KEYWORD_RISK_GROUPS = [
        // Severe adverse credit signals
        95 => [
            'bankrupt',
            'bankruptcy',
            'insolvency',
            'legal action',
            'summon',
            'judgment',
            'default',
            'written off',
            'write off',
            'blacklist',
            'npl',
            'non performing',
        ],
        // Moderate caution signals
        65 => [
            'late',
            'late payment',
            'arrears',
            'overdue',
            'delinquent',
            'restructure',
            'reschedule',
            'special attention',
            'watchlist',
            'high utilization',
            'min payment only',
        ],
        // Positive repayment signals
        10 => [
            'clean',
            'good',
            'current',
            'timely',
            'on time',
            'no issue',
            'no issues',
            'no overdue',
            'no arrears',
            'no late payment',
            'ok',
            'clear',
            'pass',
        ],
    ];

    private const MISSING_REPAYMENT_DATA_RISK = 30;

    private const UNKNOWN_REPAYMENT_TEXT_RISK = 20;

    private const MISSING_INCOME_RISK = 95;

    private const MISSING_EMPLOYMENT_FIELD_PENALTY = 12;

    private const MISSING_CARD_LIMIT_FOR_UTIL_PENALTY = 12;

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
        'pre_qualification_date' => 'date',
        'recommended_banks' => 'array',
    ];

    public function riskScore(): ?int
    {
        $client = $this->deal?->client;
        $income = (float) ($client?->monthly_income ?? 0);
        $commitments = (float) ($this->monthly_commitments ?? 0);
        $existingLoans = (float) ($this->existing_loans ?? 0);
        $cardLimits = (float) ($this->credit_card_limits ?? 0);
        $cardUtilization = (float) ($this->credit_card_utilization ?? 0);
        $ccris = strtolower((string) $this->ccris);
        $ctos = strtolower((string) $this->ctos);

        if (! $this->hasAnyRiskInput($client, $ccris, $ctos)) {
            return null;
        }

        $componentScores = [
            'affordability' => $this->affordabilityRisk($income, $commitments),
            'leverage' => $this->leverageRisk($income, $existingLoans, $cardLimits, $cardUtilization),
            'repayment_behavior' => $this->repaymentBehaviorRisk($ccris, $ctos),
            'profile_completeness' => $this->profileCompletenessRisk($client, $ccris, $ctos),
            'employment_stability' => $this->employmentStabilityRisk($client, $income),
        ];

        $weighted = 0.0;
        foreach (self::COMPONENT_WEIGHTS as $component => $weight) {
            $weighted += ($componentScores[$component] ?? 0) * $weight;
        }

        return (int) round($this->clamp($weighted));
    }

    public function riskGrade(): ?string
    {
        $score = $this->riskScore();

        if (is_null($score)) {
            return null;
        }

        if ($score >= self::GRADE_THRESHOLDS['C']) {
            return 'C';
        }

        if ($score >= self::GRADE_THRESHOLDS['B']) {
            return 'B';
        }

        return 'A';
    }

    protected function hasAnyRiskInput(?Lead $client, string $ccris, string $ctos): bool
    {
        return ! (
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

    protected function employmentStabilityRisk(?Lead $client, float $income): int
    {
        if ($income <= 0) {
            return self::MISSING_INCOME_RISK;
        }

        $risk = $this->bandedRisk($income, self::MONTHLY_INCOME_BANDS, self::MISSING_INCOME_RISK);

        if (empty($client?->occupation)) {
            $risk += self::MISSING_EMPLOYMENT_FIELD_PENALTY;
        }

        if (empty($client?->company)) {
            $risk += self::MISSING_EMPLOYMENT_FIELD_PENALTY;
        }

        return (int) round($this->clamp($risk));
    }

    protected function affordabilityRisk(float $income, float $commitments): int
    {
        if ($income <= 0) {
            return self::MISSING_INCOME_RISK;
        }

        $dsr = $commitments / $income;

        return $this->bandedRisk($dsr, self::DSR_BANDS, self::MISSING_INCOME_RISK);
    }

    protected function leverageRisk(float $income, float $existingLoans, float $cardLimits, float $cardUtilization): int
    {
        $debtRisk = 45;
        if ($income > 0) {
            $debtToAnnualIncome = $existingLoans / max(1.0, $income * 12);
            $debtRisk = $this->bandedRisk($debtToAnnualIncome, self::DEBT_TO_ANNUAL_INCOME_BANDS, 75);
        }

        if ($cardLimits > 0) {
            $utilization = $cardUtilization / 100;
            $cardRisk = $this->bandedRisk($utilization, self::CARD_UTILIZATION_BANDS, 85);
        } elseif (! is_null($this->credit_card_utilization)) {
            $cardRisk = self::MISSING_CARD_LIMIT_FOR_UTIL_PENALTY + 45;
        } else {
            $cardRisk = 30;
        }

        // Blend debt leverage and revolving credit behavior.
        return (int) round($this->clamp(($debtRisk * 0.6) + ($cardRisk * 0.4)));
    }

    protected function repaymentBehaviorRisk(string $ccris, string $ctos): int
    {
        $text = $this->normalizeRiskText(trim($ccris.' '.$ctos));
        if ($text === '') {
            return self::MISSING_REPAYMENT_DATA_RISK;
        }

        foreach (self::KEYWORD_RISK_GROUPS as $risk => $keywords) {
            if ($this->containsKeyword($text, $keywords)) {
                return (int) $risk;
            }
        }

        return self::UNKNOWN_REPAYMENT_TEXT_RISK;
    }

    protected function profileCompletenessRisk(?Lead $client, string $ccris, string $ctos): int
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

            return ! is_null($value);
        })->count();

        $completeness = $total > 0 ? ($filled / $total) * 100 : 0;

        return (int) round($this->clamp(100 - $completeness));
    }

    protected function containsKeyword(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $this->normalizeRiskText($keyword))) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeRiskText(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = str_replace(['-', '_', '/', '\\', ',', '.'], ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return $normalized;
    }

    protected function bandedRisk(float $value, array $bands, int $fallbackRisk): int
    {
        foreach ($bands as $band) {
            if ($value <= $band['max']) {
                return (int) $band['risk'];
            }
        }

        return $fallbackRisk;
    }

    protected function clamp(float $score): float
    {
        return max(0, min(100, $score));
    }
}
