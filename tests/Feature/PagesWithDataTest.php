<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AuthorType;
use App\Models\Conversation;
use App\Models\PortalAccessToken;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Quote;
use App\Services\Portal\PortalTokenService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Carga de todas las pantallas CON DATOS REALES.
 *
 * El smoke test anterior corría contra tablas vacías: los bucles de las vistas
 * no se ejecutaban y los accesores no llegaban a llamarse, así que no detectaba
 * violaciones de lazy loading ni errores dentro de los listados. Estas pruebas
 * usan el seeder de demostración, que es lo que el usuario ve en pantalla.
 */
class PagesWithDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaseline();
        $this->seed(CatalogSeeder::class);
        $this->seed(DemoDataSeeder::class);

        // Un hilo de conversación con mensajes de ambas partes, ya persistidos:
        // el fallo solo aparece cuando los modelos NO son wasRecentlyCreated.
        $project = Project::first();

        $conversation = Conversation::create([
            'client_id' => $project->client_id,
            'project_id' => $project->id,
            'subject' => '¿Cuándo entran los pintores?',
            'last_message_at' => now(),
            'unread_for_staff' => 1,
        ]);

        $conversation->messages()->create([
            'author_type' => AuthorType::Client,
            'author_name' => $project->client->name,
            'body' => 'Buenos días, ¿sabéis ya la semana?',
        ]);

        $conversation->messages()->create([
            'author_type' => AuthorType::Staff,
            'user_id' => $project->manager_id,
            'author_name' => 'Laura Jefa de Obra',
            'body' => 'Entran el lunes 12. Te confirmo el viernes.',
        ]);
    }

    // ================================================================ PANEL
    public static function adminPages(): array
    {
        return [
            'panel' => ['/panel'],
            'clientes' => ['/panel/clientes'],
            'ficha cliente · resumen' => ['/panel/clientes/{client}'],
            'ficha cliente · inmuebles' => ['/panel/clientes/{client}?tab=inmuebles'],
            'ficha cliente · contactos' => ['/panel/clientes/{client}?tab=contactos'],
            'ficha cliente · presupuestos' => ['/panel/clientes/{client}?tab=presupuestos'],
            'ficha cliente · obras' => ['/panel/clientes/{client}?tab=obras'],
            'ficha cliente · documentos' => ['/panel/clientes/{client}?tab=documentos'],
            'ficha cliente · actividad' => ['/panel/clientes/{client}?tab=actividad'],
            'editar cliente' => ['/panel/clientes/{client}/editar'],
            'proveedores' => ['/panel/proveedores'],
            'ficha proveedor · resumen' => ['/panel/proveedores/{provider}'],
            'ficha proveedor · documentación' => ['/panel/proveedores/{provider}?tab=documentacion'],
            'ficha proveedor · disponibilidad' => ['/panel/proveedores/{provider}?tab=disponibilidad'],
            'ficha proveedor · trabajos' => ['/panel/proveedores/{provider}?tab=trabajos'],
            'ficha proveedor · valoraciones' => ['/panel/proveedores/{provider}?tab=valoraciones'],
            'editar proveedor' => ['/panel/proveedores/{provider}/editar'],
            'presupuestos' => ['/panel/presupuestos'],
            'ficha presupuesto' => ['/panel/presupuestos/{quote}'],
            'editor presupuesto' => ['/panel/presupuestos/{quote}/editar'],
            'obras' => ['/panel/obras'],
            'tablero obras' => ['/panel/obras/tablero'],
            'obra · fases' => ['/panel/obras/{project}'],
            'obra · incidencias' => ['/panel/obras/{project}?tab=incidencias'],
            'obra · fotos' => ['/panel/obras/{project}?tab=fotos'],
            'obra · extras' => ['/panel/obras/{project}?tab=extras'],
            'obra · diario' => ['/panel/obras/{project}?tab=diario'],
            'obra · documentos' => ['/panel/obras/{project}?tab=documentos'],
            'obra · actividad' => ['/panel/obras/{project}?tab=actividad'],
            'agenda' => ['/panel/agenda'],
            'agenda · lista' => ['/panel/agenda?vista=list'],
            'catálogo' => ['/panel/catalogo'],
            'documentos' => ['/panel/documentos'],
            'mensajes' => ['/panel/mensajes'],
            'hilo de mensajes' => ['/panel/mensajes/{conversation}'],
            'configuración' => ['/panel/configuracion'],
        ];
    }

    /** Sustituye los marcadores por los identificadores reales de esta ejecución. */
    private function resolvePath(string $path): string
    {
        return strtr($path, [
            '{client}' => (string) \App\Models\Client::value('id'),
            '{provider}' => (string) Provider::value('id'),
            '{quote}' => (string) Quote::value('id'),
            '{project}' => (string) Project::value('id'),
            '{conversation}' => (string) Conversation::value('id'),
        ]);
    }

    /** @dataProvider adminPages */
    public function test_las_pantallas_del_panel_cargan_con_datos(string $path): void
    {
        $this->actingAs($this->admin())->get($this->resolvePath($path))->assertOk();
    }

    // =============================================================== PORTAL
    private function openPortal($tokenable, array $abilities, string $audience = 'client'): void
    {
        $plain = app(PortalTokenService::class)->issue($tokenable, $abilities, $audience);

        $this->get('/portal/t/'.$plain)->assertRedirect();
    }

    public static function clientPortalPages(): array
    {
        return [
            'resumen' => ['/portal/obra'],
            'trabajos' => ['/portal/obra/tareas'],
            'fotos' => ['/portal/obra/fotos'],
            'incidencias' => ['/portal/obra/incidencias'],
            'extras' => ['/portal/obra/extras'],
            'documentos' => ['/portal/obra/documentos'],
            'mensajes' => ['/portal/obra/mensajes'],
        ];
    }

    /** @dataProvider clientPortalPages */
    public function test_las_pantallas_del_portal_cargan_con_datos(string $path): void
    {
        $this->openPortal(Project::first(), [
            'project.view', 'extra.view', 'extra.decide', 'message.send',
        ]);

        $this->get($path)->assertOk();
    }

    public function test_el_portal_muestra_un_hilo_con_respuesta_del_equipo(): void
    {
        $this->openPortal(Project::first(), ['project.view', 'message.send']);

        $this->get('/portal/obra/mensajes')
            ->assertOk()
            ->assertSee('Entran el lunes 12', escape: false)
            ->assertSee('Laura Jefa de Obra');
    }

    public function test_el_panel_muestra_el_hilo_con_el_mensaje_del_cliente(): void
    {
        $this->actingAs($this->admin())
            ->get($this->resolvePath('/panel/mensajes/{conversation}'))
            ->assertOk()
            ->assertSee('Buenos días, ¿sabéis ya la semana?', escape: false);
    }

    public function test_el_portal_del_presupuesto_carga(): void
    {
        $quote = Quote::pending()->first();

        $this->openPortal($quote, ['quote.view', 'quote.decide', 'quote.download']);

        $this->get('/portal/presupuesto')->assertOk();
    }

    public function test_el_portal_del_proveedor_carga_con_tareas(): void
    {
        $provider = Provider::has('tasks')->first();

        $this->openPortal($provider, ['provider.tasks', 'provider.photos'], 'provider');

        $this->get('/portal/proveedor')->assertOk();
    }

    public function test_el_pdf_del_presupuesto_se_genera(): void
    {
        $response = $this->actingAs($this->admin())->get($this->resolvePath('/panel/presupuestos/{quote}/pdf'));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
