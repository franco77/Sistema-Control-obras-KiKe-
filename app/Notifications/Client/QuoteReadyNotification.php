<?php

declare(strict_types=1);

namespace App\Notifications\Client;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Envío del presupuesto al cliente con su enlace seguro de aprobación. */
class QuoteReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Quote $quote,
        public readonly string $plainToken,
        public readonly ?string $customMessage = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function logSubject(): string
    {
        return "Presupuesto {$this->quote->reference}";
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('portal.enter', ['token' => $this->plainToken]);

        $mail = (new MailMessage)
            ->subject("Tu presupuesto {$this->quote->reference} — ".setting('company.name', config('app.name')))
            ->greeting("Hola {$this->quote->client->name},")
            ->line("Ya tienes disponible el presupuesto **{$this->quote->title}**.");

        if ($this->customMessage) {
            $mail->line($this->customMessage);
        }

        return $mail
            ->line('Importe total: **'.money($this->quote->total).'** (IVA incluido)')
            ->when($this->quote->valid_until, fn (MailMessage $m) => $m->line(
                'Válido hasta el '.$this->quote->valid_until->format('d/m/Y').'.'
            ))
            ->action('Ver y aprobar el presupuesto', $url)
            ->line('Desde ese enlace puedes revisarlo con detalle, descargarlo en PDF y aprobarlo o rechazarlo.')
            ->salutation('Un saludo, '.setting('company.name', config('app.name')));
    }
}