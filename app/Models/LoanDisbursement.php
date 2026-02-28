<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanDisbursement extends Model
{
    use HasFactory;

    protected $fillable = [
        'deal_id',
        'loan_id',
        'first_disbursement_date',
        'full_disbursement_date',
        'spa_completion_date',
        'client_notification_date',
    ];

    protected $casts = [
        'first_disbursement_date' => 'date',
        'full_disbursement_date' => 'date',
        'spa_completion_date' => 'date',
        'client_notification_date' => 'date',
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
