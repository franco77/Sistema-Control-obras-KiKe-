<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\Provider;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Coherencia del kit de UI.
 *
 * Los componentes de formulario llevan `w-full` por defecto, pero
 * ComponentAttributeBag::class() concatena sin resolver conflictos: al pasar
 * `class="w-44"` el elemento salía con `w-full w-44` y, como Tailwind emite
 * `.w-full` después de los anchos fijos, ganaba `w-full`. El campo ocupaba
 * todo el ancho ignorando lo indicado, sin error de ningún tipo.
 *
 * Afectaba a 18 llamadas repartidas por 12 pantallas: todas las barras de
 * filtros y el formulario de oficios.
 */
class UiKitTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------- nivel de componente
    public static function formComponents(): array
    {
        return [
            'input' => ['<x-input class="w-44" />'],
            'select' => ['<x-select class="w-44"><option>a</option></x-select>'],
            'textarea' => ['<x-textarea class="w-44" />'],
        ];
    }

    /** @dataProvider formComponents */
    public function test_el_ancho_indicado_por_el_llamante_manda(string $markup): void
    {
        $html = Blade::render($markup);

        $this->assertStringContainsString('w-44', $html);
        $this->assertStringNotContainsString(
            'w-full',
            $html,
            'Si el llamante indica un ancho, el componente no debe añadir w-full: Tailwind lo haría ganar.'
        );
    }

    public function test_sin_ancho_indicado_se_mantiene_el_w_full_por_defecto(): void
    {
        foreach (['<x-input />', '<x-select><option>a</option></x-select>', '<x-textarea />'] as $markup) {
            $this->assertStringContainsString('w-full', Blade::render($markup), $markup);
        }
    }

    public function test_flex_1_tambien_cuenta_como_ancho_propio(): void
    {
        $html = Blade::render('<x-input class="min-w-0 flex-1" />');

        $this->assertStringContainsString('flex-1', $html);
        $this->assertStringNotContainsString('w-full', $html);
    }

    // ------------------------------------------------------ nivel de página
    /**
     * Busca en el HTML elementos cuyo atributo class declare el ancho dos
     * veces de forma contradictoria.
     *
     * @return array<int, string> las listas de clases en conflicto
     */
    private function conflictosDeAncho(string $html): array
    {
        // (?<![:\w-]) descarta :class y x-bind:class de Alpine, cuyo valor es
        // una expresión JavaScript y no una lista de clases.
        preg_match_all('/(?<![:\w-])class="([^"]*)"/', $html, $matches);

        $conflictos = [];

        foreach ($matches[1] as $classList) {
            // Restos de expresiones Alpine: no son listas de clases.
            if (Str::contains($classList, ['{', '}', '&&', '?', "'"])) {
                continue;
            }

            $anchos = collect(preg_split('/\s+/', trim($classList)) ?: [])
                ->filter()
                // Solo las variantes base: `sm:w-44` y `w-full` no compiten.
                ->reject(fn (string $class) => Str::contains($class, ':'))
                ->filter(fn (string $class) => Str::startsWith($class, 'w-'))
                ->unique()
                ->values();

            if ($anchos->count() > 1) {
                $conflictos[] = $anchos->implode(' + ');
            }
        }

        return array_unique($conflictos);
    }

    public static function paginasConFiltros(): array
    {
        return [
            'clientes' => ['/panel/clientes'],
            'proveedores' => ['/panel/proveedores'],
            'presupuestos' => ['/panel/presupuestos'],
            'obras' => ['/panel/obras'],
            'agenda' => ['/panel/agenda'],
            'catálogo' => ['/panel/catalogo'],
            'documentos' => ['/panel/documentos'],
            'mensajes' => ['/panel/mensajes'],
            'configuración' => ['/panel/configuracion'],
            'obra · incidencias' => ['/panel/obras/{project}?tab=incidencias'],
            'obra · fotos' => ['/panel/obras/{project}?tab=fotos'],
            'ficha obra' => ['/panel/obras/{project}'],
            'hilo de mensajes' => ['/panel/mensajes/{conversation}'],
        ];
    }

    /** @dataProvider paginasConFiltros */
    public function test_ninguna_pantalla_declara_anchos_en_conflicto(string $path): void
    {
        $this->seedBaseline();
        $this->seed(CatalogSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $project = Project::first();

        Conversation::create([
            'client_id' => $project->client_id,
            'project_id' => $project->id,
            'subject' => 'Consulta',
            'last_message_at' => now(),
        ]);

        $path = strtr($path, [
            '{project}' => (string) $project->id,
            '{conversation}' => (string) Conversation::value('id'),
            '{client}' => (string) Client::value('id'),
            '{provider}' => (string) Provider::value('id'),
        ]);

        $response = $this->actingAs($this->admin())->get($path);

        $response->assertOk();

        $conflictos = $this->conflictosDeAncho($response->getContent());

        $this->assertSame(
            [],
            $conflictos,
            "En {$path} hay elementos con anchos contradictorios: ".implode(' | ', $conflictos)
            .'. Tailwind aplicaría el que emita más tarde, no el que se pretendía.'
        );
    }
}
