<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Bloques de disponibilidad declarados por el proveedor.
 */
enum AvailabilityType: string
{
    use HasLabel;

    case Available = 'available';
    case Unavailable = 'unavailable';
    case Vacation = 'vacation';
    case Booked = 'booked';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::Unavailable => 'No disponible',
            self::Vacation => 'Vacaciones',
            self::Booked => 'Ocupado en obra',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'green',
            self::Unavailable => 'red',
            self::Vacation => 'amber',
            self::Booked => 'indigo',
        };
    }
}
