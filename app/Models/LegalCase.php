<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'deal_id',
        'status',
        'lawyer_firm',
        'spa_date',
        'loan_agreement_date',
        'completion_date',
        'stamp_duty',
    ];

    protected $casts = [
        'spa_date' => 'date',
        'loan_agreement_date' => 'date',
        'completion_date' => 'date',
        'stamp_duty' => 'boolean',
    ];

    public function deal()
    {
        return $this->belongsTo(Deal::class, 'deal_id');
    }
}
