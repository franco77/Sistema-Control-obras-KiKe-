<?php

declare(strict_types=1);

namespace App\Notifications\Client;

use App\Models\ProjectUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Aviso de nuevo avance publicado en el portal del cliente. */
class ProjectUpdatePublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ProjectUpdate $update,
        public readonly string $plainToken,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function logSubject(): string
    {
        return 'Avance de obra '.$this->update->project->code;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $project = $this->update->project;

        return (new MailMessage)
            ->subject("Novedades en tu obra — {$project->name}")
            ->greeting("Hola {$project->client->name},")
            ->line($this->update->title ?: 'Hemos publicado un nuevo avance de tu obra.')
            ->line($this->update->body)
            ->line("Avance actual: **{$project->progress} %**")
            ->action('Ver el estado de la obra', route('portal.enter', ['token' => $this->plainToken]));
    }
}