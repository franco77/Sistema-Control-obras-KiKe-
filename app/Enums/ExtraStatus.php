<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Estados de un extra / modificado de obra.
 */
enum ExtraStatus: string
{
    use HasLabel;

    case Draft = 'draft';
    case Sent = 'sent';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Sent => 'Enviado al cliente',
            self::Approved => 'Aprobado',
            self::Rejected => 'Rechazado',
            self::Cancelled => 'Anulado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sent => 'blue',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Cancelled => 'gray',
        };
    }
}
