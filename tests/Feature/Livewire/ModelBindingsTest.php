<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\Calendar\CalendarBoard;
use App\Livewire\Admin\Catalog\CatalogManager;
use App\Livewire\Admin\Clients\ClientForm;
use App\Livewire\Admin\Clients\ContactManager;
use App\Livewire\Admin\Clients\PropertyManager;
use App\Livewire\Admin\Projects\ExtraManager;
use App\Livewire\Admin\Projects\IncidentManager;
use App\Livewire\Admin\Projects\PhaseManager;
use App\Livewire\Admin\Providers\ProviderForm;
use App\Livewire\Admin\Providers\ProviderShow;
use App\Livewire\Admin\Quotes\QuoteBuilder;
use App\Livewire\Admin\Settings\SettingsPage;
use App\Models\Client;
use App\Models\Project;
use App\Models\Provider;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Comprueba que cada `wire:model` del HTML apunta a una propiedad que existe.
 *
 * Livewire enlaza `wire:model` mediante `x-model` de Alpine, cuyo getter hace
 * `dataGet($wire, "<expresión>")`. Si la expresión no resuelve, el campo se
 * pinta vacío y lo que escribe el usuario va a un hueco que nadie lee: se
 * guarda sin error y al volver no hay nada. Sin errores en el log, sin
 * excepción y sin pista alguna.
 *
 * Es justo lo que ocurría en Ajustes, donde la vista enlazaba «values.0»
 * porque groupBy() había reindexado las claves del esquema.
 */
class ModelBindingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaseline();
        $this->seed(CatalogSeeder::class);
        $this->seed(DemoDataSeeder::class);
        $this->actingAs($this->admin());
    }

    /**
     * Extrae los wire:model del HTML y comprueba que todos resuelven contra
     * el estado del componente.
     */
    private function assertBindingsResolve(Testable $component, string $contexto): void
    {
        preg_match_all('/wire:model(?:\.[\w.]+)?="([^"]+)"/', $component->html(), $matches);

        $expresiones = array_unique($matches[1]);

        $this->assertNotEmpty($expresiones, "No se encontró ningún wire:model en {$contexto}.");

        $rotos = [];

        foreach ($expresiones as $expresion) {
            $base = explode('.', $expresion)[0];

            // Arr::has y no data_get: data_get usa isset(), que da por
            // inexistente una propiedad pública cuyo valor es null —y null es
            // un valor perfectamente válido en el estado del componente.
            $estado = [$base => $this->normalizar($component->get($base))];

            if (! \Illuminate\Support\Arr::has($estado, $expresion)) {
                $rotos[] = $expresion;
            }
        }

        $this->assertSame(
            [],
            $rotos,
            "En {$contexto} hay wire:model que no resuelven en el estado del componente: "
            .implode(', ', $rotos)
            .'. El campo se pintaría vacío y lo que escriba el usuario se perdería.'
        );
    }

    /**
     * Convierte objetos de formulario en arrays para poder comprobar la
     * existencia de cada clave, tal como hace Livewire al serializar el
     * componente hacia el navegador.
     */
    private function normalizar(mixed $valor): mixed
    {
        if (is_object($valor)) {
            $valor = method_exists($valor, 'all') ? $valor->all() : get_object_vars($valor);
        }

        if (is_array($valor)) {
            return array_map(fn ($item) => $this->normalizar($item), $valor);
        }

        return $valor;
    }

    public function test_ajustes(): void
    {
        $this->assertBindingsResolve(Livewire::test(SettingsPage::class), 'Configuración');
    }

    public function test_formulario_de_cliente(): void
    {
        $this->assertBindingsResolve(
            Livewire::test(ClientForm::class, ['client' => Client::first()]),
            'Editar cliente'
        );
    }

    public function test_formulario_de_proveedor(): void
    {
        $this->assertBindingsResolve(
            Livewire::test(ProviderForm::class, ['provider' => Provider::first()]),
            'Editar proveedor'
        );
    }

    public function test_constructor_de_presupuestos(): void
    {
        $this->assertBindingsResolve(Livewire::test(QuoteBuilder::class), 'Constructor de presupuestos');
    }

    public function test_inmuebles_del_cliente(): void
    {
        $component = Livewire::test(PropertyManager::class, ['client' => Client::first()])->call('create');

        $this->assertBindingsResolve($component, 'Inmuebles');
    }

    public function test_contactos_del_cliente(): void
    {
        $component = Livewire::test(ContactManager::class, ['client' => Client::first()])->call('create');

        $this->assertBindingsResolve($component, 'Contactos');
    }

    public function test_fases_y_tareas(): void
    {
        $project = Project::first();

        $conFase = Livewire::test(PhaseManager::class, ['project' => $project])->call('newPhase');
        $this->assertBindingsResolve($conFase, 'Formulario de fase');

        $conTarea = Livewire::test(PhaseManager::class, ['project' => $project])
            ->call('newTask', $project->phases()->value('id'));
        $this->assertBindingsResolve($conTarea, 'Formulario de tarea');
    }

    public function test_incidencias(): void
    {
        $component = Livewire::test(IncidentManager::class, ['project' => Project::first()])->call('newIncident');

        $this->assertBindingsResolve($component, 'Incidencias');
    }

    public function test_extras(): void
    {
        $component = Livewire::test(ExtraManager::class, ['project' => Project::first()])->call('newExtra');

        $this->assertBindingsResolve($component, 'Extras');
    }

    public function test_agenda(): void
    {
        $component = Livewire::test(CalendarBoard::class)->call('newEvent');

        $this->assertBindingsResolve($component, 'Agenda');
    }

    public function test_catalogo(): void
    {
        $component = Livewire::test(CatalogManager::class)->call('create');

        $this->assertBindingsResolve($component, 'Catálogo');
    }

    public function test_disponibilidad_del_proveedor(): void
    {
        $component = Livewire::test(ProviderShow::class, ['provider' => Provider::first()])
            ->set('tab', 'disponibilidad');

        $this->assertBindingsResolve($component, 'Disponibilidad del proveedor');
    }
}
