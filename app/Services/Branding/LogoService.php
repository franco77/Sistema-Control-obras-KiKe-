<?php

declare(strict_types=1);

namespace App\Services\Branding;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Logo de la empresa: subida, normalizado y consumo desde el panel, el
 * portal y el PDF.
 *
 * Va al disco público, no al privado de documentación: es una marca
 * corporativa, se pide en cada carga de página y no tiene sentido pagar una
 * petición de PHP por servirla.
 */
class LogoService
{
    public const DISK = 'public';
    public const SETTING_KEY = 'company.logo_path';

    /** Alto máximo en píxeles; el ancho se ajusta manteniendo proporción. */
    public const MAX_HEIGHT = 320;

    /**
     * Formatos admitidos.
     *
     * SVG queda deliberadamente fuera: puede contener <script> y se serviría
     * desde nuestro propio dominio, lo que lo convierte en un vector de XSS
     * almacenado. No compensa por un logo.
     */
    public const MIME_TYPES = ['image/png', 'image/jpeg', 'image/webp'];

    public const MAX_KILOBYTES = 2048;

    /** Guarda el logo, normalizado a PNG, y devuelve su ruta relativa. */
    public function store(UploadedFile $file): string
    {
        $image = Image::read($file->getRealPath());

        if ($image->height() > self::MAX_HEIGHT) {
            $image->scaleDown(height: self::MAX_HEIGHT);
        }

        // PNG para conservar la transparencia de los logos sobre fondo claro.
        $path = 'branding/logo-'.Str::random(12).'.png';

        Storage::disk(self::DISK)->put($path, (string) $image->toPng());

        $this->forget();

        Setting::put(self::SETTING_KEY, $path, 'string', 'empresa');

        return $path;
    }

    /** Elimina el logo actual y vuelve a las iniciales. */
    public function remove(): void
    {
        $this->forget();

        Setting::put(self::SETTING_KEY, '', 'string', 'empresa');
    }

    public function path(): ?string
    {
        $path = Setting::get(self::SETTING_KEY);

        if (blank($path) || ! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        return $path;
    }

    public function exists(): bool
    {
        return $this->path() !== null;
    }

    /**
     * URL relativa a la raíz, no absoluta.
     *
     * Storage::url() antepone APP_URL, de modo que el logo se rompería al
     * abrir la aplicación por otro host o puerto (127.0.0.1:8000 durante el
     * desarrollo, por ejemplo). Con ruta relativa funciona en cualquiera.
     * Si algún día se despliega en un subdirectorio, habrá que revisarlo.
     */
    public function url(): ?string
    {
        $path = $this->path();

        return $path ? '/storage/'.$path : null;
    }

    /**
     * El logo en base64 para el PDF.
     *
     * dompdf no descarga recursos remotos salvo que se habilite isRemoteEnabled
     * (que abre la puerta a SSRF), así que se incrusta el fichero directamente.
     */
    public function dataUri(): ?string
    {
        $path = $this->path();

        if ($path === null) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(Storage::disk(self::DISK)->get($path));
    }

    /** Borra del disco el fichero del logo actual, si lo hay. */
    private function forget(): void
    {
        $current = Setting::get(self::SETTING_KEY);

        if (filled($current) && Storage::disk(self::DISK)->exists($current)) {
            Storage::disk(self::DISK)->delete($current);
        }
    }
}
