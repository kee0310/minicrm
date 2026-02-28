<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanApprovalAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'deal_id',
        'loan_id',
        'approved_bank',
        'applied_amount',
        'approved_amount',
        'interest_rate',
        'lock_in_period',
        'mrta_mlta',
        'special_conditions',
        'approval_deviation_percentage',
    ];

    protected $casts = [
        'applied_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'approval_deviation_percentage' => 'decimal:2',
    ];

    public function deal()
    {
        return $this->belongsTo(Deal::class, 'deal_id');
    }

    public function bankSubmission()
    {
        return $this->belongsTo(LoanBankSubmission::class, 'loan_id', 'loan_id');
    }
}
