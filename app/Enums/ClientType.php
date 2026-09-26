<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Naturaleza jurídica del cliente.
 */
enum ClientType: string
{
    use HasLabel;

    case Individual = 'individual';
    case Company = 'company';
    case Community = 'community';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Particular',
            self::Company => 'Empresa',
            self::Community => 'Comunidad de propietarios',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Individual => 'blue',
            self::Company => 'indigo',
            self::Community => 'purple',
        };
    }
}
