<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Enums\PhotoStage;
use App\Models\Project;
use App\Models\ProjectPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Subida de fotos de avance con miniatura generada al vuelo.
 * Se guardan en disco privado: el portal las sirve por ruta controlada.
 */
class PhotoService
{
    public const DISK = 'documents';
    public const MAX_WIDTH = 1920;
    public const THUMB_WIDTH = 480;

    public function store(UploadedFile $file, Project $project, array $attributes = []): ProjectPhoto
    {
        $folder = sprintf('photos/projects/%d/%s', $project->id, now()->format('Y/m'));
        $name = Str::uuid()->toString();

        $image = Image::read($file->getRealPath());
        $width = $image->width();
        $height = $image->height();

        if ($width > self::MAX_WIDTH) {
            $image->scaleDown(width: self::MAX_WIDTH);
        }

        $path = "{$folder}/{$name}.jpg";
        $thumbPath = "{$folder}/{$name}_thumb.jpg";

        Storage::disk(self::DISK)->put($path, (string) $image->toJpeg(82));
        Storage::disk(self::DISK)->put(
            $thumbPath,
            (string) Image::read($file->getRealPath())->scaleDown(width: self::THUMB_WIDTH)->toJpeg(75)
        );

        return $project->photos()->create(array_merge([
            'disk' => self::DISK,
            'path' => $path,
            'thumb_path' => $thumbPath,
            'original_name' => $file->getClientOriginalName(),
            'mime' => 'image/jpeg',
            'size' => Storage::disk(self::DISK)->size($path),
            'width' => min($width, self::MAX_WIDTH),
            'height' => $height,
            'stage' => PhotoStage::Progress,
            'taken_at' => now(),
            'visible_to_client' => true,
            'uploaded_by' => auth()->id(),
        ], $attributes));
    }

    public function response(ProjectPhoto $photo, bool $thumb = false)
    {
        $path = $thumb ? ($photo->thumb_path ?: $photo->path) : $photo->path;

        abort_unless(Storage::disk($photo->disk)->exists($path), 404);

        return Storage::disk($photo->disk)->response($path, null, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}