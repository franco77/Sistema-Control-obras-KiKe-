<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Comprobación de humo: todas las pantallas del panel responden. */
class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseline();
        $this->seed(CatalogSeeder::class);
    }

    public static function pageProvider(): array
    {
        return [
            'panel' => ['admin.dashboard'],
            'clientes' => ['admin.clients.index'],
            'nuevo cliente' => ['admin.clients.create'],
            'proveedores' => ['admin.providers.index'],
            'nuevo proveedor' => ['admin.providers.create'],
            'presupuestos' => ['admin.quotes.index'],
            'obras' => ['admin.projects.index'],
            'tablero de obras' => ['admin.projects.board'],
            'agenda' => ['admin.calendar.index'],
            'catálogo' => ['admin.catalog.index'],
            'documentos' => ['admin.documents.index'],
            'mensajes' => ['admin.conversations.index'],
            'configuración' => ['admin.settings.index'],
        ];
    }

    /** @dataProvider pageProvider */
    public function test_las_pantallas_del_panel_cargan(string $routeName): void
    {
        $this->actingAs($this->admin())
            ->get(route($routeName))
            ->assertOk();
    }

    public function test_el_panel_exige_autenticacion(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_solo_admin_entra_en_configuracion(): void
    {
        $this->actingAs($this->userWithRole('comercial'))
            ->get(route('admin.settings.index'))
            ->assertForbidden();
    }

    public function test_un_comercial_no_puede_crear_proveedores(): void
    {
        $this->actingAs($this->userWithRole('comercial'))
            ->get(route('admin.providers.create'))
            ->assertForbidden();
    }
}