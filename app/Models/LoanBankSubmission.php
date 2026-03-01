<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanBankSubmission extends Model
{
    use HasFactory;

    protected $table = 'loans';

    protected $primaryKey = 'loan_id';

    protected $fillable = [
        'deal_id',
        'bank_name',
        'banker_contact',
        'submission_date',
        'document_completeness_score',
        'approval_status',
        'expected_approval_date',
        'file_completeness_percentage',
        'approved_bank',
        'applied_amount',
        'approved_amount',
        'interest_rate',
        'lock_in_period',
        'mrta_mlta',
        'special_conditions',
        'approval_deviation_percentage',
        'first_disbursement_date',
        'full_disbursement_date',
        'spa_completion_date',
        'client_notification_date',
    ];

    protected $casts = [
        'submission_date' => 'date',
        'expected_approval_date' => 'date',
        'document_completeness_score' => 'integer',
        'file_completeness_percentage' => 'integer',
        'applied_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'approval_deviation_percentage' => 'decimal:2',
        'first_disbursement_date' => 'date',
        'full_disbursement_date' => 'date',
        'spa_completion_date' => 'date',
        'client_notification_date' => 'date',
    ];

    public function deal()
    {
        return $this->belongsTo(Deal::class, 'deal_id');
    }
}
