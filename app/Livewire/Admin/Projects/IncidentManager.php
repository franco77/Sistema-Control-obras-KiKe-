<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Projects;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Livewire\Concerns\WithToasts;
use App\Models\Project;
use App\Models\Provider;
use App\Models\User;
use App\Notifications\Client\IncidentReportedNotification;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Portal\PortalTokenService;
use App\Services\Projects\ProjectCostService;
use Illuminate\Validation\Rule;
use Livewire\Component;

/** Registro y seguimiento de incidencias de obra. */
class IncidentManager extends Component
{
    use WithToasts;

    public Project $project;

    public bool $showForm = false;
    public ?int $editingId = null;
    public string $filter = 'open';

    public array $form = [
        'title' => '', 'description' => '', 'severity' => 'medium', 'status' => 'open',
        'project_phase_id' => null, 'project_task_id' => null, 'provider_id' => null,
        'assigned_to' => null, 'due_date' => '', 'resolution' => '',
        'cost_impact' => 0, 'days_impact' => 0, 'visible_to_client' => false,
    ];

    public function newIncident(): void
    {
        $this->reset('form', 'editingId');
        $this->form['assigned_to'] = $this->project->manager_id;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $incident = $this->project->incidents()->findOrFail($id);
        $this->editingId = $id;
        $this->form = [
            'title' => $incident->title,
            'description' => $incident->description,
            'severity' => $incident->severity->value,
            'status' => $incident->status->value,
            'project_phase_id' => $incident->project_phase_id,
            'project_task_id' => $incident->project_task_id,
            'provider_id' => $incident->provider_id,
            'assigned_to' => $incident->assigned_to,
            'due_date' => $incident->due_date?->toDateString() ?? '',
            'resolution' => (string) $incident->resolution,
            'cost_impact' => (float) $incident->cost_impact,
            'days_impact' => $incident->days_impact,
            'visible_to_client' => $incident->visible_to_client,
        ];
        $this->showForm = true;
    }

    public function save(ProjectCostService $costs): void
    {
        $this->authorize('update', $this->project);

        $data = $this->validate([
            'form.title' => ['required', 'string', 'max:191'],
            'form.description' => ['required', 'string', 'max:5000'],
            'form.severity' => ['required', Rule::enum(IncidentSeverity::class)],
            'form.status' => ['required', Rule::enum(IncidentStatus::class)],
            'form.project_phase_id' => ['nullable', 'exists:project_phases,id'],
            'form.project_task_id' => ['nullable', 'exists:project_tasks,id'],
            'form.provider_id' => ['nullable', 'exists:providers,id'],
            'form.assigned_to' => ['nullable', 'exists:users,id'],
            'form.due_date' => ['nullable', 'date'],
            'form.resolution' => ['nullable', 'string', 'max:5000'],
            'form.cost_impact' => ['required', 'numeric'],
            'form.days_impact' => ['required', 'integer', 'min:0', 'max:999'],
        ])['form'];

        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);

        $incident = $this->editingId
            ? tap($this->project->incidents()->findOrFail($this->editingId))->update($data)
            : $this->project->incidents()->create($data + ['reported_by' => auth()->id()]);

        $incident->recordActivity(
            $this->editingId ? 'incident.updated' : 'incident.opened',
            $incident->title
        );

        $costs->recalculate($this->project);
        $this->reset('form', 'editingId', 'showForm');
        $this->dispatch('project-updated');
        $this->toastSuccess('Incidencia guardada.');
    }

    /** Comunica la incidencia al cliente con enlace a su portal. */
    public function notifyClient(int $id, PortalTokenService $tokens, NotificationDispatcher $notifications): void
    {
        $this->authorize('update', $this->project);

        $incident = $this->project->incidents()->findOrFail($id);
        $incident->update(['visible_to_client' => true]);

        $plain = $tokens->issueOrReuse(
            $this->project,
            abilities: ['project.view', 'extra.view', 'extra.decide', 'message.send'],
            ttlDays: 365,
        );

        $notifications->toClient(
            $this->project->client,
            new IncidentReportedNotification($incident, $plain),
            related: $this->project,
        );

        $incident->recordActivity('incident.notified', 'Comunicada al cliente');
        $this->toastSuccess('Cliente informado de la incidencia.');
    }

    public function changeStatus(int $id, string $status): void
    {
        $incident = $this->project->incidents()->findOrFail($id);
        $incident->update(['status' => IncidentStatus::from($status)]);
        $incident->recordActivity('incident.status_changed', $incident->status->label());
    }

    public function render()
    {
        $incidents = $this->project->incidents()
            ->with('phase', 'task', 'provider', 'assignee', 'reporter')
            ->when($this->filter === 'open', fn ($q) => $q->open())
            ->get();

        return view('livewire.admin.projects.incident-manager', [
            'incidents' => $incidents,
            'phases' => $this->project->phases()->get(),
            'tasks' => $this->project->tasks()->get(),
            'providers' => Provider::orderBy('name')->get(['id', 'name']),
            'users' => User::active()->orderBy('name')->get(['id', 'name']),
            'severities' => IncidentSeverity::cases(),
            'statuses' => IncidentStatus::cases(),
        ]);
    }
}