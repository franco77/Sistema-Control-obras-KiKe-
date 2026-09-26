<?php

declare(strict_types=1);

namespace App\Notifications\Internal;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Alerta interna: obra que ha superado su fecha de fin prevista. */
class ProjectDelayedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Project $project) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Obra retrasada — {$this->project->code}")
            ->line("**{$this->project->name}** ({$this->project->client->name}) debía terminar el ".
                $this->project->planned_end->format('d/m/Y').'.')
            ->line("Avance actual: {$this->project->progress} %")
            ->action('Abrir la obra', route('admin.projects.show', $this->project));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'project.delayed',
            'title' => "Obra retrasada: {$this->project->code}",
            'body' => "{$this->project->name} — {$this->project->progress} % completado",
            'url' => route('admin.projects.show', $this->project),
        ];
    }
}