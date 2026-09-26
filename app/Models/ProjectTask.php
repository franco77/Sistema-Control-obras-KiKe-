<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tarea dentro de una fase. Es la unidad que se asigna a un proveedor
 * y la que mueve el porcentaje de avance de la obra.
 */
class ProjectTask extends Model
{
    use HasFactory;
    use RecordsActivity;

    protected $fillable = [
        'project_id', 'project_phase_id', 'trade_id', 'provider_id', 'assigned_user_id',
        'name', 'description', 'status', 'priority', 'position',
        'planned_start', 'planned_end', 'started_at', 'completed_at',
        'estimated_hours', 'actual_hours', 'cost_estimated', 'cost_real',
        'visible_to_client', 'requires_client_approval', 'notes',
    ];

    protected $attributes = [
        'status' => 'pending',
        'priority' => 'normal',
        'position' => 0,
        'cost_estimated' => 0,
        'cost_real' => 0,
        'visible_to_client' => true,
        'requires_client_approval' => false,
    ];
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'planned_start' => 'date',
            'planned_end' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'estimated_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
            'cost_estimated' => 'decimal:2',
            'cost_real' => 'decimal:2',
            'visible_to_client' => 'boolean',
            'requires_client_approval' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $task): void {
            // Sellos de tiempo coherentes con el estado.
            if ($task->isDirty('status')) {
                if ($task->status === TaskStatus::InProgress && $task->started_at === null) {
                    $task->started_at = now();
                }

                if ($task->status === TaskStatus::Done && $task->completed_at === null) {
                    $task->completed_at = now();
                }

                if ($task->status !== TaskStatus::Done) {
                    $task->completed_at = null;
                }
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

    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(ProjectIncident::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ProjectPhoto::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [TaskStatus::Done, TaskStatus::Cancelled]);
    }

    public function scopeVisibleToClient(Builder $query): Builder
    {
        return $query->where('visible_to_client', true);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()->whereNotNull('planned_end')->whereDate('planned_end', '<', now());
    }

    public function scopeForProvider(Builder $query, int $providerId): Builder
    {
        return $query->where('provider_id', $providerId);
    }

    public function isDone(): bool
    {
        return $this->status === TaskStatus::Done;
    }

    public function isOverdue(): bool
    {
        return $this->planned_end !== null && $this->planned_end->isPast() && ! $this->isDone();
    }
}