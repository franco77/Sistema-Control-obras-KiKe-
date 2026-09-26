<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Unidades de medición admitidas en las partidas.
 */
enum MeasurementUnit: string
{
    use HasLabel;

    case Unit = 'ud';
    case SquareMeter = 'm2';
    case LinearMeter = 'ml';
    case CubicMeter = 'm3';
    case Kilogram = 'kg';
    case Hour = 'h';
    case Day = 'day';
    case LumpSum = 'pa';

    public function label(): string
    {
        return match ($this) {
            self::Unit => 'ud',
            self::SquareMeter => 'm²',
            self::LinearMeter => 'ml',
            self::CubicMeter => 'm³',
            self::Kilogram => 'kg',
            self::Hour => 'h',
            self::Day => 'día',
            self::LumpSum => 'P.A.',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unit => 'gray',
            self::SquareMeter => 'blue',
            self::LinearMeter => 'teal',
            self::CubicMeter => 'indigo',
            self::Kilogram => 'gray',
            self::Hour => 'amber',
            self::Day => 'amber',
            self::LumpSum => 'purple',
        };
    }
}
