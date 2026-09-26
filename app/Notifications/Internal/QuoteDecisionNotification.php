<?php

declare(strict_types=1);

namespace App\Notifications\Internal;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Avisa al equipo de que el cliente ha aprobado o rechazado un presupuesto. */
class QuoteDecisionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Quote $quote,
        public readonly bool $approved,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verb = $this->approved ? 'APROBADO' : 'RECHAZADO';

        return (new MailMessage)
            ->subject("Presupuesto {$verb} — {$this->quote->reference}")
            ->line("{$this->quote->client->name} ha {$verb} el presupuesto **{$this->quote->title}**.")
            ->line('Importe: '.money($this->quote->total))
            ->when(! $this->approved && $this->quote->rejection_reason, fn (MailMessage $m) => $m->line(
                'Motivo: '.$this->quote->rejection_reason
            ))
            ->action('Abrir presupuesto', route('admin.quotes.show', $this->quote));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->approved ? 'quote.approved' : 'quote.rejected',
            'title' => "Presupuesto {$this->quote->reference} ".($this->approved ? 'aprobado' : 'rechazado'),
            'body' => $this->quote->client->name.' — '.money($this->quote->total),
            'url' => route('admin.quotes.show', $this->quote),
        ];
    }
}