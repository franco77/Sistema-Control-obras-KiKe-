<?php

declare(strict_types=1);

namespace App\Notifications\Provider;

use App\Models\ProjectTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Aviso al proveedor de una nueva tarea asignada, con enlace a su portal. */
class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ProjectTask $task,
        public readonly string $plainToken,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function logSubject(): string
    {
        return 'Tarea asignada: '.$this->task->name;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $project = $this->task->project;

        return (new MailMessage)
            ->subject("Nueva tarea asignada — {$project->code}")
            ->greeting('Hola,')
            ->line("Te hemos asignado una tarea en la obra **{$project->name}**.")
            ->line("**{$this->task->name}**")
            ->when($this->task->description, fn (MailMessage $m) => $m->line($this->task->description))
            ->when($this->task->planned_start, fn (MailMessage $m) => $m->line(
                'Fechas previstas: '.$this->task->planned_start->format('d/m/Y').
                ' — '.$this->task->planned_end?->format('d/m/Y')
            ))
            ->line('Dirección: '.($project->property?->full_address ?? 'por confirmar'))
            ->action('Ver mis tareas', route('portal.enter', ['token' => $this->plainToken]))
            ->line('Desde ese enlace puedes marcar avances, subir fotos y avisar de incidencias.');
    }
}