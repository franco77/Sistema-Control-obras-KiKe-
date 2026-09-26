<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Origen de un mensaje del hilo de conversación.
 */
enum AuthorType: string
{
    use HasLabel;

    case Client = 'client';
    case Staff = 'staff';
    case Provider = 'provider';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Cliente',
            self::Staff => 'Equipo',
            self::Provider => 'Proveedor',
            self::System => 'Sistema',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Client => 'blue',
            self::Staff => 'indigo',
            self::Provider => 'teal',
            self::System => 'gray',
        };
    }
}
