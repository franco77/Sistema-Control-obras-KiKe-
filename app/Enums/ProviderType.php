<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Forma en la que factura el proveedor.
 */
enum ProviderType: string
{
    use HasLabel;

    case Freelancer = 'freelancer';
    case Company = 'company';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Freelancer => 'Autónomo',
            self::Company => 'Empresa',
            self::Employee => 'Personal propio',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Freelancer => 'blue',
            self::Company => 'indigo',
            self::Employee => 'teal',
        };
    }
}
