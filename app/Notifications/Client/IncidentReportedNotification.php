<?php

declare(strict_types=1);

namespace App\Notifications\Client;

use App\Models\ProjectIncident;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Comunicación de una incidencia relevante al cliente. */
class IncidentReportedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ProjectIncident $incident,
        public readonly string $plainToken,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function logSubject(): string
    {
        return "Incidencia {$this->incident->code}";
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Incidencia en tu obra — {$this->incident->title}")
            ->greeting("Hola {$this->incident->project->client->name},")
            ->line("Queremos informarte de una incidencia detectada en **{$this->incident->project->name}**.")
            ->line("**{$this->incident->title}** ({$this->incident->severity->label()})")
            ->line($this->incident->description)
            ->action('Ver detalle en tu portal', route('portal.enter', ['token' => $this->plainToken]))
            ->line('Te mantendremos informado de su resolución.');
    }
}