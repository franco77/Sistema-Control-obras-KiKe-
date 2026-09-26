<?php

declare(strict_types=1);

use App\Models\Setting;

if (! function_exists('setting')) {
    /** Lee un valor de configuración de la empresa (cacheado). */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('money')) {
    /** Formatea un importe en euros con separadores españoles. */
    function money(float|int|string|null $amount, bool $withSymbol = true): string
    {
        $formatted = number_format((float) $amount, 2, ',', '.');

        return $withSymbol ? $formatted.' €' : $formatted;
    }
}

if (! function_exists('percent')) {
    function percent(float|int|string|null $value, int $decimals = 1): string
    {
        return number_format((float) $value, $decimals, ',', '.').' %';
    }
}