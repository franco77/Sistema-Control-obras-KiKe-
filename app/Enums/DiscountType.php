<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Modo de aplicar el descuento global del presupuesto.
 */
enum DiscountType: string
{
    use HasLabel;

    case None = 'none';
    case Percent = 'percent';
    case Amount = 'amount';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Sin descuento',
            self::Percent => 'Porcentaje',
            self::Amount => 'Importe fijo',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::None => 'gray',
            self::Percent => 'blue',
            self::Amount => 'indigo',
        };
    }
}
