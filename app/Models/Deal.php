<?php

namespace App\Models;

use App\Enums\PipelineEnum;
use App\Enums\RoleEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Deal extends Model
{
    use HasFactory;

    private const PIPELINE_DATE_COLUMN_MAP = [
        PipelineEnum::LEAD->value => 'lead_date',
        PipelineEnum::VIEWING->value => 'viewing_date',
        PipelineEnum::BOOKING->value => 'booking_date',
        PipelineEnum::SPA_SIGNED->value => 'spa_signed_date',
        PipelineEnum::LOAN_SUBMITTED->value => 'loan_submitted_date',
        PipelineEnum::LOAN_APPROVED->value => 'loan_approved_date',
        PipelineEnum::LEGAL_PROCESSING->value => 'legal_processing_date',
        PipelineEnum::COMPLETED->value => 'completed_date',
        PipelineEnum::COMMISSION_PAID->value => 'commission_paid_date',
    ];

    protected $fillable = [
        'deal_id',
        'lead_id',
        'project_name',
        'developer',
        'unit_number',
        'selling_price',
        'commission_percentage',
        'commission_amount',
        'salesperson_id',
        'leader_id',
        'loan_officer_id',
        'legal_officer_id',
        'booking_fee',
        'deal_closing_date',
        'pipeline',
    ];

    protected $casts = [
        'selling_price' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'booking_fee' => 'decimal:2',
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

            $deal->syncPipelineStage(
                $deal->pipeline instanceof PipelineEnum ? $deal->pipeline : (string) ($deal->pipeline ?? PipelineEnum::LEAD->value),
                $deal->created_at
            );
        });

        static::saved(function (Deal $deal) {
            $deal->ensureCommissionRecordForCompletion();
        });
    }

    public function client()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function salesperson()
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function loanOfficer()
    {
        return $this->belongsTo(User::class, 'loan_officer_id');
    }

    public function legalOfficer()
    {
        return $this->belongsTo(User::class, 'legal_officer_id');
    }

    public function preQualification()
    {
        return $this->hasOne(LoanPreQualification::class, 'deal_id');
    }

    public function bankSubmissions()
    {
        return $this->hasMany(LoanBankSubmission::class, 'deal_id');
    }

    public function legalCase()
    {
        return $this->hasOne(LegalCase::class, 'deal_id');
    }

    public function commission()
    {
        return $this->hasOne(Commission::class, 'deal_id');
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole(RoleEnum::ADMIN->value)) {
            return $query;
        }

        if ($user->hasRole(RoleEnum::LOAN_OFFICER->value)) {
            return $query->where('deals.loan_officer_id', $user->id);
        }

        if ($user->hasRole(RoleEnum::SALESPERSON->value)) {
            return $query->where('deals.salesperson_id', $user->id);
        }

        if ($user->hasRole(RoleEnum::LEADER->value)) {
            return $query->where('deals.leader_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function syncPipelineStage(string|PipelineEnum $stage, ?CarbonInterface $at = null): void
    {
        $stageValue = $stage instanceof PipelineEnum ? $stage->value : $stage;
        $stageEnum = PipelineEnum::tryFrom((string) $stageValue) ?? PipelineEnum::LEAD;
        $timestamp = $at?->copy() ?? now();

        $this->ensurePipelineRow($timestamp);

        if (($this->pipeline?->value ?? (string) $this->pipeline) !== $stageEnum->value) {
            $this->forceFill(['pipeline' => $stageEnum->value])->saveQuietly();
        }

        $this->ensureCommissionRecordForCompletion($stageEnum);

        $column = self::PIPELINE_DATE_COLUMN_MAP[$stageEnum->value] ?? null;
        if (! $column) {
            return;
        }

        $row = DB::table('deal_pipelines')
            ->where('deal_id', $this->id)
            ->select([$column])
            ->first();

        if (empty($row?->{$column})) {
            DB::table('deal_pipelines')
                ->where('deal_id', $this->id)
                ->update([
                    $column => $timestamp->toDateTimeString(),
                    'updated_at' => now(),
                ]);
        }
    }

    protected function ensurePipelineRow(CarbonInterface $reference): void
    {
        $exists = DB::table('deal_pipelines')
            ->where('deal_id', $this->id)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('deal_pipelines')->insert([
            'deal_id' => $this->id,
            'lead_date' => $reference->toDateTimeString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function ensureCommissionRecordForCompletion(?PipelineEnum $stageEnum = null): void
    {
        $currentStage = $stageEnum?->value ?? ($this->pipeline?->value ?? (string) $this->pipeline);
        if (! in_array($currentStage, [PipelineEnum::COMPLETED->value, PipelineEnum::COMMISSION_PAID->value], true)) {
            return;
        }

        $this->commission()->firstOrCreate(
            ['deal_id' => $this->id],
            [
                'paid' => 0,
                'payment_status' => 'Unpaid',
            ]
        );
    }
}
