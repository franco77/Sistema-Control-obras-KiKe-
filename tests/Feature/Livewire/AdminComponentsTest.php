<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\Calendar\CalendarBoard;
use App\Livewire\Admin\Catalog\CatalogManager;
use App\Livewire\Admin\Clients\ClientForm;
use App\Livewire\Admin\Clients\ContactManager;
use App\Livewire\Admin\Clients\PropertyManager;
use App\Livewire\Admin\Projects\PhaseManager;
use App\Livewire\Admin\Quotes\QuoteBuilder;
use App\Livewire\Admin\Settings\SettingsPage;
use App\Models\CalendarEvent;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Project;
use App\Models\Setting;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pruebas de las acciones de los componentes del panel.
 *
 * Los smoke tests por HTTP solo comprobaban que la pantalla pintaba; estas
 * ejercitan los botones (añadir capítulo, guardar ajustes, cargar la ficha…),
 * que es justo donde estaban los fallos.
 */
class AdminComponentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseline();
        $this->seed(CatalogSeeder::class);
        $this->actingAs($this->admin());
    }

    // ------------------------------------------------------------ clientes
    public function test_el_formulario_de_edicion_carga_los_datos_del_cliente(): void
    {
        $client = Client::create([
            'name' => 'Carmen Ruiz',
            'email' => 'carmen@test.local',
            'phone' => '655123456',
            'city' => 'Madrid',
            'tax_id' => '12345678Z',
            'status' => 'active',
        ]);

        Livewire::test(ClientForm::class, ['client' => $client])
            ->assertSet('form.name', 'Carmen Ruiz')
            ->assertSet('form.email', 'carmen@test.local')
            ->assertSet('form.phone', '655123456')
            ->assertSet('form.city', 'Madrid')
            ->assertSet('form.tax_id', '12345678Z')
            ->assertSet('form.status', 'active');
    }

    public function test_el_formulario_de_alta_crea_el_cliente(): void
    {
        Livewire::test(ClientForm::class)
            ->set('form.name', 'Nuevo Cliente')
            ->set('form.email', 'nuevo@test.local')
            ->call('save');

        $this->assertDatabaseHas('clients', ['name' => 'Nuevo Cliente']);
    }

    public function test_editar_actualiza_en_vez_de_duplicar(): void
    {
        $client = Client::create(['name' => 'Antiguo']);

        Livewire::test(ClientForm::class, ['client' => $client])
            ->set('form.name', 'Renombrado')
            ->call('save');

        $this->assertSame(1, Client::count());
        $this->assertSame('Renombrado', $client->fresh()->name);
    }

    public function test_se_pueden_anadir_inmuebles_y_contactos(): void
    {
        $client = Client::create(['name' => 'Carmen']);

        Livewire::test(PropertyManager::class, ['client' => $client])
            ->call('create')
            ->set('form.alias', 'Piso centro')
            ->set('form.address', 'C/ Mayor 1')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('properties', ['alias' => 'Piso centro']);

        Livewire::test(ContactManager::class, ['client' => $client])
            ->call('create')
            ->set('form.name', 'Julián Nieto')
            ->set('form.email', 'julian@test.local')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('client_contacts', ['name' => 'Julián Nieto']);
    }

    // ------------------------------------------------------- presupuestos
    private function builder()
    {
        Client::create(['name' => 'Carmen Ruiz']);

        return Livewire::test(QuoteBuilder::class);
    }

    public function test_el_constructor_crea_un_borrador_con_su_primer_capitulo(): void
    {
        $component = $this->builder();

        $quote = $component->get('quote');

        $this->assertNotNull($quote);
        $this->assertSame(1, $quote->sections()->count());
    }

    public function test_anadir_capitulo(): void
    {
        $component = $this->builder();

        $component->call('addSection');

        $this->assertSame(2, $component->get('quote')->sections()->count());
    }

    public function test_anadir_partida_en_blanco(): void
    {
        $component = $this->builder();
        $sectionId = $component->get('quote')->sections()->first()->id;

        $component->call('addItem', $sectionId);

        $this->assertSame(1, $component->get('quote')->items()->count());
    }

    public function test_anadir_partida_desde_el_catalogo(): void
    {
        $component = $this->builder();
        $sectionId = $component->get('quote')->sections()->first()->id;
        $catalogItem = CatalogItem::first();

        $component->call('openCatalog', $sectionId)
            ->call('addCatalogItem', $catalogItem->id);

        $item = $component->get('quote')->items()->first();

        $this->assertNotNull($item);
        $this->assertSame($catalogItem->name, $item->name);
        $this->assertEquals((float) $catalogItem->unit_price, (float) $item->unit_price);
    }

    public function test_cambiar_cantidad_recalcula_el_total(): void
    {
        $component = $this->builder();
        $sectionId = $component->get('quote')->sections()->first()->id;
        $component->call('addItem', $sectionId);
        $itemId = $component->get('quote')->items()->first()->id;

        $component->set("items.{$itemId}.unit_price", 100)
            ->set("items.{$itemId}.quantity", 3);

        $this->assertEquals(300.00, (float) $component->get('quote')->fresh()->items_total);
    }

    public function test_eliminar_capitulo_y_partida(): void
    {
        $component = $this->builder();
        $sectionId = $component->get('quote')->sections()->first()->id;

        $component->call('addItem', $sectionId);
        $itemId = $component->get('quote')->items()->first()->id;

        $component->call('removeItem', $itemId);
        $this->assertSame(0, $component->get('quote')->items()->count());

        $component->call('removeSection', $sectionId);
        $this->assertSame(0, $component->get('quote')->sections()->count());
    }

    public function test_cambiar_el_titulo_persiste(): void
    {
        $component = $this->builder();

        $component->set('header.title', 'Reforma integral');

        $this->assertSame('Reforma integral', $component->get('quote')->fresh()->title);
    }

    // -------------------------------------------------------------- obras
    public function test_anadir_fase_y_tarea_a_una_obra(): void
    {
        $client = Client::create(['name' => 'Carmen']);
        $project = Project::create(['client_id' => $client->id, 'name' => 'Reforma', 'status' => 'in_progress']);

        $component = Livewire::test(PhaseManager::class, ['project' => $project]);

        $component->call('newPhase')
            ->set('phaseForm.name', 'Fontanería')
            ->set('phaseForm.weight', 10)
            ->call('savePhase')
            ->assertHasNoErrors();

        $phase = $project->phases()->first();
        $this->assertNotNull($phase);

        $component->call('newTask', $phase->id)
            ->set('taskForm.name', 'Cambio de bajante')
            ->call('saveTask')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project_tasks', ['name' => 'Cambio de bajante']);
    }

    // ------------------------------------------------------------- agenda
    public function test_crear_evento_en_la_agenda(): void
    {
        Livewire::test(CalendarBoard::class)
            ->call('newEvent')
            ->set('form.title', 'Visita de obra')
            ->set('form.starts_at', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('form.ends_at', now()->addDay()->addHour()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('calendar_events', ['title' => 'Visita de obra']);
    }

    public function test_editar_evento_carga_sus_datos(): void
    {
        $event = CalendarEvent::create([
            'title' => 'Toma de medidas',
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHour(),
        ]);

        Livewire::test(CalendarBoard::class)
            ->call('edit', $event->id)
            ->assertSet('form.title', 'Toma de medidas');
    }

    // ------------------------------------------------------------ catálogo
    public function test_crear_partida_de_catalogo(): void
    {
        Livewire::test(CatalogManager::class)
            ->call('create')
            ->set('form.name', 'Partida nueva')
            ->set('form.unit_cost', 10)
            ->set('form.unit_price', 20)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('catalog_items', ['name' => 'Partida nueva']);
    }

    // ------------------------------------------------------- configuración
    public function test_guardar_la_configuracion(): void
    {
        Livewire::test(SettingsPage::class)
            ->set('values.company_name', 'Reformas Nuevas SL')
            ->set('values.company_tax_rate', '10')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Reformas Nuevas SL', Setting::get('company.name'));
        $this->assertEquals(10.0, Setting::get('company.tax_rate'));
    }

    public function test_la_configuracion_carga_los_valores_guardados(): void
    {
        Setting::put('company.name', 'Empresa Guardada');

        Livewire::test(SettingsPage::class)
            ->assertSet('values.company_name', 'Empresa Guardada');
    }

    /**
     * Recorre los wire:model REALMENTE renderizados en vez de unos elegidos a
     * mano. El fallo anterior estaba justo ahí: el componente funcionaba, pero
     * la vista enlazaba los campos a «values.0», «values.1»… porque groupBy()
     * reindexa si no se le pasa preserveKeys. Todo lo que escribía el usuario
     * se descartaba al guardar, y los campos salían vacíos al volver.
     */
    public function test_el_formulario_de_ajustes_enlaza_todas_las_claves_reales(): void
    {
        $component = Livewire::test(SettingsPage::class);

        preg_match_all('/wire:model="values\.([^"]+)"/', $component->html(), $matches);
        $renderizadas = array_unique($matches[1]);

        $esperadas = array_map(
            fn (string $key) => SettingsPage::formKey($key),
            array_keys(SettingsPage::SCHEMA),
        );

        sort($renderizadas);
        sort($esperadas);

        $this->assertSame(
            $esperadas,
            $renderizadas,
            'Cada ajuste debe enlazarse a su clave real; si aparecen índices numéricos, groupBy() perdió las claves.'
        );
    }

    public function test_se_guardan_y_se_recuperan_todos_los_ajustes(): void
    {
        $component = Livewire::test(SettingsPage::class);

        // Un valor distinto y reconocible por cada campo de texto.
        $escritos = [];

        foreach (SettingsPage::SCHEMA as $key => [$label, $cast, $group]) {
            if ($cast === 'bool') {
                continue;
            }

            $valor = match (true) {
                in_array($cast, ['int', 'float'], true) => '7',
                str_contains($key, 'email') => 'ajustes@empresa.test',
                default => 'valor-'.md5($key),
            };
            $escritos[$key] = $valor;

            $component->set('values.'.SettingsPage::formKey($key), $valor);
        }

        $component->call('save')->assertHasNoErrors();

        foreach ($escritos as $key => $valor) {
            $this->assertEquals(
                $valor,
                (string) Setting::get($key),
                "El ajuste «{$key}» no se guardó."
            );
        }

        // Y al reabrir la pantalla deben verse de nuevo.
        $reabierto = Livewire::test(SettingsPage::class);

        foreach ($escritos as $key => $valor) {
            $reabierto->assertSet('values.'.SettingsPage::formKey($key), $valor);
        }
    }

    public function test_anadir_oficio_desde_configuracion(): void
    {
        Livewire::test(SettingsPage::class)
            ->set('tradeForm.name', 'Domótica')
            ->call('addTrade')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('trades', ['name' => 'Domótica']);
    }
}
