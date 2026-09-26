<?php

declare(strict_types=1);

namespace App\Notifications\Internal;

use App\Models\ProjectExtra;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Avisa al equipo de la decisión del cliente sobre un extra. */
class ExtraDecisionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ProjectExtra $extra,
        public readonly bool $approved,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verb = $this->approved ? 'aprobado' : 'rechazado';

        return (new MailMessage)
            ->subject("Extra {$verb} — {$this->extra->code}")
            ->line("El cliente ha {$verb} el extra **{$this->extra->title}** de la obra {$this->extra->project->code}.")
            ->line('Importe: '.money($this->extra->total))
            ->action('Abrir la obra', route('admin.projects.show', $this->extra->project));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->approved ? 'extra.approved' : 'extra.rejected',
            'title' => "Extra {$this->extra->code} ".($this->approved ? 'aprobado' : 'rechazado'),
            'body' => $this->extra->project->name.' — '.money($this->extra->total),
            'url' => route('admin.projects.show', $this->extra->project),
        ];
    }
}