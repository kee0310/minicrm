<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'deal_id',
        'paid',
        'payment_status',
    ];

    protected $casts = [
        'paid' => 'decimal:2',
    ];

    public function deal()
    {
        return $this->belongsTo(Deal::class, 'deal_id');
    }
}

