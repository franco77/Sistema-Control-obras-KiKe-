<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Disponibilidad operativa del proveedor.
 */
enum ProviderStatus: string
{
    use HasLabel;

    case Active = 'active';
    case PendingDocs = 'pending_docs';
    case Inactive = 'inactive';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::PendingDocs => 'Pendiente documentación',
            self::Inactive => 'Inactivo',
            self::Blocked => 'Bloqueado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::PendingDocs => 'amber',
            self::Inactive => 'gray',
            self::Blocked => 'red',
        };
    }
}
