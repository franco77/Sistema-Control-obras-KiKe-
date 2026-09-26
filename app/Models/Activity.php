<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Traza de auditoría del sistema (quién hizo qué, cuándo y desde dónde).
 * Es la fuente del timeline que se muestra en presupuestos y obras.
 */
class Activity extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'subject_type', 'subject_id', 'causer_type', 'causer_id',
        'event', 'description', 'properties', 'ip', 'user_agent', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    /**
     * Quién provocó el evento. Sin causer es una acción del portal (cliente
     * o proveedor), que actúa sin usuario autenticado.
     */
    public function getCauserNameAttribute(): string
    {
        if ($this->causer_id === null) {
            return 'Cliente / Portal';
        }

        return $this->loadMissing('causer')->causer?->name ?? 'Usuario eliminado';
    }
}