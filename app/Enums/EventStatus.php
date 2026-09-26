<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Estados de un evento de calendario.
 */
enum EventStatus: string
{
    use HasLabel;

    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case Done = 'done';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Programado',
            self::Confirmed => 'Confirmado',
            self::Done => 'Realizado',
            self::Cancelled => 'Cancelado',
            self::NoShow => 'No asistió',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Scheduled => 'blue',
            self::Confirmed => 'indigo',
            self::Done => 'green',
            self::Cancelled => 'gray',
            self::NoShow => 'red',
        };
    }
}
