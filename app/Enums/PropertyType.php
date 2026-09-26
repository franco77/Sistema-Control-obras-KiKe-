<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Tipología del inmueble sobre el que se interviene.
 */
enum PropertyType: string
{
    use HasLabel;

    case Apartment = 'apartment';
    case House = 'house';
    case Penthouse = 'penthouse';
    case Commercial = 'commercial';
    case Office = 'office';
    case Warehouse = 'warehouse';
    case Building = 'building';
    case CommonArea = 'common_area';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Apartment => 'Piso',
            self::House => 'Casa / Chalet',
            self::Penthouse => 'Ático',
            self::Commercial => 'Local comercial',
            self::Office => 'Oficina',
            self::Warehouse => 'Nave industrial',
            self::Building => 'Edificio completo',
            self::CommonArea => 'Zona común',
            self::Other => 'Otro',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Apartment => 'blue',
            self::House => 'green',
            self::Penthouse => 'teal',
            self::Commercial => 'amber',
            self::Office => 'indigo',
            self::Warehouse => 'gray',
            self::Building => 'purple',
            self::CommonArea => 'pink',
            self::Other => 'gray',
        };
    }
}
