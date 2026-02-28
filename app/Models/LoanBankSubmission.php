<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanBankSubmission extends Model
{
    use HasFactory;

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
    ];

    protected $casts = [
        'submission_date' => 'date',
        'expected_approval_date' => 'date',
        'document_completeness_score' => 'integer',
        'file_completeness_percentage' => 'integer',
    ];

    public function deal()
    {
        return $this->belongsTo(Deal::class, 'deal_id');
    }

    public function approvalAnalysis()
    {
        return $this->hasOne(LoanApprovalAnalysis::class, 'loan_id', 'loan_id');
    }

    public function disbursement()
    {
        return $this->hasOne(LoanDisbursement::class, 'loan_id', 'loan_id');
    }
}
