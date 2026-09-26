<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Projects;

use App\Enums\PhaseStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Livewire\Concerns\WithToasts;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ProjectTask;
use App\Models\Provider;
use App\Models\Trade;
use App\Models\User;
use App\Services\Projects\ProjectProgressService;
use App\Services\Projects\TaskAssignmentService;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Fases y tareas de la obra.
 *
 * Toda modificación de estado de tarea recalcula el avance de su fase y de
 * la obra: el porcentaje que ve el cliente nunca se teclea a mano.
 */
class PhaseManager extends Component
{
    use WithToasts;

    public Project $project;

    public array $expanded = [];

    // --- formulario de fase ---
    public bool $showPhaseForm = false;
    public ?int $editingPhaseId = null;
    public array $phaseForm = [
        'name' => '', 'description' => '', 'trade_id' => null, 'weight' => 1,
        'planned_start' => '', 'planned_end' => '', 'budget_amount' => 0, 'visible_to_client' => true,
    ];

    // --- formulario de tarea ---
    public ?int $taskPhaseId = null;
    public ?int $editingTaskId = null;
    public array $taskForm = [
        'name' => '', 'description' => '', 'trade_id' => null, 'provider_id' => null,
        'assigned_user_id' => null, 'priority' => 'normal', 'planned_start' => '', 'planned_end' => '',
        'estimated_hours' => null, 'cost_estimated' => 0, 'visible_to_client' => true,
    ];

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->expanded = $project->phases()->pluck('id')->take(3)->all();
    }

    public function toggle(int $phaseId): void
    {
        $this->expanded = in_array($phaseId, $this->expanded, true)
            ? array_values(array_diff($this->expanded, [$phaseId]))
            : [...$this->expanded, $phaseId];
    }

    // ----------------------------------------------------------- fases ---
    public function newPhase(): void
    {
        $this->reset('phaseForm', 'editingPhaseId');
        $this->showPhaseForm = true;
    }

    public function editPhase(int $id): void
    {
        $phase = $this->project->phases()->findOrFail($id);
        $this->editingPhaseId = $id;
        $this->phaseForm = [
            'name' => $phase->name,
            'description' => (string) $phase->description,
            'trade_id' => $phase->trade_id,
            'weight' => $phase->weight,
            'planned_start' => $phase->planned_start?->toDateString() ?? '',
            'planned_end' => $phase->planned_end?->toDateString() ?? '',
            'budget_amount' => (float) $phase->budget_amount,
            'visible_to_client' => $phase->visible_to_client,
        ];
        $this->showPhaseForm = true;
    }

    public function savePhase(ProjectProgressService $progress): void
    {
        $this->authorize('update', $this->project);

        $data = $this->validate([
            'phaseForm.name' => ['required', 'string', 'max:191'],
            'phaseForm.description' => ['nullable', 'string', 'max:2000'],
            'phaseForm.trade_id' => ['nullable', 'exists:trades,id'],
            'phaseForm.weight' => ['required', 'integer', 'min:1', 'max:100'],
            'phaseForm.planned_start' => ['nullable', 'date'],
            'phaseForm.planned_end' => ['nullable', 'date', 'after_or_equal:phaseForm.planned_start'],
            'phaseForm.budget_amount' => ['required', 'numeric', 'min:0'],
        ])['phaseForm'];

        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);

        if ($this->editingPhaseId) {
            $this->project->phases()->findOrFail($this->editingPhaseId)->update($data);
        } else {
            $this->project->phases()->create($data + [
                'position' => (int) $this->project->phases()->max('position') + 1,
            ]);
        }

        $progress->recalculate($this->project);
        $this->reset('phaseForm', 'editingPhaseId', 'showPhaseForm');
        $this->dispatch('project-updated');
        $this->toastSuccess('Fase guardada.');
    }

    public function deletePhase(int $id, ProjectProgressService $progress): void
    {
        $this->authorize('update', $this->project);

        $this->project->phases()->findOrFail($id)->delete();
        $progress->recalculate($this->project);
        $this->dispatch('project-updated');
        $this->toastSuccess('Fase eliminada.');
    }

    public function movePhase(int $id, int $direction): void
    {
        $ordered = $this->project->phases()->orderBy('position')->get();
        $index = $ordered->search(fn (ProjectPhase $p) => $p->id === $id);
        $target = $index + $direction;

        if ($index === false || $target < 0 || $target >= $ordered->count()) {
            return;
        }

        $ordered->splice($target, 0, [$ordered->pull($index)]);
        $ordered->values()->each(fn (ProjectPhase $p, int $i) => $p->update(['position' => $i]));
    }

    // ---------------------------------------------------------- tareas ---
    public function newTask(int $phaseId): void
    {
        $this->reset('taskForm', 'editingTaskId');
        $phase = $this->project->phases()->findOrFail($phaseId);

        $this->taskPhaseId = $phaseId;
        $this->taskForm['trade_id'] = $phase->trade_id;
        $this->taskForm['planned_start'] = $phase->planned_start?->toDateString() ?? '';
        $this->taskForm['planned_end'] = $phase->planned_end?->toDateString() ?? '';
    }

    public function editTask(int $id): void
    {
        $task = $this->project->tasks()->findOrFail($id);
        $this->editingTaskId = $id;
        $this->taskPhaseId = $task->project_phase_id;
        $this->taskForm = [
            'name' => $task->name,
            'description' => (string) $task->description,
            'trade_id' => $task->trade_id,
            'provider_id' => $task->provider_id,
            'assigned_user_id' => $task->assigned_user_id,
            'priority' => $task->priority->value,
            'planned_start' => $task->planned_start?->toDateString() ?? '',
            'planned_end' => $task->planned_end?->toDateString() ?? '',
            'estimated_hours' => $task->estimated_hours,
            'cost_estimated' => (float) $task->cost_estimated,
            'visible_to_client' => $task->visible_to_client,
        ];
    }

    public function saveTask(TaskAssignmentService $assignments, ProjectProgressService $progress): void
    {
        $this->authorize('update', $this->project);

        $data = $this->validate([
            'taskForm.name' => ['required', 'string', 'max:191'],
            'taskForm.description' => ['nullable', 'string', 'max:2000'],
            'taskForm.trade_id' => ['nullable', 'exists:trades,id'],
            'taskForm.provider_id' => ['nullable', 'exists:providers,id'],
            'taskForm.assigned_user_id' => ['nullable', 'exists:users,id'],
            'taskForm.priority' => ['required', Rule::enum(TaskPriority::class)],
            'taskForm.planned_start' => ['nullable', 'date'],
            'taskForm.planned_end' => ['nullable', 'date', 'after_or_equal:taskForm.planned_start'],
            'taskForm.estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'taskForm.cost_estimated' => ['required', 'numeric', 'min:0'],
        ])['taskForm'];

        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);
        $providerId = $data['provider_id'] ?? null;
        unset($data['provider_id']);

        $task = $this->editingTaskId
            ? tap($this->project->tasks()->findOrFail($this->editingTaskId))->update($data)
            : $this->project->tasks()->create($data + [
                'project_phase_id' => $this->taskPhaseId,
                'position' => (int) ProjectTask::where('project_phase_id', $this->taskPhaseId)->max('position') + 1,
            ]);

        // La asignación pasa siempre por el servicio: valida documentación,
        // bloquea el calendario del proveedor y le notifica.
        if ($providerId && (int) $providerId !== (int) $task->provider_id) {
            $assignments->assign($task, Provider::findOrFail($providerId));
        } elseif (! $providerId && $task->provider_id) {
            $assignments->unassign($task);
        }

        $progress->recalculate($this->project);
        $this->reset('taskForm', 'editingTaskId', 'taskPhaseId');
        $this->dispatch('project-updated');
        $this->toastSuccess('Tarea guardada.');
    }

    public function changeTaskStatus(int $taskId, string $status, ProjectProgressService $progress): void
    {
        $this->authorize('update', $this->project);

        $task = $this->project->tasks()->findOrFail($taskId);
        $task->update(['status' => TaskStatus::from($status)]);
        $task->recordActivity('task.status_changed', "{$task->name}: {$task->status->label()}");

        $progress->recalculate($this->project);
        $this->dispatch('project-updated');
    }

    public function deleteTask(int $taskId, ProjectProgressService $progress): void
    {
        $this->authorize('update', $this->project);

        $this->project->tasks()->findOrFail($taskId)->delete();
        $progress->recalculate($this->project);
        $this->dispatch('project-updated');
        $this->toastSuccess('Tarea eliminada.');
    }

    public function render()
    {
        return view('livewire.admin.projects.phase-manager', [
            'phases' => $this->project->phases()
                ->with(['tasks.provider', 'tasks.trade', 'tasks.assignee', 'trade'])
                ->get(),
            'trades' => Trade::active()->get(),
            'providers' => Provider::assignable()->with('trades')->orderBy('name')->get(),
            'users' => User::active()->orderBy('name')->get(['id', 'name']),
            'taskStatuses' => TaskStatus::cases(),
            'phaseStatuses' => PhaseStatus::cases(),
            'priorities' => TaskPriority::cases(),
        ]);
    }
}