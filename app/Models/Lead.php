<?php

namespace App\Models;

use App\Enums\LeadStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'age',
        'ic_passport',
        'occupation',
        'company',
        'working_years',
        'monthly_income',
        'fixed_income',
    ];

    protected $casts = [
        'status' => LeadStatusEnum::class,
        'age' => 'integer',
        'working_years' => 'integer',
        'monthly_income' => 'decimal:2',
        'fixed_income' => 'decimal:2',
    ];

    public function salesperson()
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->hasRole(\App\Enums\RoleEnum::ADMIN->value)) {
            return $query;
        }

        if ($user->hasRole(\App\Enums\RoleEnum::LEADER->value)) {
            return $query->where(function (Builder $roleQuery) use ($user) {
                $roleQuery->where('salesperson_id', $user->id)
                    ->orWhere('leader_id', $user->id);
            });
        }

        return $query->where('salesperson_id', $user->id);
    }

    // Client profiles are stored directly on leads now.
}
