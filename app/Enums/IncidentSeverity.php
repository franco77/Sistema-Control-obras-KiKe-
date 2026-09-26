<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Gravedad de la incidencia.
 */
enum IncidentSeverity: string
{
    use HasLabel;

    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Leve',
            self::Medium => 'Moderada',
            self::High => 'Grave',
            self::Critical => 'Crítica',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'gray',
            self::Medium => 'amber',
            self::High => 'orange',
            self::Critical => 'red',
        };
    }
}
