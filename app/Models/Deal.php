<?php

namespace App\Models;

use App\Enums\PipelineEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    use HasFactory;

    protected $fillable = [
        'deal_id',
        'client_id',
        'project_name',
        'developer',
        'unit_number',
        'selling_price',
        'commission_percentage',
        'commission_amount',
        'salesperson_id',
        'leader_id',
        'booking_fee',
        'spa_date',
        'deal_closing_date',
        'pipeline',
    ];

    protected $casts = [
        'selling_price' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'booking_fee' => 'decimal:2',
        'spa_date' => 'date',
        'deal_closing_date' => 'date',
        'pipeline' => PipelineEnum::class,
    ];

    protected static function booted()
    {
        static::creating(function (Deal $deal) {
            // commission amount calculated if both values present
            $deal->commission_amount = ($deal->selling_price ?? 0) * ($deal->commission_percentage ?? 0) / 100;
        });

        static::updating(function (Deal $deal) {
            $deal->commission_amount = ($deal->selling_price ?? 0) * ($deal->commission_percentage ?? 0) / 100;
        });

        static::created(function (Deal $deal) {
            if (empty($deal->deal_id)) {
                $deal->deal_id = sprintf('DL-%06d', $deal->id);
                $deal->saveQuietly();
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function salesperson()
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function preQualification()
    {
        return $this->hasOne(LoanPreQualification::class, 'deal_id');
    }

    public function bankSubmissions()
    {
        return $this->hasMany(LoanBankSubmission::class, 'deal_id');
    }
}
