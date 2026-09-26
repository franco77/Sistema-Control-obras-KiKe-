<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\UploadEnvironment;
use Illuminate\Console\Command;

/**
 * Comprueba que el servidor puede recibir ficheros.
 *
 * Pensado para ejecutarlo tras cada despliegue o cuando una subida falla sin
 * explicación: el fallo ocurre dentro del framework y el usuario solo ve un
 * mensaje genérico.
 */
class CheckUploadsCommand extends Command
{
    protected $signature = 'crm:check-uploads {--dir= : Comprobar este directorio en lugar del configurado}';

    protected $description = 'Verifica que el directorio temporal de subidas es utilizable';

    public function handle(): int
    {
        UploadEnvironment::forget();

        $directory = $this->option('dir') ?: UploadEnvironment::temporaryDirectory();

        $this->components->twoColumnDetail('Directorio temporal', $directory);
        $this->components->twoColumnDetail('upload_tmp_dir', ini_get('upload_tmp_dir') ?: '<no definido>');
        $this->components->twoColumnDetail('upload_max_filesize', ini_get('upload_max_filesize'));
        $this->components->twoColumnDetail('post_max_size', ini_get('post_max_size') ?: '0 (sin límite)');

        $problem = UploadEnvironment::problem($directory);

        if ($problem !== null) {
            $this->newLine();
            $this->components->error($problem);

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('El directorio temporal de subidas es utilizable.');

        return self::SUCCESS;
    }
}
