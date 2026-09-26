<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExtraStatus;
use App\Models\Concerns\HasDocuments;
use App\Models\Concerns\HasPortalTokens;
use App\Models\Concerns\HasSequentialCode;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Extra / modificado de obra que el cliente aprueba o rechaza desde el portal.
 * Al aprobarse suma a projects.extras_total y puede ampliar el plazo.
 */
class ProjectExtra extends Model
{
    use HasDocuments;
    use HasFactory;
    use HasPortalTokens;
    use HasSequentialCode;
    use RecordsActivity;

    protected $fillable = [
        'code', 'project_id', 'project_phase_id', 'project_incident_id',
        'title', 'description', 'justification', 'status',
        'amount', 'tax_rate', 'tax_amount', 'total', 'cost_estimated', 'extra_days',
        'sent_at', 'decided_at', 'decision_ip', 'signer_name', 'rejection_reason', 'created_by',
    ];

    protected $attributes = [
        'status' => 'draft',
        'amount' => 0,
        'tax_rate' => 21,
        'tax_amount' => 0,
        'total' => 0,
        'cost_estimated' => 0,
        'extra_days' => 0,
    ];
    protected function casts(): array
    {
        return [
            'status' => ExtraStatus::class,
            'amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'cost_estimated' => 'decimal:2',
            'sent_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public static function codePrefix(): string
    {
        return 'EXT';
    }

    protected static function booted(): void
    {
        static::saving(function (self $extra): void {
            $extra->tax_amount = round((float) $extra->amount * ((float) $extra->tax_rate / 100), 2);
            $extra->total = round((float) $extra->amount + (float) $extra->tax_amount, 2);
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProjectPhase::class, 'project_phase_id');
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(ProjectIncident::class, 'project_incident_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ExtraStatus::Sent);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', ExtraStatus::Approved);
    }

    public function isDecidable(): bool
    {
        return $this->status === ExtraStatus::Sent;
    }

    public function getMarginAmountAttribute(): float
    {
        return round((float) $this->amount - (float) $this->cost_estimated, 2);
    }
}