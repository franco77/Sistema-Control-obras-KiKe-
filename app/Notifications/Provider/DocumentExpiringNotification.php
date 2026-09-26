<?php

declare(strict_types=1);

namespace App\Notifications\Provider;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Recordatorio al proveedor de documentación legal a punto de caducar. */
class DocumentExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Document $document) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function logSubject(): string
    {
        return 'Documentación por caducar: '.$this->document->category->label();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu documentación caduca pronto')
            ->greeting('Hola,')
            ->line("Tu **{$this->document->category->label()}** caduca el ".$this->document->expires_on->format('d/m/Y').'.')
            ->line('Para poder seguir asignándote trabajos necesitamos el documento actualizado.')
            ->line('Puedes respondernos a este correo con el documento renovado.');
    }
}