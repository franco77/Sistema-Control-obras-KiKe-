<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Enums\DocumentCategory;
use App\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Almacenamiento de documentación adjunta en un disco privado.
 * Las rutas se organizan por tipo de entidad para facilitar auditorías
 * y backups selectivos: documents/clients/12/2026/uuid.pdf
 */
class DocumentService
{
    public const DISK = 'documents';

    public function store(
        UploadedFile $file,
        Model $documentable,
        DocumentCategory $category = DocumentCategory::Other,
        array $attributes = [],
    ): Document {
        $folder = sprintf(
            'documents/%s/%d/%s',
            Str::plural(Str::snake(class_basename($documentable))),
            $documentable->getKey(),
            now()->format('Y'),
        );

        $filename = Str::uuid()->toString().'.'.strtolower($file->getClientOriginalExtension() ?: 'bin');
        $path = $file->storeAs($folder, $filename, self::DISK);

        return $documentable->documents()->create(array_merge([
            'category' => $category,
            'name' => $attributes['name'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'hash' => hash_file('sha256', $file->getRealPath()),
            'uploaded_by' => auth()->id(),
        ], $attributes));
    }

    public function download(Document $document)
    {
        abort_unless($document->exists(), 404, 'El fichero ya no está disponible.');

        return Storage::disk($document->disk)->download(
            $document->path,
            $document->original_name ?: $document->name
        );
    }

    public function delete(Document $document, bool $force = false): void
    {
        $force ? $document->forceDelete() : $document->delete();
    }
}