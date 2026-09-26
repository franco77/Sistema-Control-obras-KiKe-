<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Estados de la obra.
 */
enum ProjectStatus: string
{
    use HasLabel;

    case Draft = 'draft';
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Paused = 'paused';
    case Finished = 'finished';
    case Warranty = 'warranty';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Preparación',
            self::Planned => 'Planificada',
            self::InProgress => 'En ejecución',
            self::Paused => 'Parada',
            self::Finished => 'Finalizada',
            self::Warranty => 'En garantía',
            self::Cancelled => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Planned => 'blue',
            self::InProgress => 'indigo',
            self::Paused => 'amber',
            self::Finished => 'green',
            self::Warranty => 'teal',
            self::Cancelled => 'red',
        };
    }
}
