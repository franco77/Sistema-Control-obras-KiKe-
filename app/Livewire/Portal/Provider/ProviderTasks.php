<?php

declare(strict_types=1);

namespace App\Livewire\Portal\Provider;

use App\Enums\PhotoStage;
use App\Enums\TaskStatus;
use App\Livewire\Concerns\InteractsWithPortal;
use App\Livewire\Concerns\WithToasts;
use App\Models\Provider;
use App\Models\ProjectTask;
use App\Services\Documents\PhotoService;
use App\Services\Projects\ProjectProgressService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Portal del proveedor: ve sus tareas, marca avances y sube fotos.
 * Nunca ve importes de venta ni datos de otros proveedores.
 */
#[Layout('components.layouts.portal')]
class ProviderTasks extends Component
{
    use InteractsWithPortal;
    use WithFileUploads;
    use WithToasts;

    public ?int $uploadingTaskId = null;

    /** @var array<int, mixed> */
    public array $photos = [];

    private function provider(): Provider
    {
        $provider = $this->portalToken()->tokenable;

        abort_unless($provider instanceof Provider, 404);

        return $provider;
    }

    public function updateStatus(int $taskId, string $status, ProjectProgressService $progress): void
    {
        $this->portalAbility('provider.tasks');

        $task = $this->task($taskId);

        // El proveedor solo puede mover la tarea dentro de su flujo operativo.
        abort_unless(in_array($status, ['in_progress', 'blocked', 'review', 'done'], true), 403);

        $task->update(['status' => TaskStatus::from($status)]);
        $task->recordActivity('task.status_changed', "{$task->provider->name}: {$task->status->label()}");

        $progress->recalculate($task->project);

        $this->toastSuccess('Estado actualizado. Gracias.');
    }

    public function uploadPhotos(PhotoService $photos): void
    {
        $this->portalAbility('provider.photos');

        $this->validate([
            'photos' => ['required', 'array', 'min:1', 'max:10'],
            'photos.*' => ['image', 'max:10240'],
        ], ['photos.*.image' => 'Solo imágenes.', 'photos.*.max' => 'Máximo 10 MB por foto.']);

        $task = $this->task($this->uploadingTaskId);
        $provider = $this->provider();

        foreach ($this->photos as $photo) {
            $photos->store($photo, $task->project, [
                'project_phase_id' => $task->project_phase_id,
                'project_task_id' => $task->id,
                'stage' => PhotoStage::Progress,
                'caption' => $task->name,
                'visible_to_client' => false, // el equipo decide si publicarla
                'uploaded_by' => null,
                'uploaded_by_provider_id' => $provider->id,
            ]);
        }

        $this->reset('photos', 'uploadingTaskId');
        $this->toastSuccess('Fotos subidas. El equipo las revisará.');
    }

    private function task(?int $id): ProjectTask
    {
        return ProjectTask::with('project', 'phase', 'provider')
            ->where('provider_id', $this->provider()->id)
            ->findOrFail($id);
    }

    public function render()
    {
        $provider = $this->provider();

        $tasks = ProjectTask::with('project.property', 'phase')
            ->forProvider($provider->id)
            ->whereHas('project', fn ($q) => $q->open())
            ->orderBy('planned_start')
            ->get()
            ->groupBy('project_id');

        return view('livewire.portal.provider.provider-tasks', [
            'provider' => $provider,
            'grouped' => $tasks,
            'missingDocuments' => $provider->missing_documents,
        ])->title('Mis trabajos');
    }
}