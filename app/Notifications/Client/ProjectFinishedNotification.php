<?php

declare(strict_types=1);

namespace App\Notifications\Client;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Entrega de obra: cierre, garantía y documentación final. */
class ProjectFinishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Project $project,
        public readonly string $plainToken,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function logSubject(): string
    {
        return 'Obra finalizada '.$this->project->code;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Obra finalizada — {$this->project->name}")
            ->greeting("Hola {$this->project->client->name},")
            ->line('Hemos completado todos los trabajos contratados. ¡Gracias por confiar en nosotros!')
            ->line("Tu obra queda cubierta por una garantía de {$this->project->warranty_months} meses.")
            ->action('Ver documentación y fotos finales', route('portal.enter', ['token' => $this->plainToken]))
            ->line('El enlace seguirá activo durante el periodo de garantía.');
    }
}