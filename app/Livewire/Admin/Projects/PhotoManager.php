<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Projects;

use App\Enums\PhotoStage;
use App\Livewire\Concerns\WithToasts;
use App\Models\Project;
use App\Services\Documents\PhotoService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Reportaje fotográfico de la obra. Cada foto se marca con su momento
 * (antes/avance/después) y si es visible en el portal del cliente.
 */
class PhotoManager extends Component
{
    use WithFileUploads;
    use WithToasts;

    public Project $project;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $uploads = [];

    public ?int $phaseId = null;
    public string $stage = 'progress';
    public string $caption = '';
    public bool $visibleToClient = true;

    #[Url(as: 'etapa', except: '')]
    public string $filterStage = '';

    public function updatedUploads(): void
    {
        $this->validate([
            'uploads.*' => ['image', 'max:10240'],
        ], ['uploads.*.image' => 'Solo se admiten imágenes.', 'uploads.*.max' => 'Máximo 10 MB por foto.']);
    }

    public function save(PhotoService $photos): void
    {
        $this->authorize('update', $this->project);

        $this->validate([
            'uploads' => ['required', 'array', 'min:1'],
            'uploads.*' => ['image', 'max:10240'],
            'stage' => ['required', Rule::enum(PhotoStage::class)],
            'phaseId' => ['nullable', 'exists:project_phases,id'],
            'caption' => ['nullable', 'string', 'max:191'],
        ]);

        foreach ($this->uploads as $upload) {
            $photos->store($upload, $this->project, [
                'project_phase_id' => $this->phaseId,
                'stage' => PhotoStage::from($this->stage),
                'caption' => $this->caption ?: null,
                'visible_to_client' => $this->visibleToClient,
            ]);
        }

        $count = count($this->uploads);
        $this->project->recordActivity('project.photos_added', "{$count} fotos subidas");

        $this->reset('uploads', 'caption');
        $this->toastSuccess("{$count} fotos subidas.");
    }

    public function toggleVisibility(int $photoId): void
    {
        $photo = $this->project->photos()->findOrFail($photoId);
        $photo->update(['visible_to_client' => ! $photo->visible_to_client]);
    }

    public function setCover(int $photoId): void
    {
        $this->project->photos()->update(['is_cover' => false]);
        $this->project->photos()->whereKey($photoId)->update(['is_cover' => true]);
        $this->toastSuccess('Portada actualizada.');
    }

    public function delete(int $photoId): void
    {
        $this->authorize('update', $this->project);
        $this->project->photos()->findOrFail($photoId)->delete();
        $this->toastSuccess('Foto eliminada.');
    }

    public function render()
    {
        return view('livewire.admin.projects.photo-manager', [
            'photos' => $this->project->photos()
                ->with('phase')
                ->stage($this->filterStage ?: null)
                ->latest('taken_at')
                ->get()
                ->groupBy(fn ($photo) => $photo->taken_at?->format('Y-m') ?? 'sin-fecha'),
            'phases' => $this->project->phases()->get(),
            'stages' => PhotoStage::cases(),
        ]);
    }
}