<?php

declare(strict_types=1);

namespace App\Notifications\Client;

use App\Models\ProjectExtra;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Solicitud de aprobación de un extra durante la ejecución de la obra. */
class ExtraApprovalRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ProjectExtra $extra,
        public readonly string $plainToken,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function logSubject(): string
    {
        return "Extra {$this->extra->code}";
    }

    public function toMail(object $notifiable): MailMessage
    {
        $project = $this->extra->project;

        return (new MailMessage)
            ->subject("Aprobación pendiente: {$this->extra->title} — obra {$project->code}")
            ->greeting("Hola {$project->client->name},")
            ->line("Durante la ejecución de **{$project->name}** ha surgido un trabajo adicional que necesita tu visto bueno.")
            ->line("**{$this->extra->title}**")
            ->line($this->extra->description)
            ->line('Importe: **'.money($this->extra->total).'** (IVA incluido)')
            ->when($this->extra->extra_days > 0, fn (MailMessage $m) => $m->line(
                "Impacto en plazo: {$this->extra->extra_days} días adicionales."
            ))
            ->action('Revisar y decidir', route('portal.enter', ['token' => $this->plainToken]))
            ->line('Hasta que no lo apruebes no ejecutaremos este trabajo.');
    }
}