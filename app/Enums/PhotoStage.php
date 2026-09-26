<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Momento del reportaje fotográfico.
 */
enum PhotoStage: string
{
    use HasLabel;

    case Before = 'before';
    case Progress = 'progress';
    case After = 'after';
    case Incident = 'incident';
    case Detail = 'detail';

    public function label(): string
    {
        return match ($this) {
            self::Before => 'Antes',
            self::Progress => 'Avance',
            self::After => 'Después',
            self::Incident => 'Incidencia',
            self::Detail => 'Detalle',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Before => 'gray',
            self::Progress => 'blue',
            self::After => 'green',
            self::Incident => 'red',
            self::Detail => 'teal',
        };
    }
}
