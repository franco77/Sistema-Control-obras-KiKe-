<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Ciclo de vida de una incidencia.
 */
enum IncidentStatus: string
{
    use HasLabel;

    case Open = 'open';
    case InProgress = 'in_progress';
    case WaitingClient = 'waiting_client';
    case WaitingProvider = 'waiting_provider';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Abierta',
            self::InProgress => 'En resolución',
            self::WaitingClient => 'Esperando al cliente',
            self::WaitingProvider => 'Esperando al proveedor',
            self::Resolved => 'Resuelta',
            self::Closed => 'Cerrada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'red',
            self::InProgress => 'amber',
            self::WaitingClient => 'purple',
            self::WaitingProvider => 'indigo',
            self::Resolved => 'green',
            self::Closed => 'gray',
        };
    }
}
