<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\Settings\SettingsPage;
use App\Models\Setting;
use App\Services\Branding\LogoService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File as TestingFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyLogoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaseline();
        Storage::fake(LogoService::DISK);
        $this->actingAs($this->admin());
    }

    /**
     * Imagen real generada con GD, no un fichero vacio: LogoService la abre
     * con Intervention y la reescala, asi que debe ser una imagen de verdad.
     */
    private function imagen(int $ancho = 800, int $alto = 600, string $nombre = 'logo.png'): TestingFile
    {
        return UploadedFile::fake()->image($nombre, $ancho, $alto);
    }

    public function test_subir_el_logo_lo_guarda_y_lo_registra_en_los_ajustes(): void
    {
        Livewire::test(SettingsPage::class)
            ->set('logo', $this->imagen())
            ->call('saveLogo')
            ->assertHasNoErrors();

        $path = Setting::get(LogoService::SETTING_KEY);

        $this->assertNotEmpty($path);
        Storage::disk(LogoService::DISK)->assertExists($path);
        $this->assertStringEndsWith('.png', $path);
    }

    public function test_el_logo_se_reescala_a_la_altura_maxima(): void
    {
        Livewire::test(SettingsPage::class)
            ->set('logo', $this->imagen(2000, 1500))
            ->call('saveLogo');

        $contenido = Storage::disk(LogoService::DISK)->get(Setting::get(LogoService::SETTING_KEY));
        $imagen = Image::read($contenido);

        $this->assertSame(LogoService::MAX_HEIGHT, $imagen->height());
        $this->assertSame(427, $imagen->width(), 'Debe mantener la proporción original.');
    }

    public function test_un_jpg_se_normaliza_a_png(): void
    {
        Livewire::test(SettingsPage::class)
            ->set('logo', $this->imagen(600, 400, 'logo.jpg'))
            ->call('saveLogo')
            ->assertHasNoErrors();

        $path = Setting::get(LogoService::SETTING_KEY);

        $this->assertStringEndsWith('.png', $path);
        $this->assertSame('image/png', Image::read(Storage::disk(LogoService::DISK)->get($path))->origin()->mediaType());
    }

    public function test_subir_un_logo_nuevo_borra_el_anterior(): void
    {
        $component = Livewire::test(SettingsPage::class)
            ->set('logo', $this->imagen())
            ->call('saveLogo');

        $primero = Setting::get(LogoService::SETTING_KEY);

        $component->set('logo', $this->imagen(400, 300))->call('saveLogo');

        $segundo = Setting::get(LogoService::SETTING_KEY);

        $this->assertNotSame($primero, $segundo);
        Storage::disk(LogoService::DISK)->assertMissing($primero);
        Storage::disk(LogoService::DISK)->assertExists($segundo);
    }

    public function test_quitar_el_logo_borra_el_fichero(): void
    {
        $component = Livewire::test(SettingsPage::class)
            ->set('logo', $this->imagen())
            ->call('saveLogo');

        $path = Setting::get(LogoService::SETTING_KEY);

        $component->call('removeLogo');

        Storage::disk(LogoService::DISK)->assertMissing($path);
        $this->assertFalse(app(LogoService::class)->exists());
    }

    public function test_se_rechaza_un_fichero_que_no_es_imagen(): void
    {
        Livewire::test(SettingsPage::class)
            ->set('logo', UploadedFile::fake()->create('contrato.pdf', 100, 'application/pdf'))
            ->call('saveLogo')
            ->assertHasErrors('logo');

        $this->assertFalse(app(LogoService::class)->exists());
    }

    public function test_se_rechaza_un_svg_por_riesgo_de_xss(): void
    {
        Livewire::test(SettingsPage::class)
            ->set('logo', UploadedFile::fake()->create('logo.svg', 4, 'image/svg+xml'))
            ->call('saveLogo')
            ->assertHasErrors('logo');

        $this->assertFalse(app(LogoService::class)->exists());
    }

    public function test_se_rechaza_un_fichero_demasiado_grande(): void
    {
        Livewire::test(SettingsPage::class)
            ->set('logo', UploadedFile::fake()->image('enorme.png')->size(LogoService::MAX_KILOBYTES + 1))
            ->call('saveLogo')
            ->assertHasErrors('logo');
    }

    public function test_sin_logo_la_url_es_nula_y_el_layout_usa_iniciales(): void
    {
        $servicio = app(LogoService::class);

        $this->assertNull($servicio->url());
        $this->assertNull($servicio->dataUri());

        $this->get('/panel')->assertOk()->assertSee('Re', escape: false);
    }

    public function test_el_logo_aparece_en_el_panel_y_en_el_portal(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->seed(DemoDataSeeder::class);

        Livewire::test(SettingsPage::class)
            ->set('logo', $this->imagen())
            ->call('saveLogo');

        $url = app(LogoService::class)->url();
        $this->assertStringStartsWith('/storage/branding/', $url);

        // Panel
        $this->actingAs($this->admin())->get('/panel')->assertOk()->assertSee($url, escape: false);

        // Portal del cliente
        $plain = app(\App\Services\Portal\PortalTokenService::class)
            ->issue(\App\Models\Project::first(), ['project.view']);

        $this->get('/portal/t/'.$plain);
        $this->get('/portal/obra')->assertOk()->assertSee($url, escape: false);
    }

    public function test_el_pdf_incrusta_el_logo_en_base64(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->seed(DemoDataSeeder::class);

        Livewire::test(SettingsPage::class)
            ->set('logo', $this->imagen())
            ->call('saveLogo');

        $dataUri = app(LogoService::class)->dataUri();

        $this->assertStringStartsWith('data:image/png;base64,', $dataUri);

        $quote = \App\Models\Quote::first();

        $response = $this->actingAs($this->admin())->get("/panel/presupuestos/{$quote->id}/pdf");

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_un_logo_borrado_del_disco_no_rompe_las_paginas(): void
    {
        Livewire::test(SettingsPage::class)
            ->set('logo', $this->imagen())
            ->call('saveLogo');

        // Alguien limpia storage a mano: el ajuste apunta a un fichero que ya no está.
        Storage::disk(LogoService::DISK)->delete(Setting::get(LogoService::SETTING_KEY));

        $this->assertNull(app(LogoService::class)->url());
        $this->get('/panel')->assertOk();
    }
}
