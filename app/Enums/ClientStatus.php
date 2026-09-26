<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Ciclo de vida comercial del cliente.
 */
enum ClientStatus: string
{
    use HasLabel;

    case Lead = 'lead';
    case Active = 'active';
    case Inactive = 'inactive';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Lead => 'Lead',
            self::Active => 'Activo',
            self::Inactive => 'Inactivo',
            self::Blocked => 'Bloqueado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Lead => 'amber',
            self::Active => 'green',
            self::Inactive => 'gray',
            self::Blocked => 'red',
        };
    }
}
