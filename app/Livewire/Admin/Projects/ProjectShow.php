<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Projects;

use App\Enums\DocumentCategory;
use App\Enums\ProjectStatus;
use App\Livewire\Concerns\WithToasts;
use App\Models\Project;
use App\Models\User;
use App\Notifications\Client\ProjectFinishedNotification;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Portal\PortalTokenService;
use App\Services\Projects\ProjectCostService;
use App\Services\Projects\ProjectProgressService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Centro de mando de una obra. Delega cada bloque funcional en componentes
 * hijos (fases, incidencias, fotos, extras, diario) y se queda con la
 * cabecera, los indicadores y las acciones globales.
 */
#[Layout('components.layouts.admin')]
class ProjectShow extends Component
{
    use WithToasts;

    public Project $project;

    #[Url(as: 'tab')]
    public string $tab = 'fases';

    public ?string $portalLink = null;

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);
        $this->project = $project;
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    /** Los componentes hijos avisan cuando cambian algo que afecta al avance. */
    #[On('project-updated')]
    public function refreshProject(ProjectProgressService $progress, ProjectCostService $costs): void
    {
        $progress->recalculate($this->project);
        $costs->recalculate($this->project);
        $this->project->refresh();
    }

    public function changeStatus(string $status): void
    {
        $this->authorize('update', $this->project);

        $new = ProjectStatus::from($status);
        $previous = $this->project->status;

        $this->project->forceFill([
            'status' => $new,
            'actual_start' => $new === ProjectStatus::InProgress
                ? ($this->project->actual_start ?? now()->toDateString())
                : $this->project->actual_start,
            'actual_end' => $new === ProjectStatus::Finished ? now()->toDateString() : null,
        ])->save();

        $this->project->recordActivity('project.status_changed', "Estado: {$previous->label()} → {$new->label()}");
        $this->toastSuccess('Estado actualizado.');
    }

    public function generatePortalLink(PortalTokenService $tokens): void
    {
        $this->authorize('update', $this->project);

        $plain = $tokens->issueOrReuse(
            $this->project,
            abilities: ['project.view', 'extra.view', 'extra.decide', 'message.send'],
            ttlDays: (int) setting('projects.token_ttl_days', 365),
        );

        $this->portalLink = $tokens->urlFor($plain);
        $this->toastInfo('Enlace generado. Cópialo ahora: no vuelve a mostrarse.');
    }

    /** Cierra la obra y avisa al cliente con su enlace de garantía. */
    public function finishProject(
        ProjectProgressService $progress,
        PortalTokenService $tokens,
        NotificationDispatcher $notifications,
    ): void {
        $this->authorize('update', $this->project);

        $progress->recalculate($this->project);

        if ($this->project->tasks()->open()->exists()) {
            $this->toastError('Quedan tareas abiertas. Ciérralas o cancélalas antes de finalizar.');

            return;
        }

        $this->project->forceFill([
            'status' => ProjectStatus::Finished,
            'actual_end' => now()->toDateString(),
        ])->save();

        $plain = $tokens->issueOrReuse(
            $this->project,
            abilities: ['project.view'],
            ttlDays: max(30, $this->project->warranty_months * 31),
        );

        $notifications->toClient(
            $this->project->client,
            new ProjectFinishedNotification($this->project, $plain),
            related: $this->project,
        );

        $this->project->recordActivity('project.finished', 'Obra entregada al cliente');
        $this->toastSuccess('Obra finalizada y cliente avisado.');
    }

    public function render()
    {
        $this->project->load('client', 'property', 'quote', 'manager');

        return view('livewire.admin.projects.project-show', [
            'costSummary' => app(ProjectCostService::class)->summary($this->project),
            'statuses' => ProjectStatus::cases(),
            'managers' => User::active()->orderBy('name')->get(['id', 'name']),
            'activities' => $this->tab === 'actividad'
                ? $this->project->activities()->with('causer')->limit(60)->get()
                : null,
            'documentCategories' => [
                DocumentCategory::Contract->value, DocumentCategory::Plan->value,
                DocumentCategory::License->value, DocumentCategory::Invoice->value,
                DocumentCategory::Certificate->value, DocumentCategory::DeliveryNote->value,
                DocumentCategory::Other->value,
            ],
            'counters' => [
                'incidents' => $this->project->openIncidentsCount(),
                'extras' => $this->project->pendingExtrasCount(),
                'photos' => $this->project->photos()->count(),
            ],
        ])->title($this->project->name);
    }
}