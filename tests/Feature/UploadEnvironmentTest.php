<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Admin\Settings\SettingsPage;
use App\Support\UploadEnvironment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Diagnóstico del entorno de subidas.
 *
 * Origen: el logo no se subía y el único mensaje era «The logo failed to
 * upload». La causa estaba fuera de la aplicación —PHP escribía los ficheros
 * subidos en C:\Windows\Temp, donde el proceso del servidor web podía crear
 * ficheros pero realpath() fallaba por las ACL de Windows, de modo que
 * UploadedFile::getRealPath() devolvía cadena vacía y Laravel reventaba con
 * «Path cannot be empty»— y ninguna prueba podía detectarla, porque los tests
 * de Livewire no pasan por el endpoint de subida.
 *
 * Estas pruebas cubren lo que sí se puede cubrir: que la aplicación detecte el
 * problema y lo explique, en vez de dejar al usuario con un mensaje genérico.
 */
class UploadEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UploadEnvironment::forget();
    }

    public function test_en_un_entorno_sano_no_reporta_problemas(): void
    {
        $this->assertNull(UploadEnvironment::problem());
    }

    public function test_el_directorio_temporal_es_el_que_usara_php(): void
    {
        $esperado = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();

        $this->assertSame($esperado, UploadEnvironment::temporaryDirectory());
    }

    public function test_detecta_un_directorio_temporal_inexistente(): void
    {
        $inexistente = sys_get_temp_dir().DIRECTORY_SEPARATOR.'no-existe-'.bin2hex(random_bytes(6));

        $problema = UploadEnvironment::problem($inexistente);

        $this->assertNotNull($problema);
        $this->assertStringContainsString('no existe', $problema);
    }

    public function test_la_comprobacion_no_deja_ficheros_sueltos(): void
    {
        $directorio = UploadEnvironment::temporaryDirectory();

        $antes = glob($directorio.DIRECTORY_SEPARATOR.'crm_upload_probe_*') ?: [];

        UploadEnvironment::problem();

        $despues = glob($directorio.DIRECTORY_SEPARATOR.'crm_upload_probe_*') ?: [];

        $this->assertSame($antes, $despues, 'El fichero de prueba debe borrarse siempre.');
    }

    public function test_el_resultado_se_cachea_para_no_escribir_en_cada_carga(): void
    {
        $this->assertNull(UploadEnvironment::cachedProblem());

        // Con el valor ya cacheado, no se vuelve a tocar disco.
        $ficheros = glob(UploadEnvironment::temporaryDirectory().DIRECTORY_SEPARATOR.'crm_upload_probe_*') ?: [];

        UploadEnvironment::cachedProblem();

        $this->assertSame(
            $ficheros,
            glob(UploadEnvironment::temporaryDirectory().DIRECTORY_SEPARATOR.'crm_upload_probe_*') ?: [],
        );

        UploadEnvironment::forget();
    }

    public function test_el_comando_de_comprobacion_termina_bien(): void
    {
        $this->artisan('crm:check-uploads')->assertSuccessful();
    }

    public function test_el_comando_falla_si_el_directorio_no_sirve(): void
    {
        $inexistente = sys_get_temp_dir().DIRECTORY_SEPARATOR.'no-existe-'.bin2hex(random_bytes(6));

        $this->artisan('crm:check-uploads', ['--dir' => $inexistente])->assertFailed();
    }

    public function test_configuracion_avisa_cuando_el_entorno_no_admite_subidas(): void
    {
        $this->seedBaseline();
        $this->actingAs($this->admin());

        // Entorno sano: sin aviso.
        Livewire::test(SettingsPage::class)
            ->assertSet('logo', null)
            ->assertDontSee('Este servidor no puede recibir ficheros');

        // Entorno roto: el aviso explica la causa antes de que el usuario
        // intente subir nada.
        Cache::put(UploadEnvironment::CACHE_KEY, 'El directorio temporal de subidas no existe.', 60);

        try {
            Livewire::test(SettingsPage::class)
                ->assertSee('Este servidor no puede recibir ficheros')
                ->assertSee('crm:check-uploads');
        } finally {
            UploadEnvironment::forget();
        }
    }
}
