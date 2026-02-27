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
}
