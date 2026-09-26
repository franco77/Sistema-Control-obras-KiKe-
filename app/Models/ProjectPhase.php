<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PhaseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Fase de obra (demolición, albañilería, fontanería…). Contiene tareas y
 * aporta un peso relativo al cálculo del avance global de la obra.
 */
class ProjectPhase extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'trade_id', 'name', 'description', 'status', 'position', 'weight',
        'progress', 'planned_start', 'planned_end', 'actual_start', 'actual_end',
        'budget_amount', 'visible_to_client',
    ];

    protected $attributes = [
        'status' => 'pending',
        'position' => 0,
        'weight' => 1,
        'progress' => 0,
        'budget_amount' => 0,
        'visible_to_client' => true,
    ];
    protected function casts(): array
    {
        return [
            'status' => PhaseStatus::class,
            'planned_start' => 'date',
            'planned_end' => 'date',
            'actual_start' => 'date',
            'actual_end' => 'date',
            'budget_amount' => 'decimal:2',
            'visible_to_client' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class)->orderBy('position');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ProjectPhoto::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(ProjectIncident::class);
    }

    public function scopeVisibleToClient($query)
    {
        return $query->where('visible_to_client', true);
    }
}