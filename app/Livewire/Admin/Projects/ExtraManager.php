<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Projects;

use App\Enums\ExtraStatus;
use App\Livewire\Concerns\WithToasts;
use App\Models\Project;
use App\Services\Projects\ExtraWorkflow;
use Livewire\Component;

/**
 * Extras y modificados de obra. El cliente los aprueba desde su portal;
 * aquí se preparan, se envían y se ve el histórico de decisiones.
 */
class ExtraManager extends Component
{
    use WithToasts;

    public Project $project;

    public bool $showForm = false;
    public ?int $editingId = null;

    public array $form = [
        'title' => '', 'description' => '', 'justification' => '',
        'project_phase_id' => null, 'project_incident_id' => null,
        'amount' => 0, 'tax_rate' => 21, 'cost_estimated' => 0, 'extra_days' => 0,
    ];

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->form['tax_rate'] = (float) setting('company.tax_rate', 21);
    }

    public function newExtra(): void
    {
        $this->reset('form', 'editingId');
        $this->form['tax_rate'] = (float) setting('company.tax_rate', 21);
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $extra = $this->project->extras()->findOrFail($id);

        abort_unless($extra->status->is(ExtraStatus::Draft, ExtraStatus::Rejected), 403);

        $this->editingId = $id;
        $this->form = [
            'title' => $extra->title,
            'description' => $extra->description,
            'justification' => (string) $extra->justification,
            'project_phase_id' => $extra->project_phase_id,
            'project_incident_id' => $extra->project_incident_id,
            'amount' => (float) $extra->amount,
            'tax_rate' => (float) $extra->tax_rate,
            'cost_estimated' => (float) $extra->cost_estimated,
            'extra_days' => $extra->extra_days,
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->project);

        $data = $this->validate([
            'form.title' => ['required', 'string', 'max:191'],
            'form.description' => ['required', 'string', 'max:5000'],
            'form.justification' => ['nullable', 'string', 'max:5000'],
            'form.project_phase_id' => ['nullable', 'exists:project_phases,id'],
            'form.project_incident_id' => ['nullable', 'exists:project_incidents,id'],
            'form.amount' => ['required', 'numeric', 'min:0.01'],
            'form.tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'form.cost_estimated' => ['required', 'numeric', 'min:0'],
            'form.extra_days' => ['required', 'integer', 'min:0', 'max:365'],
        ])['form'];

        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);

        $this->editingId
            ? $this->project->extras()->findOrFail($this->editingId)->update($data)
            : $this->project->extras()->create($data + ['created_by' => auth()->id()]);

        $this->reset('form', 'editingId', 'showForm');
        $this->dispatch('project-updated');
        $this->toastSuccess('Extra guardado.');
    }

    public function send(int $id, ExtraWorkflow $workflow): void
    {
        $this->authorize('update', $this->project);

        $workflow->send($this->project->extras()->findOrFail($id));
        $this->toastSuccess('Extra enviado al cliente para su aprobación.');
    }

    public function cancel(int $id): void
    {
        $extra = $this->project->extras()->findOrFail($id);
        $extra->update(['status' => ExtraStatus::Cancelled]);
        $extra->portalTokens()->update(['revoked_at' => now()]);
        $this->dispatch('project-updated');
    }

    public function render()
    {
        return view('livewire.admin.projects.extra-manager', [
            'extras' => $this->project->extras()->with('phase', 'incident', 'author')->get(),
            'phases' => $this->project->phases()->get(),
            'incidents' => $this->project->incidents()->get(),
            'totals' => [
                'approved' => (float) $this->project->extras()->approved()->sum('amount'),
                'pending' => (float) $this->project->extras()->pending()->sum('amount'),
            ],
        ]);
    }
}