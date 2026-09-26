<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Projects;

use App\Enums\ProjectStatus;
use App\Livewire\Concerns\WithToasts;
use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Tablero por estado de obra, con cambio de estado en un clic. */
#[Layout('components.layouts.admin')]
#[Title('Tablero de obras')]
class ProjectBoard extends Component
{
    use WithToasts;

    /** Columnas visibles del tablero. */
    public array $columns = ['planned', 'in_progress', 'paused', 'finished'];

    public function moveTo(int $projectId, string $status): void
    {
        $project = Project::findOrFail($projectId);
        $this->authorize('update', $project);

        $newStatus = ProjectStatus::from($status);
        $previous = $project->status;

        $project->forceFill([
            'status' => $newStatus,
            'actual_start' => $newStatus === ProjectStatus::InProgress
                ? ($project->actual_start ?? now()->toDateString())
                : $project->actual_start,
            'actual_end' => $newStatus === ProjectStatus::Finished
                ? ($project->actual_end ?? now()->toDateString())
                : null,
        ])->save();

        $project->recordActivity(
            'project.status_changed',
            "Estado: {$previous->label()} → {$newStatus->label()}"
        );

        $this->toastSuccess("{$project->code} movida a «{$newStatus->label()}».");
    }

    public function render()
    {
        $projects = Project::with('client', 'manager')
            ->whereIn('status', $this->columns)
            ->orderBy('planned_end')
            ->get()
            ->groupBy(fn (Project $p) => $p->status->value);

        return view('livewire.admin.projects.project-board', [
            'grouped' => $projects,
            'statuses' => collect($this->columns)->map(fn (string $s) => ProjectStatus::from($s)),
        ]);
    }
}