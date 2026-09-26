<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Estados del hilo de mensajes del portal.
 */
enum ConversationStatus: string
{
    use HasLabel;

    case Open = 'open';
    case Answered = 'answered';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Abierto',
            self::Answered => 'Respondido',
            self::Closed => 'Cerrado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'amber',
            self::Answered => 'blue',
            self::Closed => 'gray',
        };
    }
}
