<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Estados de una tarea dentro de una fase.
 */
enum TaskStatus: string
{
    use HasLabel;

    case Pending = 'pending';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Blocked = 'blocked';
    case Review = 'review';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Assigned => 'Asignada',
            self::InProgress => 'En curso',
            self::Blocked => 'Bloqueada',
            self::Review => 'En revisión',
            self::Done => 'Completada',
            self::Cancelled => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Assigned => 'blue',
            self::InProgress => 'indigo',
            self::Blocked => 'red',
            self::Review => 'amber',
            self::Done => 'green',
            self::Cancelled => 'gray',
        };
    }
}
