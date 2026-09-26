<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Projects;

use App\Livewire\Concerns\WithToasts;
use App\Models\Project;
use App\Notifications\Client\ProjectUpdatePublishedNotification;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Portal\PortalTokenService;
use Livewire\Component;

/**
 * Diario de obra. Cada parte puede publicarse en el portal del cliente y,
 * opcionalmente, avisarle por email.
 */
class UpdateComposer extends Component
{
    use WithToasts;

    public Project $project;

    public array $form = [
        'title' => '', 'body' => '', 'project_phase_id' => null,
        'visible_to_client' => true, 'notify_client' => false,
    ];

    public function save(PortalTokenService $tokens, NotificationDispatcher $notifications): void
    {
        $this->authorize('update', $this->project);

        $data = $this->validate([
            'form.title' => ['nullable', 'string', 'max:191'],
            'form.body' => ['required', 'string', 'max:5000'],
            'form.project_phase_id' => ['nullable', 'exists:project_phases,id'],
        ])['form'];

        $update = $this->project->updates()->create([
            ...$data,
            'project_phase_id' => $data['project_phase_id'] ?: null,
            'user_id' => auth()->id(),
            'progress_snapshot' => $this->project->progress,
            'published_at' => now(),
        ]);

        if ($update->notify_client && $update->visible_to_client) {
            $plain = $tokens->issueOrReuse(
                $this->project,
                abilities: ['project.view', 'extra.view', 'extra.decide', 'message.send'],
                ttlDays: 365,
            );

            $notifications->toClient(
                $this->project->client,
                new ProjectUpdatePublishedNotification($update, $plain),
                related: $this->project,
            );
        }

        $this->project->recordActivity('project.update_published', $update->title ?: 'Nuevo parte de obra');

        $this->reset('form');
        $this->form['visible_to_client'] = true;
        $this->toastSuccess('Parte publicado.');
    }

    public function delete(int $id): void
    {
        $this->authorize('update', $this->project);
        $this->project->updates()->findOrFail($id)->delete();
        $this->toastSuccess('Parte eliminado.');
    }

    public function render()
    {
        return view('livewire.admin.projects.update-composer', [
            'updates' => $this->project->updates()->with('author', 'phase')->get(),
            'phases' => $this->project->phases()->get(),
        ]);
    }
}