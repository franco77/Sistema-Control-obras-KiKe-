<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Tipos de evento del calendario.
 */
enum EventType: string
{
    use HasLabel;

    case Visit = 'visit';
    case Measurement = 'measurement';
    case Milestone = 'milestone';
    case Task = 'task';
    case Meeting = 'meeting';
    case Delivery = 'delivery';
    case Inspection = 'inspection';
    case Handover = 'handover';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Visit => 'Visita',
            self::Measurement => 'Toma de medidas',
            self::Milestone => 'Hito de obra',
            self::Task => 'Tarea programada',
            self::Meeting => 'Reunión',
            self::Delivery => 'Entrega de material',
            self::Inspection => 'Inspección / Revisión',
            self::Handover => 'Entrega de obra',
            self::Other => 'Otro',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Visit => 'blue',
            self::Measurement => 'teal',
            self::Milestone => 'purple',
            self::Task => 'indigo',
            self::Meeting => 'amber',
            self::Delivery => 'orange',
            self::Inspection => 'pink',
            self::Handover => 'green',
            self::Other => 'gray',
        };
    }
}
