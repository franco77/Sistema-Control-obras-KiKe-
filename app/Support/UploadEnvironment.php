<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Comprueba que el entorno puede recibir ficheros subidos.
 *
 * PHP escribe cada fichero subido en un directorio temporal antes de que la
 * aplicación lo vea. Si ese directorio no sirve, la subida falla dentro del
 * framework y el usuario solo ve «The logo failed to upload», sin pista alguna
 * de la causa.
 *
 * El caso que motivó esta clase: en Windows, sin `upload_tmp_dir` definido, PHP
 * usa C:\Windows\Temp, donde el proceso del servidor web puede crear ficheros
 * pero `realpath()` falla por las ACL del sistema. Como Laravel hace
 * `fopen($file->getRealPath())` para mover el fichero, revienta con
 * «Path cannot be empty». Escribir sí se podía; resolver la ruta, no.
 *
 * De ahí que la comprobación no se limite a is_writable(): escribe de verdad y
 * vuelve a resolver la ruta, que es exactamente lo que hará el framework.
 */
class UploadEnvironment
{
    public const CACHE_KEY = 'uploads.environment.problem';

    /** Directorio donde PHP dejará los ficheros subidos. */
    public static function temporaryDirectory(): string
    {
        return ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
    }

    /**
     * Describe el problema detectado, o null si las subidas funcionarán.
     *
     * Acepta el directorio como parámetro: upload_tmp_dir es PHP_INI_SYSTEM y
     * no se puede cambiar en caliente, así que sin esto la comprobación no
     * sería verificable con pruebas.
     */
    public static function problem(?string $directory = null): ?string
    {
        $directory ??= self::temporaryDirectory();

        if (! is_dir($directory)) {
            return "El directorio temporal de subidas ({$directory}) no existe.";
        }

        $probe = rtrim($directory, '\\/').DIRECTORY_SEPARATOR.'crm_upload_probe_'.bin2hex(random_bytes(6));

        if (@file_put_contents($probe, 'probe') === false) {
            return "No se puede escribir en el directorio temporal de subidas ({$directory}).";
        }

        $resolves = realpath($probe) !== false;

        @unlink($probe);

        if (! $resolves) {
            return "El directorio temporal de subidas ({$directory}) permite escribir pero "
                ."realpath() no resuelve las rutas, así que el framework no puede mover los "
                ."ficheros. Define upload_tmp_dir en php.ini apuntando a un directorio propio.";
        }

        return null;
    }

    /**
     * Igual que problem(), pero cacheado: la comprobación escribe en disco y
     * no tiene sentido repetirla en cada carga de página.
     */
    public static function cachedProblem(int $seconds = 300): ?string
    {
        // Se cachea cadena vacía en lugar de null: Cache::remember trata null
        // como «no cacheado» y repetiría la comprobación cada vez.
        $problem = Cache::remember(self::CACHE_KEY, $seconds, fn () => self::problem() ?? '');

        return $problem ?: null;
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
