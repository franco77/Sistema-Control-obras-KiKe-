<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Estados del presupuesto. Las transiciones válidas viven en QuoteStateMachine.
 */
enum QuoteStatus: string
{
    use HasLabel;

    case Draft = 'draft';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Superseded = 'superseded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Sent => 'Enviado',
            self::Viewed => 'Visto por el cliente',
            self::Approved => 'Aprobado',
            self::Rejected => 'Rechazado',
            self::Expired => 'Caducado',
            self::Superseded => 'Sustituido por nueva versión',
            self::Cancelled => 'Anulado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sent => 'blue',
            self::Viewed => 'indigo',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Expired => 'amber',
            self::Superseded => 'purple',
            self::Cancelled => 'gray',
        };
    }
}
