<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'leader_id',
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'leader_id' => 'integer',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Deals where user is salesperson
     */
    public function salesLeads()
    {
        return $this->hasMany(\App\Models\Lead::class, 'salesperson_id');
    }

    /**
     * Deals where user is salesperson
     */
    public function salesDeals()
    {
        return $this->hasMany(\App\Models\Deal::class, 'salesperson_id');
    }

    /**
     * Deals where user is leader
     */
    public function leaderDeals()
    {
        return $this->hasMany(\App\Models\Deal::class, 'leader_id');
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function salespersons()
    {
        return $this->hasMany(User::class, 'leader_id');
    }
}
