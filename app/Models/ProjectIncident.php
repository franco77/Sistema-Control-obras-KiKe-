<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Models\Concerns\HasDocuments;
use App\Models\Concerns\HasSequentialCode;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Incidencia de obra: desviación, imprevisto o defecto detectado.
 * Puede originar un extra y afectar a coste y plazo.
 */
class ProjectIncident extends Model
{
    use HasDocuments;
    use HasFactory;
    use HasSequentialCode;
    use RecordsActivity;

    protected $fillable = [
        'code', 'project_id', 'project_phase_id', 'project_task_id', 'provider_id',
        'title', 'description', 'severity', 'status',
        'opened_at', 'resolved_at', 'due_date', 'resolution',
        'cost_impact', 'days_impact', 'visible_to_client', 'reported_by_client',
        'reported_by', 'assigned_to',
    ];

    protected $attributes = [
        'severity' => 'medium',
        'status' => 'open',
        'cost_impact' => 0,
        'days_impact' => 0,
        'visible_to_client' => false,
        'reported_by_client' => false,
    ];
    protected function casts(): array
    {
        return [
            'severity' => IncidentSeverity::class,
            'status' => IncidentStatus::class,
            'opened_at' => 'datetime',
            'resolved_at' => 'datetime',
            'due_date' => 'date',
            'cost_impact' => 'decimal:2',
            'visible_to_client' => 'boolean',
            'reported_by_client' => 'boolean',
        ];
    }

    public static function codePrefix(): string
    {
        return 'INC';
    }

    protected static function booted(): void
    {
        static::creating(fn (self $incident) => $incident->opened_at ??= now());

        static::saving(function (self $incident): void {
            if ($incident->isDirty('status')) {
                $incident->resolved_at = $incident->status->is(IncidentStatus::Resolved, IncidentStatus::Closed)
                    ? ($incident->resolved_at ?? now())
                    : null;
            }
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

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ProjectPhoto::class);
    }

    public function extras(): HasMany
    {
        return $this->hasMany(ProjectExtra::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [IncidentStatus::Resolved, IncidentStatus::Closed]);
    }

    public function scopeVisibleToClient(Builder $query): Builder
    {
        return $query->where('visible_to_client', true);
    }

    public function scopeCritical(Builder $query): Builder
    {
        return $query->open()->whereIn('severity', [IncidentSeverity::High, IncidentSeverity::Critical]);
    }
}