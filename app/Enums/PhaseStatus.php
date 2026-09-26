<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Estados de una fase de obra.
 */
enum PhaseStatus: string
{
    use HasLabel;

    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Blocked = 'blocked';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::InProgress => 'En curso',
            self::Blocked => 'Bloqueada',
            self::Done => 'Completada',
            self::Cancelled => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::InProgress => 'indigo',
            self::Blocked => 'red',
            self::Done => 'green',
            self::Cancelled => 'gray',
        };
    }
}
