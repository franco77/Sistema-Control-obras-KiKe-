<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Shared;

use App\Enums\DocumentCategory;
use App\Livewire\Concerns\WithToasts;
use App\Models\Document;
use App\Services\Documents\DocumentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Gestor de documentación reutilizable.
 *
 * Se monta sobre cualquier modelo con el trait HasDocuments (cliente,
 * inmueble, proveedor, obra o presupuesto) y encapsula subida, caducidades
 * y visibilidad para el cliente.
 */
class DocumentManager extends Component
{
    use WithFileUploads;
    use WithToasts;

    public Model $model;
    public bool $showClientToggle = true;

    /** Categorías sugeridas para este contexto. */
    public array $categories = [];

    #[Validate('required|file|max:20480')]
    public $file;

    #[Validate('required|string')]
    public string $category = 'other';

    #[Validate('nullable|string|max:191')]
    public string $name = '';

    #[Validate('nullable|date')]
    public ?string $issued_on = null;

    #[Validate('nullable|date|after:issued_on')]
    public ?string $expires_on = null;

    public bool $visible_to_client = false;
    public bool $uploading = false;

    public function mount(Model $model, array $categories = [], bool $showClientToggle = true): void
    {
        $this->model = $model;
        $this->categories = $categories ?: array_keys(DocumentCategory::options());
        $this->category = $this->categories[0] ?? 'other';
        $this->showClientToggle = $showClientToggle;
    }

    public function save(DocumentService $documents): void
    {
        $this->validate([
            'file' => ['required', 'file', 'max:20480'],
            'category' => ['required', Rule::enum(DocumentCategory::class)],
            'name' => ['nullable', 'string', 'max:191'],
            'issued_on' => ['nullable', 'date'],
            'expires_on' => ['nullable', 'date', 'after:issued_on'],
        ]);

        $documents->store($this->file, $this->model, DocumentCategory::from($this->category), array_filter([
            'name' => $this->name ?: null,
            'issued_on' => $this->issued_on,
            'expires_on' => $this->expires_on,
            'visible_to_client' => $this->visible_to_client,
        ], fn ($value) => $value !== null));

        $this->reset('file', 'name', 'issued_on', 'expires_on', 'visible_to_client', 'uploading');
        $this->toastSuccess('Documento subido correctamente.');
        $this->dispatch('documents-updated');
    }

    public function toggleClientVisibility(int $documentId): void
    {
        $document = $this->model->documents()->findOrFail($documentId);
        $document->update(['visible_to_client' => ! $document->visible_to_client]);
    }

    public function delete(int $documentId, DocumentService $documents): void
    {
        $documents->delete($this->model->documents()->findOrFail($documentId));
        $this->toastSuccess('Documento eliminado.');
    }

    public function render()
    {
        return view('livewire.admin.shared.document-manager', [
            'documents' => $this->model->documents()->with('uploader')->get(),
            'categoryOptions' => collect(DocumentCategory::cases())
                ->filter(fn (DocumentCategory $c) => in_array($c->value, $this->categories, true))
                ->all(),
        ]);
    }
}