<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientFinancialCondition extends Model
{
    use HasFactory;

    protected $table = 'client_financial';

    protected $fillable = [
        'client_id',
        'existing_loans',
        'monthly_commitments',
        'credit_card_limits',
        'credit_card_utilization',
        'ccris',
        'ctos',
        'risk_grade',
    ];

    protected $casts = [
        'existing_loans' => 'decimal:2',
        'monthly_commitments' => 'decimal:2',
        'credit_card_limits' => 'decimal:2',
        'credit_card_utilization' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function calculateRiskGrade(Client $client): ?string
    {
        $income = (float) ($client->monthly_income ?? 0);
        $commitments = (float) ($this->monthly_commitments ?? 0);
        $utilization = (float) ($this->credit_card_utilization ?? 0);

        $debtRatio = $income > 0 ? ($commitments / $income) : null;
        $ccris = strtolower((string) $this->ccris);
        $ctos = strtolower((string) $this->ctos);

        $negativeKeywords = ['late', 'default', 'delinquent', 'overdue', 'legal', 'bankrupt', 'bad'];
        $hasNegativeRecord = collect($negativeKeywords)->contains(function ($keyword) use ($ccris, $ctos) {
            return str_contains($ccris, $keyword) || str_contains($ctos, $keyword);
        });

        if ($hasNegativeRecord || $utilization > 70 || (!is_null($debtRatio) && $debtRatio > 0.7)) {
            return 'C';
        }

        if ($utilization > 40 || (!is_null($debtRatio) && $debtRatio > 0.4)) {
            return 'B';
        }

        if ($income > 0 || $utilization > 0 || $ccris !== '' || $ctos !== '') {
            return 'A';
        }

        return null;
    }
}
