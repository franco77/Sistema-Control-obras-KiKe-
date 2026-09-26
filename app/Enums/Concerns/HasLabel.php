<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

/**
 * Comportamiento común a todos los enums de dominio.
 *
 * Cada enum define label() y color(); este trait añade los helpers de
 * presentación que consumen los componentes Livewire (selects, badges…).
 */
trait HasLabel
{
    /** @return array<string, string> value => label */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return array<int, array{value: string, label: string, color: string}> */
    public static function toArray(): array
    {
        return collect(self::cases())
            ->map(fn (self $case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'color' => $case->color(),
            ])->all();
    }

    public function is(self ...$cases): bool
    {
        return in_array($this, $cases, true);
    }

    public function isNot(self ...$cases): bool
    {
        return ! $this->is(...$cases);
    }
}
