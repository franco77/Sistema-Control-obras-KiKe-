<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AvailabilityType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bloque de disponibilidad del proveedor: vacaciones, no disponible o
 * ocupación generada automáticamente al asignarle tareas de una obra.
 */
class ProviderAvailability extends Model
{
    use HasFactory;

    protected $table = 'provider_availabilities';

    protected $fillable = [
        'provider_id', 'type', 'starts_on', 'ends_on',
        'starts_at', 'ends_at', 'project_id', 'note',
    ];

    protected $attributes = [
        'type' => 'unavailable',
    ];
    protected function casts(): array
    {
        return [
            'type' => AvailabilityType::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeOverlapping(Builder $query, string $from, string $to): Builder
    {
        return $query->where('starts_on', '<=', $to)->where('ends_on', '>=', $from);
    }

    public function scopeBlocking(Builder $query): Builder
    {
        return $query->whereIn('type', [AvailabilityType::Unavailable, AvailabilityType::Vacation]);
    }
}