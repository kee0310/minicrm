<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Leads extends Model
{
    /** @use HasFactory<\Database\Factories\LeadsFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'source',
        'assigned_to',
        'leader',
        'status',
    ];

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to', 'name');
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader', 'name');
    }

    /**
     * Deals associated with this lead
     */
    public function deals()
    {
        return $this->hasMany(Deal::class, 'lead_id');
    }
}
