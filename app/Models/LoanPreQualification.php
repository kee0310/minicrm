<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanPreQualification extends Model
{
    use HasFactory;

    protected $fillable = [
        'deal_id',
        'pre_qualification_date',
        'recommended_banks',
    ];

    protected $casts = [
        'pre_qualification_date' => 'date',
        'recommended_banks' => 'array',
    ];

    public function deal()
    {
        return $this->belongsTo(Deal::class, 'deal_id');
    }
}

