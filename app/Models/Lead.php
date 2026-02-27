<?php

namespace App\Models;

use App\Enums\LeadStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Lead extends Model
{
    /** @use HasFactory<\Database\Factories\LeadFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'source',
        'salesperson_id',
        'leader_id',
        'status',
    ];

    protected $casts = [
        'status' => LeadStatusEnum::class,
    ];

    protected static function booted()
    {
        static::saved(function (Lead $lead) {
            $leadStatus = $lead->status instanceof LeadStatusEnum
                ? $lead->status
                : LeadStatusEnum::tryFrom((string) $lead->status);

            if ($leadStatus === LeadStatusEnum::DEAL) {
                $lead->ensureClientProfile();
            }
        });
    }

    public function salesperson()
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    /**
     * Deals associated with this lead
     */
    public function deals()
    {
        return $this->hasMany(Deal::class, 'lead_id');
    }

    public function client()
    {
        return $this->hasOne(Client::class, 'lead_id');
    }

    protected function ensureClientProfile(): void
    {
        $client = $this->client()->firstOrCreate(
            ['lead_id' => $this->id],
            [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
            ]
        );
    }
}
