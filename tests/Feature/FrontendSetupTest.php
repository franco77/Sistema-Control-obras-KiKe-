<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Guardia de la configuración del front-end.
 *
 * Livewire 3 empaqueta y arranca su propio Alpine. Si el bundle de la
 * aplicación arranca otro, hay dos instancias de Alpine: la primera reclama
 * el DOM y las directivas `wire:` nunca se enlazan, así que los botones
 * dejan de responder sin ningún error en pantalla ni en los logs.
 *
 * Es un fallo silencioso y caro de diagnosticar, y la tentación de volver a
 * añadir `import Alpine from 'alpinejs'` es alta (lo trae Breeze por defecto).
 * De ahí esta prueba.
 */
class FrontendSetupTest extends TestCase
{
    public function test_la_aplicacion_no_arranca_un_segundo_alpine(): void
    {
        $appJs = file_get_contents(resource_path('js/app.js'));

        $this->assertStringNotContainsString(
            'Alpine.start()',
            $appJs,
            'resources/js/app.js no debe arrancar Alpine: lo hace Livewire y dos instancias rompen las directivas wire:.'
        );

        $this->assertStringNotContainsString(
            "from 'alpinejs'",
            $appJs,
            'No importes Alpine en el bundle: usa el que expone Livewire en window.Alpine.'
        );
    }

    public function test_los_layouts_cargan_los_scripts_de_livewire(): void
    {
        $layouts = [
            resource_path('views/components/layouts/admin.blade.php'),
            resource_path('views/components/layouts/portal.blade.php'),
            resource_path('views/layouts/app.blade.php'),
        ];

        foreach ($layouts as $layout) {
            $this->assertStringContainsString(
                '@livewireScripts',
                file_get_contents($layout),
                basename($layout).' debe incluir @livewireScripts (es quien aporta Alpine).'
            );
        }
    }
}
