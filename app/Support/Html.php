<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;

/**
 * Utilidades para los componentes Blade del kit de UI.
 */
class Html
{
    /**
     * ¿Trae el llamante una clase que ya define el ancho del elemento?
     *
     * Los campos de formulario llevan `w-full` por defecto, pero
     * ComponentAttributeBag::class() solo concatena: no resuelve conflictos.
     * Al pasar `class="w-44"` el elemento acababa con `w-full w-44`, y como
     * Tailwind emite `.w-full` después que los anchos fijos, ganaba `w-full`
     * y el campo se iba a todo el ancho ignorando lo indicado.
     *
     * Con esto, el `w-full` por defecto se omite cuando el llamante ya ha
     * decidido el ancho.
     */
    public static function hasWidth(ComponentAttributeBag $attributes): bool
    {
        $classes = preg_split('/\s+/', trim((string) $attributes->get('class', ''))) ?: [];

        foreach (array_filter($classes) as $class) {
            // Descartar prefijos de variante: sm:, md:, hover:, dark:…
            $utility = Str::afterLast($class, ':');

            $definesWidth = Str::startsWith($utility, ['w-', 'basis-'])
                || in_array($utility, ['flex-1', 'flex-auto', 'grow'], true);

            if ($definesWidth) {
                return true;
            }
        }

        return false;
    }

    /**
     * ¿Define el llamante el tamaño (ancho o alto)?
     *
     * Mismo problema que hasWidth(), aplicado a los iconos: su tamaño por
     * defecto es `h-5 w-5` y, al pedirle `h-4 w-4`, Tailwind emite `.w-5`
     * después de `.w-4`, así que el icono salía a 20 px en lugar de 16.
     */
    public static function hasSize(ComponentAttributeBag $attributes): bool
    {
        $classes = preg_split('/\s+/', trim((string) $attributes->get('class', ''))) ?: [];

        foreach (array_filter($classes) as $class) {
            $utility = Str::afterLast($class, ':');

            if (Str::startsWith($utility, ['w-', 'h-', 'size-'])) {
                return true;
            }
        }

        return false;
    }
}
