<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'email',
        'phone',
        'salesperson_id',
        'leader_id',
        'age',
        'ic_passport',
        'occupation',
        'company',
        'monthly_income',
        'status',
        'completeness_rate',
    ];

    protected $casts = [
        'salesperson_id' => 'integer',
        'leader_id' => 'integer',
        'age' => 'integer',
        'monthly_income' => 'decimal:2',
        'completeness_rate' => 'integer',
    ];

    protected static function booted()
    {
        static::created(function (Client $client) {
            if (empty($client->client_id)) {
                $client->client_id = sprintf('CL-%06d', $client->id);
                $client->saveQuietly();
            }
        });
    }

    public function lead()
    {
        return $this->hasOne(Lead::class, 'email', 'email');
    }

    public function deals()
    {
        return $this->hasMany(Deal::class, 'client_id');
    }

    public function salesperson()
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function recalculateCompletenessAndStatus(): void
    {
        $fields = [
            $this->age,
            $this->ic_passport,
            $this->occupation,
            $this->company,
            $this->monthly_income,
        ];

        $total = count($fields);
        $filled = collect($fields)->filter(function ($value) {
            if (is_string($value)) {
                return trim($value) !== '';
            }
            return !is_null($value);
        })->count();

        $rate = $total > 0 ? (int) round(($filled / $total) * 100) : 0;
        $status = 'New';

        if ($rate >= 100) {
            $status = 'Completed';
        } elseif ($rate > 0) {
            $status = $rate . '%';
        }

        $this->forceFill([
            'completeness_rate' => $rate,
            'status' => $status,
        ])->save();
    }

}
